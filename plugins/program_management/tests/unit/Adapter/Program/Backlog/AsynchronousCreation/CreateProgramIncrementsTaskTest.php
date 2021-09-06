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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\ProgramManagementProjectAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingArtifactCreationStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementsCreator;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Tests\Builder\ReplicationDataBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class CreateProgramIncrementsTaskTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\ProjectManager
     */
    private $project_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProgramIncrementsCreator
     */
    private $mirror_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PendingArtifactCreationStore
     */
    private $pending_artifact_creation_store;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PlanUserStoriesInMirroredProgramIncrements
     */
    private $user_stories_planner;
    private TestLogger $logger;
    private SearchTeamsOfProgram $teams_searcher;
    private GatherFieldValuesStub $values_gatherer;

    protected function setUp(): void
    {
        $this->teams_searcher                  = SearchTeamsOfProgramStub::buildTeams(102);
        $this->project_manager                 = $this->createStub(\ProjectManager::class);
        $this->planning_factory                = $this->createStub(\PlanningFactory::class);
        $this->mirror_creator                  = $this->createMock(ProgramIncrementsCreator::class);
        $this->logger                          = new TestLogger();
        $this->pending_artifact_creation_store = $this->createMock(PendingArtifactCreationStore::class);
        $this->user_stories_planner            = $this->createMock(PlanUserStoriesInMirroredProgramIncrements::class);
        $this->values_gatherer                 = GatherFieldValuesStub::withDefault();
    }

    private function getTask(): CreateProgramIncrementsTask
    {
        return new CreateProgramIncrementsTask(
            new PlanningAdapter($this->planning_factory, RetrieveUserStub::withGenericUser()),
            $this->mirror_creator,
            $this->logger,
            $this->pending_artifact_creation_store,
            $this->user_stories_planner,
            $this->teams_searcher,
            new ProgramManagementProjectAdapter($this->project_manager),
            BuildSynchronizedFieldsStub::withDefault(),
            RetrieveFieldValuesGathererStub::withGatherer($this->values_gatherer)
        );
    }

    public function testItCreateMirrors(): void
    {
        $replication = ReplicationDataBuilder::build();

        $team_project_id = 102;
        $team_project    = ProjectTestBuilder::aProject()->withId($team_project_id)->build();
        $this->project_manager->method('getProject')->willReturn($team_project);

        $planning = new \Planning(1, 'Root planning', $team_project_id, '', '');
        $planning->setPlanningTracker($replication->getTracker()->getFullTracker());
        $this->planning_factory->method('getRootPlanning')->willReturn($planning);

        $this->mirror_creator->expects(self::once())->method('createProgramIncrements');

        $this->pending_artifact_creation_store->expects(self::once())
            ->method('deleteArtifactFromPendingCreation')
            ->with($replication->getArtifact()->getId(), (int) $replication->getUser()->getId());

        $this->user_stories_planner->expects(self::once())->method('plan');

        $this->getTask()->createProgramIncrements($replication);
    }

    public function testItLogsWhenAnExceptionOccurs(): void
    {
        $this->values_gatherer = GatherFieldValuesStub::withError();
        $replication           = ReplicationDataBuilder::build();

        $this->user_stories_planner->expects(self::never())->method('plan');

        $this->getTask()->createProgramIncrements($replication);
        self::assertTrue($this->logger->hasErrorRecords());
    }
}
