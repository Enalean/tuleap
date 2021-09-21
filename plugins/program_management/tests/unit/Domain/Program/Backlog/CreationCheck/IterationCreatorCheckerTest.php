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
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class IterationCreatorCheckerTest extends TestCase
{
    private PFUser $user;
    private ProgramIdentifier $program;
    private TestLogger $logger;
    private RetrievePlanningMilestoneTracker $milestone_retriever;
    private VerifyIsIterationTrackerStub $iteration_tracker_verifier;
    private TrackerReference $tracker;
    private RetrieveVisibleIterationTrackerStub $iteration_tracker_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TimeboxCreatorChecker
     */
    private $timebox_creator_checker;
    private UserIdentifierStub $user_identifier;

    protected function setUp(): void
    {
        $this->logger                     = new TestLogger();
        $this->user                       = UserTestBuilder::aUser()->build();
        $this->user_identifier            = UserIdentifierStub::buildGenericUser();
        $this->program                    = ProgramIdentifierBuilder::build();
        $this->milestone_retriever        = RetrievePlanningMilestoneTrackerStub::withValidTrackers(
            TrackerReferenceStub::withDefaults()
        );
        $this->iteration_tracker_verifier = VerifyIsIterationTrackerStub::buildValidIteration();
        $this->tracker                    = TrackerReferenceStub::withId(102);

        $this->timebox_creator_checker = $this->createMock(TimeboxCreatorChecker::class);

        $this->iteration_tracker_retriever = RetrieveVisibleIterationTrackerStub::withValidTracker(
            $this->tracker
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

    public function testAllowArtifactCreationWhenTrackerIsNotIterationTracker(): void
    {
        $this->iteration_tracker_verifier = VerifyIsIterationTrackerStub::buildNotIteration();
        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    SearchTeamsOfProgramStub::buildTeams(),
                    new BuildProjectStub(),
                    $this->program
                ),
                new ConfigurationErrorsCollector(true),
                $this->user_identifier
            )
        );
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testAllowArtifactCreationWhenNoTeamLinkedToProgram(): void
    {
        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    SearchTeamsOfProgramStub::buildTeams(),
                    new BuildProjectStub(),
                    $this->program
                ),
                new ConfigurationErrorsCollector(true),
                $this->user_identifier
            )
        );
    }

    public function testDisallowArtifactCreationAndLogsExceptionWhenAtLeastOneTeamHasNoSecondPlanning(): void
    {
        $this->milestone_retriever = RetrievePlanningMilestoneTrackerStub::withNoPlanning();

        self::assertFalse(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    SearchTeamsOfProgramStub::buildTeams(104),
                    new BuildProjectStub(),
                    $this->program
                ),
                new ConfigurationErrorsCollector(true),
                $this->user_identifier
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
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    SearchTeamsOfProgramStub::buildTeams(104),
                    new BuildProjectStub(),
                    $this->program
                ),
                new ConfigurationErrorsCollector(true),
                $this->user_identifier
            )
        );
    }

    public function testAllowArtifactCreationWhenUserCanSeeTrackerAndAllChecksAreGoods(): void
    {
        $this->timebox_creator_checker->method('canTimeboxBeCreated')->willReturn(true);

        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    SearchTeamsOfProgramStub::buildTeams(104),
                    new BuildProjectStub(),
                    $this->program
                ),
                new ConfigurationErrorsCollector(true),
                $this->user_identifier
            )
        );
    }

    public function testDisallowArtifactCreationWhenUserCanSeeTrackerButAllChecksAreNotGoods(): void
    {
        $this->timebox_creator_checker->method('canTimeboxBeCreated')->willReturn(false);

        self::assertFalse(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    SearchTeamsOfProgramStub::buildTeams(104),
                    new BuildProjectStub(),
                    $this->program
                ),
                new ConfigurationErrorsCollector(true),
                $this->user_identifier
            )
        );
    }
}
