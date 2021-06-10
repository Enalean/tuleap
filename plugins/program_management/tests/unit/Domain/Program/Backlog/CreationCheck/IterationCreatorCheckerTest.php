<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use PFUser;
use Psr\Log\Test\TestLogger;
use Tracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Stub\ProgramStoreStub;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Stub\VerifyIsIterationTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class IterationCreatorCheckerTest extends TestCase
{
    private PFUser $user;
    private ProgramIdentifier $program;
    private TestLogger $logger;
    private RetrievePlanningMilestoneTracker $milestone_retriever;
    private VerifyIsIterationTrackerStub $iteration_tracker_verifier;
    private ProgramTracker $program_tracker;
    private RetrieveVisibleIterationTrackerStub $iteration_tracker_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TimeboxCreatorChecker
     */
    private $timebox_creator_checker;

    protected function setUp(): void
    {
        $this->logger            = new TestLogger();
        $this->user              = UserTestBuilder::aUser()->build();
        $this->program           = ProgramIdentifier::fromId(
            BuildProgramStub::stubValidProgram(),
            101,
            $this->user
        );
        $first_milestone_tracker = $this->createStub(Tracker::class);
        $first_milestone_tracker->method('getId')->willReturn(1);
        $this->milestone_retriever        = RetrievePlanningMilestoneTrackerStub::withValidTrackers($first_milestone_tracker);
        $this->iteration_tracker_verifier = VerifyIsIterationTrackerStub::buildValidIteration();
        $this->program_tracker            = new ProgramTracker(TrackerTestBuilder::aTracker()->build());

        $this->timebox_creator_checker = $this->createMock(TimeboxCreatorChecker::class);

        $iteration_tracker = TrackerTestBuilder::aTracker()->withId(102)->build();

        $this->iteration_tracker_retriever = RetrieveVisibleIterationTrackerStub::withValidTracker(
            $iteration_tracker
        );
    }

    public function testAllowArtifactCreationWhenTrackerIsNotIterationTracker(): void
    {
        $this->iteration_tracker_verifier = VerifyIsIterationTrackerStub::buildNotIteration();
        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->program_tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    ProgramStoreStub::buildTeams(),
                    new BuildProjectStub(),
                    ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
                )
            )
        );
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testAllowArtifactCreationWhenNoTeamLinkedToProgram(): void
    {
        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->program_tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    ProgramStoreStub::buildTeams(),
                    new BuildProjectStub(),
                    ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
                )
            )
        );
    }

    public function testDisallowArtifactCreationAndLogsExceptionWhenAtLeastOneTeamHasNoSecondPlanning(): void
    {
        $this->milestone_retriever = RetrievePlanningMilestoneTrackerStub::withNoPlanning();

        self::assertFalse(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->program_tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    ProgramStoreStub::buildTeams(104),
                    new BuildProjectStub(),
                    ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
                )
            )
        );

        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testAllowArtifactCreationWhenUserCanNotSeeIterationTracker(): void
    {
        $this->iteration_tracker_retriever = RetrieveVisibleIterationTrackerStub::withNotVisibleIterationTracker();

        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->program_tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    ProgramStoreStub::buildTeams(104),
                    new BuildProjectStub(),
                    ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
                )
            )
        );
    }

    public function testAllowArtifactCreationWhenUserCanSeeTrackerAndAllChecksAreGoods(): void
    {
        $this->timebox_creator_checker->method('canTimeboxBeCreated')->willReturn(true);

        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->program_tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    ProgramStoreStub::buildTeams(104),
                    new BuildProjectStub(),
                    ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
                )
            )
        );
    }

    public function testDisallowArtifactCreationWhenUserCanSeeTrackerButAllChecksAreNotGoods(): void
    {
        $this->timebox_creator_checker->method('canTimeboxBeCreated')->willReturn(false);

        self::assertFalse(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->program_tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    ProgramStoreStub::buildTeams(104),
                    new BuildProjectStub(),
                    ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
                )
            )
        );
    }

    private function getChecker(): IterationCreatorChecker
    {
        return new IterationCreatorChecker(
            $this->milestone_retriever,
            $this->iteration_tracker_verifier,
            $this->iteration_tracker_retriever,
            $this->timebox_creator_checker,
            $this->logger
        );
    }
}
