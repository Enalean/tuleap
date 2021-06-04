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

use ProjectManager;
use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlanningHasNoProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollectionFactory;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrieveRootPlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementCreatorCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TimeboxCreatorChecker
     */
    private $timebox_creator_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ProgramStore
     */
    private $program_store;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProjectManager
     */
    private $project_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|TrackerCollectionFactory
     */
    private $trackers_builder;
    private \Tracker $tracker;
    private \PFUser $user;
    private ProgramIdentifier $program;

    protected function setUp(): void
    {
        $this->program_store           = $this->createStub(ProgramStore::class);
        $this->project_manager         = $this->createMock(ProjectManager::class);
        $this->trackers_builder        = $this->createStub(TrackerCollectionFactory::class);
        $this->timebox_creator_checker = $this->createMock(TimeboxCreatorChecker::class);

        $this->tracker = TrackerTestBuilder::aTracker()->withId(102)->build();
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

        $this->mockTeamMilestoneTrackers($this->tracker);

        self::assertFalse(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                new ProgramTracker($this->tracker),
                $this->program
            )
        );
    }

    public function testAllowArtifactCreationWhenTrackerIsNotProgramIncrement(): void
    {
        $this->timebox_creator_checker->expects(self::never())->method('canTimeboxBeCreated');

        self::assertTrue(
            $this->getChecker(false)->canCreateAProgramIncrement(
                $this->user,
                new ProgramTracker($this->tracker),
                $this->program
            )
        );
    }

    public function testAllowArtifactCreationWhenOtherChecksPass(): void
    {
        $this->timebox_creator_checker->method('canTimeboxBeCreated')->willReturn(true);

        $this->mockTeamMilestoneTrackers($this->tracker);
        self::assertTrue(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                new ProgramTracker($this->tracker),
                $this->program
            )
        );
    }

    public function testAllowArtifactCreationWhenAProjectHasNoTeamProjects(): void
    {
        $this->program_store->method('getTeamProjectIdsForGivenProgramProject')->willReturn([]);
        $this->timebox_creator_checker->expects(self::never())->method('canTimeboxBeCreated');

        self::assertTrue(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                new ProgramTracker($this->tracker),
                $this->program
            )
        );
    }

    public function testDisallowArtifactCreationIfOneProjectDoesNotHaveARootPlanningWithAMilestoneTracker(): void
    {
        $this->program_store
            ->expects(self::once())
            ->method('getTeamProjectIdsForGivenProgramProject')
            ->willReturn([['team_project_id' => 104]]);

        $first_team_project = new \Project(
            ['group_id' => '104', 'unix_group_name' => 'proj02', 'group_name' => 'Project 02']
        );
        $this->trackers_builder->method('buildFromProgramProjectAndItsTeam')
            ->willThrowException(new PlanningHasNoProgramIncrementException(1));
        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with($first_team_project->getID())
            ->willReturn($first_team_project);

        self::assertFalse($this->getChecker()->canCreateAProgramIncrement(
            $this->user,
            new ProgramTracker($this->tracker),
            $this->program
        ));
    }

    private function getChecker(bool $build_valid_program_increment = true): ProgramIncrementCreatorChecker
    {
        $project_data_adapter        = new ProjectAdapter($this->project_manager);
        $projects_collection_builder = new TeamProjectsCollectionBuilder(
            $this->program_store,
            $project_data_adapter
        );
        $first_milestone_tracker     = $this->createStub(\Tracker::class);
        $first_milestone_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $first_milestone_tracker->method('getId')->willReturn(1);
        $root_milestone_retriever = RetrieveRootPlanningMilestoneTrackerStub::withValidTrackers(
            $first_milestone_tracker
        );

        $verify_program_increment = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();
        if (! $build_valid_program_increment) {
            $verify_program_increment = VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement();
        }

        return new ProgramIncrementCreatorChecker(
            $this->timebox_creator_checker,
            $verify_program_increment,
            $projects_collection_builder,
            $this->trackers_builder,
            $root_milestone_retriever,
            new TestLogger()
        );
    }

    private function mockTeamMilestoneTrackers(\Tracker $tracker): void
    {
        $this->program_store->expects(self::once())->method('getTeamProjectIdsForGivenProgramProject')
            ->willReturn([['team_project_id' => 104]]);

        $first_team_project = new \Project(
            ['group_id' => '104', 'unix_group_name' => 'proj02', 'group_name' => 'Project 02']
        );

        $this->trackers_builder->method('buildFromProgramProjectAndItsTeam')
            ->willReturn(new SourceTrackerCollection([new ProgramTracker($tracker)]));
        $this->project_manager->expects(self::once())
            ->method('getProject')
            ->with($first_team_project->getID())
            ->willReturn($first_team_project);
    }
}
