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
use Tuleap\Queue\WorkerEvent;

class AsynchronousJiraRunnerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JiraRunner
     */
    private $jira_runner;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PendingJiraImportDao
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PendingJiraImportBuilder
     */
    private $builder;
    /**
     * @var AsynchronousJiraRunner
     */
    private $async_runner;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        $this->jira_runner = Mockery::mock(JiraRunner::class);
        $this->dao         = Mockery::mock(PendingJiraImportDao::class);
        $this->builder     = Mockery::mock(PendingJiraImportBuilder::class);

        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->async_runner = new AsynchronousJiraRunner($this->jira_runner, $this->dao, $this->builder);
    }

    public function testItDoesNotProcessAnythingIfPayloadDoesNotContainsSufficientInformation(): void
    {
        $event = new WorkerEvent(
            $this->logger,
            ['event_name' => AsynchronousJiraRunner::TOPIC, 'payload' => []]
        );

        $this->logger
            ->shouldReceive('error')
            ->with('The payload for tuleap.tracker.creation.jira seems to be malformed')
            ->once();
        $this->logger
            ->shouldReceive('debug')
            ->with("Malformed payload for tuleap.tracker.creation.jira: array (\n)")
            ->once();

        $this->async_runner->process($event);
    }

    public function testItDoesNotProcessAnythingIfPendingJiraImportCannotBeFound(): void
    {
        $event = new WorkerEvent(
            $this->logger,
            ['event_name' => AsynchronousJiraRunner::TOPIC, 'payload' => ['pending_jira_import_id' => 123]]
        );

        $this->dao
            ->shouldReceive('searchById')
            ->with(123)
            ->once()
            ->andReturn(false);

        $this->logger
            ->shouldReceive('error')
            ->with(
                'Not able to process an event tuleap.tracker.creation.jira, the pending jira import #123 can not be found.'
            )
            ->once();

        $this->async_runner->process($event);
    }

    public function testItDoesNotProcessAnythingIfPendingJiraImportCannotBeBuilt(): void
    {
        $event = new WorkerEvent(
            $this->logger,
            ['event_name' => AsynchronousJiraRunner::TOPIC, 'payload' => ['pending_jira_import_id' => 123]]
        );

        $this->dao
            ->shouldReceive('searchById')
            ->with(123)
            ->once()
            ->andReturn(['data_from_db']);

        $this->builder
            ->shouldReceive('buildFromRow')
            ->with(['data_from_db'])
            ->once()
            ->andThrow(new UnableToBuildPendingJiraImportException('Project is not active'));

        $this->logger
            ->shouldReceive('error')
            ->with(
                'Not able to process an event tuleap.tracker.creation.jira, the pending jira import #123 can not be built: Project is not active'
            )
            ->once();

        $this->async_runner->process($event);
    }

    public function testItAsksToTheRunnerToProcessTheImport(): void
    {
        $event = new WorkerEvent(
            $this->logger,
            ['event_name' => AsynchronousJiraRunner::TOPIC, 'payload' => ['pending_jira_import_id' => 123]]
        );

        $this->dao
            ->shouldReceive('searchById')
            ->with(123)
            ->once()
            ->andReturn(['data_from_db']);

        $import = Mockery::mock(PendingJiraImport::class);
        $this->builder
            ->shouldReceive('buildFromRow')
            ->with(['data_from_db'])
            ->once()
            ->andReturn($import);

        $this->jira_runner
            ->shouldReceive('processAsyncJiraImport')
            ->with($import)
            ->once();

        $this->async_runner->process($event);
    }
}
