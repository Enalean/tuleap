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
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Queue\QueueFactory;

final class AsynchronousArtifactsDeletionActionsRunnerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArchiveAndDeleteArtifactTaskBuilder
     */
    private $task_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|QueueFactory
     */
    private $queue_factory;
    /**
     * @var AsynchronousArtifactsDeletionActionsRunner
     */
    private $runner;

    protected function setUp(): void
    {
        $this->queue_factory = \Mockery::mock(QueueFactory::class);
        $this->task_builder  = \Mockery::mock(ArchiveAndDeleteArtifactTaskBuilder::class);

        $this->runner = new AsynchronousArtifactsDeletionActionsRunner(
            \Mockery::mock(PendingArtifactRemovalDao::class),
            new NullLogger(),
            \Mockery::mock(\UserManager::class),
            $this->queue_factory,
            $this->task_builder
        );
    }

    public function testDoesNotTryToProcessTheDeletionAsynchronouslyWhenNoWorkerIsAvailable(): void
    {
        $this->queue_factory->shouldNotReceive('getPersistentQueue');
        $task = \Mockery::mock(ArchiveAndDeleteArtifactTask::class);
        $this->task_builder->shouldReceive('build')->once()->andReturn($task);
        $task->shouldReceive('archive')->once();

        \ForgeConfig::set('sys_nb_backend_workers', 0);

        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(1234);

        $this->runner->executeArchiveAndArtifactDeletion(
            $artifact,
            \Mockery::mock(\PFUser::class)
        );
    }
}
