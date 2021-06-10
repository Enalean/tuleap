<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Stub\ProgramStoreStub;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementCreatorCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TimeboxCreatorChecker
     */
    private $timebox_creator_checker;
    private ProgramTracker $tracker;
    private \PFUser $user;
    private ProgramIdentifier $program;
    private VerifyIsProgramIncrementTracker $program_verifier;
    private RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever;
    private RetrievePlanningMilestoneTracker $root_milestone_retriever;

    protected function setUp(): void
    {
        $this->timebox_creator_checker = $this->createMock(TimeboxCreatorChecker::class);
        $this->program_verifier        = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();

        $program_increment_tracker = TrackerTestBuilder::aTracker()->withId(102)->build();
        $this->tracker             = new ProgramTracker($program_increment_tracker);

        $this->program_increment_tracker_retriever = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(
            $program_increment_tracker
        );

        $first_milestone_tracker = $this->createStub(\Tracker::class);
        $first_milestone_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $first_milestone_tracker->method('getId')->willReturn(103);
        $this->root_milestone_retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers(
            $first_milestone_tracker
        );

        $this->user    = UserTestBuilder::aUser()->build();
        $this->program = ProgramIdentifier::fromId(
            BuildProgramStub::stubValidProgram(),
            101,
            $this->user
        );
    }

    public function testDisallowArtifactCreationWhenItIsAProgramIncrementTrackerAndOtherChecksDoNotPass(): void
    {
        $this->timebox_creator_checker->method('canTimeboxBeCreated')->willReturn(false);

        self::assertFalse(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    ProgramStoreStub::buildTeams(104),
                    new BuildProjectStub(),
                    ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
                )
            )
        );
    }

    public function testAllowArtifactCreationWhenTrackerIsNotProgramIncrement(): void
    {
        $this->timebox_creator_checker->expects(self::never())->method('canTimeboxBeCreated');
        $this->program_verifier = VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement();

        self::assertTrue(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    ProgramStoreStub::buildTeams(),
                    new BuildProjectStub(),
                    ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
                )
            )
        );
    }

    public function testAllowArtifactCreationWhenOtherChecksPass(): void
    {
        $this->timebox_creator_checker->method('canTimeboxBeCreated')->willReturn(true);

        self::assertTrue(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    ProgramStoreStub::buildTeams(104),
                    new BuildProjectStub(),
                    ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
                )
            )
        );
    }

    public function testAllowArtifactCreationWhenAProjectHasNoTeamProjects(): void
    {
        $this->timebox_creator_checker->expects(self::never())->method('canTimeboxBeCreated');

        self::assertTrue(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    ProgramStoreStub::buildTeams(),
                    new BuildProjectStub(),
                    ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
                )
            )
        );
    }

    public function testDisallowArtifactCreationIfProgramIncrementTrackerIsNotVisible(): void
    {
        $this->program_increment_tracker_retriever =
            RetrieveVisibleProgramIncrementTrackerStub::withNotVisibleProgramIncrementTracker();

        self::assertFalse(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    ProgramStoreStub::buildTeams(104),
                    new BuildProjectStub(),
                    ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
                )
            )
        );
    }

    public function testDisallowArtifactCreationIfOneProjectDoesNotHaveARootPlanningWithAMilestoneTracker(): void
    {
        $this->root_milestone_retriever = RetrievePlanningMilestoneTrackerStub::withNoPlanning();

        self::assertFalse($this->getChecker()->canCreateAProgramIncrement(
            $this->user,
            $this->tracker,
            $this->program,
            TeamProjectsCollection::fromProgramIdentifier(
                ProgramStoreStub::buildTeams(104),
                new BuildProjectStub(),
                ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
            )
        ));
    }

    private function getChecker(): ProgramIncrementCreatorChecker
    {
        return new ProgramIncrementCreatorChecker(
            $this->timebox_creator_checker,
            $this->program_verifier,
            $this->root_milestone_retriever,
            $this->program_increment_tracker_retriever,
            new TestLogger()
        );
    }
}
