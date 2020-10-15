<?php
/*
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\Asynchronous;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_ArtifactFactory;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

final class CreateMirrorsRunnerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_Artifact_ChangesetFactory
     */
    private $changeset_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PendingArtifactCreationDao
     */
    private $pending_artifact_creation_dao;

    /**
     * @var CreateMirrorsRunner
     */
    private $runner;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|QueueFactory
     */
    private $queue_factory;

    protected function setUp(): void
    {
        $logger                              = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->queue_factory                 = Mockery::mock(QueueFactory::class);
        $this->artifact_factory              = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->user_manager                  = Mockery::mock(UserManager::class);
        $this->pending_artifact_creation_dao = Mockery::mock(PendingArtifactCreationDao::class);
        $this->changeset_factory             = Mockery::mock(\Tracker_Artifact_ChangesetFactory::class);
        $this->runner                        = new CreateMirrorsRunner(
            $logger,
            $this->queue_factory,
            $this->artifact_factory,
            $this->user_manager,
            $this->pending_artifact_creation_dao,
            $this->changeset_factory
        );
    }

    public function testItExecuteMirrorsCreation(): void
    {
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);

        $user = UserTestBuilder::aUser()->withId(10)->build();

        $changeset = Mockery::mock(\Tracker_Artifact_Changeset::class);

        $queue = \Mockery::mock(PersistentQueue::class);
        $this->queue_factory->shouldReceive('getPersistentQueue')->andReturn($queue);

        $queue->shouldReceive('pushSinglePersistentMessage')
            ->withArgs(
                ['tuleap.tracker.artifact.creation', ['artifact_id' => $artifact->getId(), 'user_id' => $user->getId()]]
            )
            ->once();

        $this->runner->executeMirrorsCreation($artifact, $user, $changeset);
    }

    public function testAddListenerThrowsExceptionWhenPendingArtifactNotFound(): void
    {
        $payload = ['artifact_id' => 101, 'user_id' => 201, 'changeset_id' => 301];
        $event   = $this->mockAnEvent($payload);
        $this->pending_artifact_creation_dao->shouldReceive('getPendingArtifactById')
            ->once()
            ->with(101, 201)
            ->andReturnNull();

        $this->expectException(PendingArtifactNotFoundException::class);

        $this->runner->addListener($event);
    }

    public function testAddListenerThrowsExceptionWhenAggregatorArtifactNotFound(): void
    {
        $payload = ['artifact_id' => 101, 'user_id' => 201, 'changeset_id' => 301];
        $result = ['aggregator_artifact_id' => 101, 'user_id' => 201, 'changeset_id' => 301];
        $event   = $this->mockAnEvent($payload);
        $this->pending_artifact_creation_dao->shouldReceive('getPendingArtifactById')
            ->once()
            ->with(101, 201)
            ->andReturn($result);

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(101)
            ->andReturnNull();

        $this->expectException(PendingArtifactNotFoundException::class);

        $this->runner->addListener($event);
    }

    public function testAddListenerThrowsExceptionWhenUserNotFound(): void
    {
        $payload = ['artifact_id' => 101, 'user_id' => 201, 'changeset_id' => 301];
        $result = ['aggregator_artifact_id' => 101, 'user_id' => 201, 'changeset_id' => 301];
        $event   = $this->mockAnEvent($payload);
        $this->pending_artifact_creation_dao->shouldReceive('getPendingArtifactById')
            ->once()
            ->with(101, 201)
            ->andReturn($result);

        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(101)
            ->andReturn($artifact);

        $this->user_manager->shouldReceive('getUserById')->once()->andReturnNull();

        $this->expectException(PendingArtifactUserNotFoundException::class);

        $this->runner->addListener($event);
    }

    public function testAddListenerThrowsExceptionWhenChangesetNotFound(): void
    {
        $payload = ['artifact_id' => 101, 'user_id' => 201, 'changeset_id' => 301];
        $result = ['aggregator_artifact_id' => 101, 'user_id' => 201, 'changeset_id' => 301];
        $event   = $this->mockAnEvent($payload);
        $this->pending_artifact_creation_dao->shouldReceive('getPendingArtifactById')
            ->once()
            ->with(101, 201)
            ->andReturn($result);

        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(101)
            ->andReturn($artifact);

        $user = UserTestBuilder::aUser()->withId(201)->build();
        $this->user_manager->shouldReceive('getUserById')->once()->andReturn($user);

        $this->changeset_factory->shouldReceive('getChangeset')->with($artifact, 301)->andReturnNull();

        $this->expectException(PendingArtifactChangesetNotFoundException::class);

        $this->runner->addListener($event);
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|WorkerEvent
     */
    private function mockAnEvent(array $payload)
    {
        $event = Mockery::mock(WorkerEvent::class);
        $event->shouldReceive('getPayload')->andReturn($payload);
        $event->shouldReceive('getEventName')->andReturn('tuleap.tracker.artifact.creation');

        return $event;
    }
}
