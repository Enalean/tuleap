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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;

class JiraRunnerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|QueueFactory
     */
    private $queue_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PendingJiraImportDao
     */
    private $dao;
    /**
     * @var JiraRunner
     */
    private $runner;

    protected function setUp(): void
    {
        $this->logger        = Mockery::mock(LoggerInterface::class);
        $this->queue_factory = Mockery::mock(QueueFactory::class);
        $this->dao           = Mockery::mock(PendingJiraImportDao::class);

        $this->runner = new JiraRunner($this->logger, $this->queue_factory, $this->dao);
    }

    public function testQueueJiraImportEvent(): void
    {
        $persistent_queue = Mockery::mock(PersistentQueue::class);
        $this->queue_factory
            ->shouldReceive('getPersistentQueue')
            ->with('app_user_events', 'redis')
            ->andReturn($persistent_queue);

        $persistent_queue
            ->shouldReceive('pushSinglePersistentMessage')
            ->with(
                'tuleap.tracker.creation.jira',
                [
                    'pending_jira_import_id' => 123,
                ]
            );

        $this->runner->queueJiraImportEvent(123);
    }

    public function testItLogsErrorWhenItCannotQueueTheEvent(): void
    {
        $persistent_queue = Mockery::mock(PersistentQueue::class);
        $this->queue_factory
            ->shouldReceive('getPersistentQueue')
            ->with('app_user_events', 'redis')
            ->andReturn($persistent_queue);

        $persistent_queue
            ->shouldReceive('pushSinglePersistentMessage')
            ->with(
                'tuleap.tracker.creation.jira',
                [
                    'pending_jira_import_id' => 123,
                ]
            )
            ->andThrow(\Exception::class);

        $this->logger
            ->shouldReceive('error')
            ->with('Unable to queue notification for Jira import #123.')
            ->once();

        $this->runner->queueJiraImportEvent(123);
    }

    public function testProcessAsyncJiraImport(): void
    {
        $import = Mockery::mock(PendingJiraImport::class);
        $import->shouldReceive(['getId' => 123]);

        $this->logger->shouldReceive('error')->with('Not implemented yet')->once();
        $this->dao->shouldReceive('deleteById')->with(123)->once();

        $this->runner->processAsyncJiraImport($import);
    }
}
