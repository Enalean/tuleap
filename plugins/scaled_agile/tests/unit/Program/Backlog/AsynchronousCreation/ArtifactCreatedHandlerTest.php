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

namespace Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Backlog\ProgramDao;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactCreatedHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|CreateProjectIncrementsRunner
     */
    private $asyncronous_runner;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|PendingArtifactCreationDao
     */
    private $pending_artifact_creation_dao;

    /**
     * @var ArtifactCreatedHandler
     */
    private $handler;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProgramDao
     */
    private $program_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|PlanningAdapter
     */
    private $planning_adapter;

    protected function setUp(): void
    {
        $this->program_dao      = M::mock(ProgramDao::class);
        $this->planning_adapter = M::mock(PlanningAdapter::class);
        $this->pending_artifact_creation_dao = M::mock(PendingArtifactCreationDao::class);
        $this->asyncronous_runner            = M::mock(CreateProjectIncrementsRunner::class);
        $this->handler          = new ArtifactCreatedHandler(
            $this->program_dao,
            $this->asyncronous_runner,
            $this->pending_artifact_creation_dao,
            $this->planning_adapter
        );
    }

    public function testHandleDelegatesToAsynchronousMirrorCreator(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $tracker = TrackerTestBuilder::aTracker()->withId(15)->withProject($project)->build();
        $artifact->shouldReceive('getTracker')->andReturn($tracker)->once();
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->program_dao->shouldReceive('isProjectAProgramProject')->andReturnTrue();
        $current_user = UserTestBuilder::aUser()->withId(1001)->build();
        $planning = new PlanningData($tracker, 7, 'Irrelevant', []);
        $this->planning_adapter->shouldReceive('buildRootPlanning')->andReturn($planning);

        $this->pending_artifact_creation_dao->shouldReceive('addArtifactToPendingCreation')
            ->withArgs([$artifact->getId(), $current_user->getId(), $changeset->getId()])
            ->once();

        $this->asyncronous_runner->shouldReceive('executeProjectIncrementsCreation')
            ->withArgs([$artifact, $current_user, $changeset])
            ->once();

        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));
    }

    public function testHandleReactsOnlyToArtifactsFromProgramProjects(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker = TrackerTestBuilder::aTracker()->withId(15)->withProject($project)->build();
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->program_dao->shouldReceive('isProjectAProgramProject')->with(101)->once()->andReturnFalse();

        $current_user = UserTestBuilder::aUser()->build();
        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));

        $this->asyncronous_runner->shouldNotHaveReceived('executeMirrorsCreation');
    }

    public function testHandleDoesNotReactWhenNoPlanningException(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker = TrackerTestBuilder::aTracker()->withId(15)->withProject($project)->build();
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->program_dao->shouldReceive('isProjectAProgramProject')->andReturnTrue();
        $this->planning_adapter->shouldReceive('buildRootPlanning')->andThrow(new TopPlanningNotFoundInProjectException(102));

        $current_user = UserTestBuilder::aUser()->build();
        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));

        $this->asyncronous_runner->shouldNotHaveReceived('executeMirrorsCreation');
    }

    public function testHandleReactsOnlyToTopMilestones(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $top_tracker = TrackerTestBuilder::aTracker()->withId(404)->withProject($project)->build();
        $artifact->shouldReceive('getTracker')->andReturn($top_tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->program_dao->shouldReceive('isProjectAProgramProject')->andReturnTrue();

        $other_tracker = TrackerTestBuilder::aTracker()->withId(12)->withProject($project)->build();
        $planning = new PlanningData($other_tracker, 7, 'Irrelevant', []);
        $this->planning_adapter->shouldReceive('buildRootPlanning')->andReturn($planning);

        $current_user = UserTestBuilder::aUser()->build();
        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));

        $this->asyncronous_runner->shouldNotHaveReceived('executeMirrorsCreation');
    }
}
