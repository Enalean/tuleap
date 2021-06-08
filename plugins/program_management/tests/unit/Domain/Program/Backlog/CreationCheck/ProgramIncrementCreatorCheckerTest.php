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
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;
use Tuleap\ProgramManagement\Stub\BuildPlanProgramIncrementConfigurationStub;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
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
    private BuildPlanProgramIncrementConfiguration $program_increment_tracker_retriever;
    private RetrievePlanningMilestoneTracker $root_milestone_retriever;

    protected function setUp(): void
    {
        $this->timebox_creator_checker = $this->createMock(TimeboxCreatorChecker::class);
        $this->program_verifier        = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();

        $this->tracker                             = new ProgramTracker(
            TrackerTestBuilder::aTracker()->withId(102)->build()
        );
        $this->program_increment_tracker_retriever = BuildPlanProgramIncrementConfigurationStub::withValidTracker(
            $this->tracker
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
                new TeamProjectsCollection([new Project(104, 'project', 'Project 1')])
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
                new TeamProjectsCollection([])
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
                new TeamProjectsCollection([new Project(104, 'project', 'Project 1')])
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
                new TeamProjectsCollection([])
            )
        );
    }

    public function testDisallowArtifactCreationIfProgramIncrementTrackerIsNotVisible(): void
    {
        $this->program_increment_tracker_retriever = BuildPlanProgramIncrementConfigurationStub::withNotVisibleProgramIncrementTracker();

        self::assertFalse(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                $this->tracker,
                $this->program,
                new TeamProjectsCollection([new Project(104, 'project', 'Project 1')])
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
            new TeamProjectsCollection([new Project(104, 'project', 'Project 1')])
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
