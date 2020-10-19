<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\MultiProjectBacklog\Aggregator\AggregatorDao;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\Asynchronous\CreateMirrorsRunner;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\Asynchronous\PendingArtifactCreationDao;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;

final class ArtifactCreatedHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|CreateMirrorsRunner
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
     * @var M\LegacyMockInterface|M\MockInterface|AggregatorDao
     */
    private $aggregator_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningFactory
     */
    private $planning_factory;

    protected function setUp(): void
    {
        $this->aggregator_dao                = M::mock(AggregatorDao::class);
        $this->planning_factory              = M::mock(\PlanningFactory::class);
        $this->pending_artifact_creation_dao = M::mock(PendingArtifactCreationDao::class);
        $this->asyncronous_runner            = M::mock(CreateMirrorsRunner::class);
        $this->handler                       = new ArtifactCreatedHandler(
            $this->aggregator_dao,
            $this->planning_factory,
            $this->asyncronous_runner,
            $this->pending_artifact_creation_dao
        );
    }

    public function testHandleDelegatesToAsynchronousMirrorCreator(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $tracker = $this->buildTestTracker(15, $project);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->andReturnTrue();
        $current_user = UserTestBuilder::aUser()->withId(1001)->build();
        $planning = new \Planning(7, 'Irrelevant', 101, 'Irrelevant', 'Irrelevant', [], 15);
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andReturn($planning);

        $this->pending_artifact_creation_dao->shouldReceive('addArtifactToPendingCreation')
            ->withArgs([$artifact->getId(), $current_user->getId(), $changeset->getId()])
            ->once();

        $this->asyncronous_runner->shouldReceive('executeMirrorsCreation')
            ->withArgs([$artifact, $current_user, $changeset])
            ->once();

        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));
    }

    public function testHandleReactsOnlyToArtifactsFromAggregatorProjects(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker  = $this->buildTestTracker(15, $project);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->with(101)->once()->andReturnFalse();

        $current_user = UserTestBuilder::aUser()->build();
        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));

        $this->asyncronous_runner->shouldNotHaveReceived('executeMirrorsCreation');
    }

    public function testHandleDoesNotReactWhenNoPlanningException(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker  = $this->buildTestTracker(15, $project);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->andReturnTrue();
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andThrow(new \Planning_NoPlanningsException());

        $current_user = UserTestBuilder::aUser()->build();
        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));

        $this->asyncronous_runner->shouldNotHaveReceived('executeMirrorsCreation');
    }

    public function testHandleReactsOnlyToTopMilestones(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker  = $this->buildTestTracker(404, $project);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->andReturnTrue();
        $planning = new \Planning(7, 'Irrelevant', 101, 'Irrelevant', 'Irrelevant', [], 15);
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andReturn($planning);

        $current_user = UserTestBuilder::aUser()->build();
        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));

        $this->asyncronous_runner->shouldNotHaveReceived('executeMirrorsCreation');
    }

    private function buildTestTracker(int $tracker_id, \Project $project): \Tracker
    {
        $tracker = new \Tracker(
            $tracker_id,
            null,
            'Irrelevant',
            'Irrelevant',
            'irrelevant',
            false,
            null,
            null,
            null,
            null,
            true,
            false,
            \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            \Tuleap\Tracker\TrackerColor::default(),
            false
        );
        $tracker->setProject($project);
        return $tracker;
    }
}
