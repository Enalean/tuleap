<?php
/**
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\NullLogger;
use Tuleap\Queue\IsAsyncTaskProcessingAvailable;
use Tuleap\Queue\QueueFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class AsynchronousArtifactsDeletionActionsRunnerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private AsynchronousArtifactsDeletionActionsRunner $runner;
    private IsAsyncTaskProcessingAvailable $worker_availability;
    private QueueFactory|\PHPUnit\Framework\MockObject\MockObject $queue_factory;
    private ArchiveAndDeleteArtifactTaskBuilder|\PHPUnit\Framework\MockObject\MockObject $task_builder;
    private PendingArtifactRemovalDao|\PHPUnit\Framework\MockObject\MockObject $pending_artifact_removal_dao;


    protected function setUp(): void
    {
        $this->queue_factory       = $this->createMock(QueueFactory::class);
        $this->task_builder        = $this->createMock(ArchiveAndDeleteArtifactTaskBuilder::class);
        $this->worker_availability = new class implements IsAsyncTaskProcessingAvailable {
            public function canProcessAsyncTasks(): bool
            {
                return false;
            }
        };

        $this->pending_artifact_removal_dao = $this->createMock(PendingArtifactRemovalDao::class);
        $this->runner                       = new AsynchronousArtifactsDeletionActionsRunner(
            $this->pending_artifact_removal_dao,
            new NullLogger(),
            $this->createMock(\UserManager::class),
            $this->queue_factory,
            $this->worker_availability,
            $this->task_builder
        );
    }

    public function testDoesNotTryToProcessTheDeletionAsynchronouslyWhenNoWorkerIsAvailable(): void
    {
        $this->queue_factory->expects(self::never())->method('getPersistentQueue');
        $task = $this->createMock(ArchiveAndDeleteArtifactTask::class);
        $task->expects(self::once())->method('archive');
        $this->task_builder->expects(self::once())->method('build')->willReturn($task);

        $artifact = ArtifactTestBuilder::anArtifact(1234)->build();

        $project_id = 104;
        $this->runner->executeArchiveAndArtifactDeletion(
            $artifact,
            UserTestBuilder::anActiveUser()->build(),
            DeletionContext::regularDeletion($project_id)
        );
    }
}
