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
use PHPUnit\Framework\MockObject\Stub;
use ProjectManager;
use Psr\Log\Test\TestLogger;
use Tracker;
use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class IterationCreatorCheckerTest extends TestCase
{
    /**
     * @var Stub|ProgramStore
     */
    private $program_store;
    /**
     * @var Stub|ProjectManager
     */
    private $project_manager;
    private PFUser $user;
    private ProgramIdentifier $program;
    private TestLogger $logger;
    private RetrievePlanningMilestoneTracker $milestone_retriever;

    protected function setUp(): void
    {
        $this->program_store   = $this->createStub(ProgramStore::class);
        $this->project_manager = $this->createStub(ProjectManager::class);
        $this->logger          = new TestLogger();

        $this->user              = UserTestBuilder::aUser()->build();
        $this->program           = ProgramIdentifier::fromId(
            BuildProgramStub::stubValidProgram(),
            101,
            $this->user
        );
        $first_milestone_tracker = $this->createStub(Tracker::class);
        $first_milestone_tracker->method('getId')->willReturn(1);
        $this->milestone_retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers($first_milestone_tracker);
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
        $first_team_project = ProjectTestBuilder::aProject()->withId(104)->build();

        $this->project_manager
            ->method('getProject')
            ->willReturn($first_team_project);
        $this->milestone_retriever = RetrievePlanningMilestoneTrackerStub::withNoPlanning();

        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->user,
                $this->program
            )
        );
        self::assertTrue($this->logger->hasErrorRecords());
    }

    private function getChecker(): IterationCreatorChecker
    {
        $project_data_adapter        = new ProjectAdapter($this->project_manager);
        $projects_collection_builder = new TeamProjectsCollectionBuilder(
            $this->program_store,
            $project_data_adapter
        );
        return new IterationCreatorChecker(
            $projects_collection_builder,
            $this->milestone_retriever,
            $this->logger
        );
    }
}
