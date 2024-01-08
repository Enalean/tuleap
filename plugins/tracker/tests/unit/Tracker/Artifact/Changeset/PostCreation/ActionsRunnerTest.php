<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Psr\Log\LogLevel;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\WorkerAvailability;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

final class ActionsRunnerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var MockInterface
     */
    private $logger;
    /**
     * @var \Mockery\LegacyMockInterface|MockInterface|WorkerAvailability
     */
    private $worker_availability;

    protected function setUp(): void
    {
        $this->logger              = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->worker_availability = \Mockery::mock(WorkerAvailability::class);
    }

    public function testAllPostCreationTasksAreExecuted(): void
    {
        $task_1 = \Mockery::mock(PostCreationTask::class);
        $task_2 = \Mockery::mock(PostCreationTask::class);

        $actions_runner = new ActionsRunner($this->logger, new QueueFactory($this->logger), $this->worker_availability, $task_1, $task_2);

        $this->worker_availability->shouldReceive('canProcessAsyncTasks')->andReturn(false);

        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $task_1->shouldReceive('execute')->with($changeset, true)->once();
        $task_2->shouldReceive('execute')->with($changeset, true)->once();

        $actions_runner->executePostCreationActions($changeset, true);
    }

    public function testPostCreationTaskCanBeExecutedAsynchronously(): void
    {
        $task = \Mockery::mock(PostCreationTask::class);

        $queue_factory = \Mockery::mock(QueueFactory::class);
        $queue         = \Mockery::mock(PersistentQueue::class);
        $queue->shouldReceive('pushSinglePersistentMessage')->once();
        $queue_factory->shouldReceive('getPersistentQueue')->andReturn($queue);

        $actions_runner = new ActionsRunner($this->logger, $queue_factory, $this->worker_availability, $task);

        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getId');
        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(753);
        $changeset->shouldReceive('getArtifact')->andReturn($artifact);

        $this->worker_availability->shouldReceive('canProcessAsyncTasks')->andReturn(true);

        $task->shouldNotReceive('execute');

        $actions_runner->executePostCreationActions($changeset, true);
    }

    public function testAsyncPostCreationTasksFallbackInSyncProcessingInCaseOfError(): void
    {
        $task = \Mockery::mock(PostCreationTask::class);

        $actions_runner = new ActionsRunner($this->logger, new QueueFactory($this->logger), $this->worker_availability, $task);

        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getId');

        $this->worker_availability->shouldReceive('canProcessAsyncTasks')->andReturn(true);

        $this->logger->shouldReceive('log')->with(LogLevel::ERROR, \Mockery::any(), \Mockery::any())->once();

        $task->shouldReceive('execute')->once();

        $actions_runner->executePostCreationActions($changeset, true);
    }

    public function testTasksAreExecutedInOrder(): void
    {
        $task_1 = \Mockery::mock(PostCreationTask::class);
        $task_2 = \Mockery::mock(PostCreationTask::class);
        $task_3 = \Mockery::mock(PostCreationTask::class);

        $actions_runner = new ActionsRunner($this->logger, new QueueFactory($this->logger), $this->worker_availability, $task_1, $task_2, $task_3);

        $this->worker_availability->shouldReceive('canProcessAsyncTasks')->andReturn(false);

        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $last_task_name = '';
        $task_1->shouldReceive('execute')->andReturnUsing(function () use (&$last_task_name) {
            $this->assertEmpty($last_task_name);
            $last_task_name = 'task_1';
        });
        $task_2->shouldReceive('execute')->andReturnUsing(function () use (&$last_task_name) {
            $this->assertSame($last_task_name, 'task_1');
            $last_task_name = 'task_2';
        });
        $task_3->shouldReceive('execute')->andReturnUsing(function () use (&$last_task_name) {
            $this->assertSame($last_task_name, 'task_2');
            $last_task_name = 'task_3';
        });

        $actions_runner->executePostCreationActions($changeset, true);
        $this->assertSame($last_task_name, 'task_3');
    }

    public function testAsyncTasksAreNotRunWhenActionsRunnerRunSync(): void
    {
        $task_1 = $this->createMock(PostCreationTask::class);
        $task_2 = $this->createMock(PostCreationTask::class);

        $actions_runner = new ActionsRunner($this->logger, new QueueFactory($this->logger), $this->worker_availability, $task_1);
        $actions_runner->addAsyncPostCreationTasks($task_2);

        $changeset = ChangesetTestBuilder::aChangeset('1')->build();

        $this->worker_availability->shouldReceive('canProcessAsyncTasks')->andReturn(false);
        $task_1->expects(self::once())->method('execute')->with($changeset, true);
        $task_2->expects(self::never())->method('execute');

        $actions_runner->executePostCreationActions($changeset, true);
    }

    public function testAsyncTasksAreRanWhenActionsRunnerRunAsync(): void
    {
        $task_1 = $this->createMock(PostCreationTask::class);
        $task_2 = $this->createMock(PostCreationTask::class);

        $actions_runner = new ActionsRunner($this->logger, new QueueFactory($this->logger), $this->worker_availability, $task_1);
        $actions_runner->addAsyncPostCreationTasks($task_2);

        $changeset = ChangesetTestBuilder::aChangeset('1')->build();

        $task_1->expects(self::once())->method('execute')->with($changeset, true);
        $task_2->expects(self::once())->method('execute')->with($changeset, true);

        $actions_runner->processAsyncPostCreationActions($changeset, true);
    }
}
