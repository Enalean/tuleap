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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use ProjectManager;
use Psr\Log\Test\TestLogger;
use Tracker;
use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class IterationCreatorCheckerTest extends TestCase
{
    /**
     * @var Stub|ProgramStore
     */
    private $program_store;
    /**
     * @var MockObject|ProjectManager
     */
    private $project_manager;
    private PFUser $user;
    private ProgramIdentifier $program;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->program_store   = $this->createStub(ProgramStore::class);
        $this->project_manager = $this->createMock(ProjectManager::class);
        $this->logger          = new TestLogger();

        $this->user    = UserTestBuilder::aUser()->build();
        $this->program = ProgramIdentifier::fromId(
            BuildProgramStub::stubValidProgram(),
            101,
            $this->user
        );
    }

    public function testAllowArtifactCreationWhenNoTeamLinkedToProgram(): void
    {
        $this->program_store->method('getTeamProjectIdsForGivenProgramProject')->willReturn([]);

        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->program
            )
        );
    }

    public function testAllowArtifactCreationAndLogsExceptionWhenAtLeastOneTeamHasNoSecondPlanning(): void
    {
        $this->program_store
            ->expects(self::once())
            ->method('getTeamProjectIdsForGivenProgramProject')
            ->willReturn([['team_project_id' => 104]]);

        $first_team_project = new \Project(
            ['group_id' => '104', 'unix_group_name' => 'proj02', 'group_name' => 'Project 02']
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with($first_team_project->getID())
            ->willReturn($first_team_project);

        self::assertTrue($this->getChecker(false)->canCreateAnIteration(
            $this->user,
            $this->program
        ));


        self::assertTrue($this->logger->hasErrorRecords());
    }

    private function getChecker(bool $retrieve_planning_trackers = true): IterationCreatorChecker
    {
        $project_data_adapter        = new ProjectAdapter($this->project_manager);
        $projects_collection_builder = new TeamProjectsCollectionBuilder(
            $this->program_store,
            $project_data_adapter
        );
        $root_milestone_retriever    = RetrievePlanningMilestoneTrackerStub::withValidTrackers();

        if ($retrieve_planning_trackers) {
            $first_milestone_tracker = $this->createStub(Tracker::class);
            $first_milestone_tracker->method('getId')->willReturn(1);
            $root_milestone_retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers(
                $first_milestone_tracker
            );
        }

        return new IterationCreatorChecker(
            $projects_collection_builder,
            $root_milestone_retriever,
            $this->logger
        );
    }
}
