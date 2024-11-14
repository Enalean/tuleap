<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use ColinODell\PsrTestLogger\TestLogger;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\NullLogger;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Queue\WorkerEventContent;

class AsynchronousActionsRunnerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private $actions_runner;
    private $artifact_factory;

    protected function setUp(): void
    {
        $this->actions_runner   = \Mockery::mock(ActionsRunner::class);
        $this->artifact_factory = \Mockery::mock(\Tracker_ArtifactFactory::class);
    }

    public function testActionsAreProcessed()
    {
        $async_actions_runner = new AsynchronousActionsRunner($this->actions_runner, $this->artifact_factory);

        $worker_event = new WorkerEvent(
            new NullLogger(),
            new WorkerEventContent('Event name', ['artifact_id' => 1, 'changeset_id' => 1, 'send_notifications' => true])
        );

        $artifact  = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $artifact->shouldReceive('getChangeset')->andReturns($changeset);
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturns($artifact);

        $this->actions_runner->shouldReceive('processAsyncPostCreationActions')->with($changeset, true)->once();

        $async_actions_runner->process($worker_event);
    }

    public function testNotWellFormedPayloadAreHandled()
    {
        $async_actions_runner = new AsynchronousActionsRunner($this->actions_runner, $this->artifact_factory);

        $logger       = new TestLogger();
        $worker_event = new WorkerEvent($logger, new WorkerEventContent('Event name', []));

        $this->actions_runner->shouldReceive('processAsyncPostCreationActions')->never();

        $async_actions_runner->process($worker_event);
        self::assertTrue($logger->hasWarningRecords());
        self::assertTrue($logger->hasDebugRecords());
    }

    public function testActionsAreNotProcessedWhenArtifactIsNotFound()
    {
        $async_actions_runner = new AsynchronousActionsRunner($this->actions_runner, $this->artifact_factory);

        $logger       = new TestLogger();
        $worker_event = new WorkerEvent(
            $logger,
            new WorkerEventContent('Event name', ['artifact_id' => 1, 'changeset_id' => 1, 'send_notifications' => true])
        );

        $this->artifact_factory->shouldReceive('getArtifactById')->andReturns(null);

        $this->actions_runner->shouldReceive('processAsyncPostCreationActions')->never();

        $async_actions_runner->process($worker_event);

        self::assertTrue($logger->hasInfoRecords());
    }

    public function testActionsAreNotProcessedWhenChangesetIsNotFound()
    {
        $async_actions_runner = new AsynchronousActionsRunner($this->actions_runner, $this->artifact_factory);

        $logger       = new TestLogger();
        $worker_event = new WorkerEvent(
            $logger,
            new WorkerEventContent('Event name', ['artifact_id' => 1, 'changeset_id' => 1, 'send_notifications' => true])
        );

        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getChangeset')->andReturns(null);
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturns($artifact);

        $this->actions_runner->shouldReceive('processAsyncPostCreationActions')->never();

        $async_actions_runner->process($worker_event);
        self::assertTrue($logger->hasInfoRecords());
    }
}
