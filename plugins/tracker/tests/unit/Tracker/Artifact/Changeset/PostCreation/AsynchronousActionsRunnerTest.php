<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use PHPUnit\Framework\TestCase;
use Tuleap\Queue\WorkerEvent;

class AsynchronousActionsRunnerTest extends TestCase
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

        $worker_event = \Mockery::mock(WorkerEvent::class);
        $worker_event->shouldReceive('getPayload')->andReturns(['artifact_id' => 1, 'changeset_id' => 1]);

        $artifact  = \Mockery::mock(\Tracker_Artifact::class);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $artifact->shouldReceive('getChangeset')->andReturns($changeset);
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturns($artifact);

        $this->actions_runner->shouldReceive('processAsyncPostCreationActions')->once();

        $async_actions_runner->process($worker_event);
    }

    public function testNotWellFormedPayloadAreHandled()
    {
        $async_actions_runner = new AsynchronousActionsRunner($this->actions_runner, $this->artifact_factory);

        $worker_event = \Mockery::mock(WorkerEvent::class);
        $worker_event->shouldReceive('getPayload')->andReturns([]);
        $worker_event->shouldReceive('getEventName')->andReturns('Event name');

        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $worker_event->shouldReceive('getLogger')->andReturns($logger);
        $logger->shouldReceive('warning')->atLeast()->once();
        $logger->shouldReceive('debug')->atLeast()->once();
        $this->actions_runner->shouldReceive('processAsyncPostCreationActions')->never();

        $async_actions_runner->process($worker_event);
    }

    public function testActionsAreNotProcessedWhenArtifactIsNotFound()
    {
        $async_actions_runner = new AsynchronousActionsRunner($this->actions_runner, $this->artifact_factory);

        $worker_event = \Mockery::mock(WorkerEvent::class);
        $worker_event->shouldReceive('getPayload')->andReturns(['artifact_id' => 1, 'changeset_id' => 1]);
        $worker_event->shouldReceive('getEventName')->andReturns('Event name');

        $this->artifact_factory->shouldReceive('getArtifactById')->andReturns(null);

        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $worker_event->shouldReceive('getLogger')->andReturns($logger);
        $logger->shouldReceive('info')->atLeast()->once();

        $this->actions_runner->shouldReceive('processAsyncPostCreationActions')->never();

        $async_actions_runner->process($worker_event);
    }

    public function testActionsAreNotProcessedWhenChangesetIsNotFound()
    {
        $async_actions_runner = new AsynchronousActionsRunner($this->actions_runner, $this->artifact_factory);

        $worker_event = \Mockery::mock(WorkerEvent::class);
        $worker_event->shouldReceive('getPayload')->andReturns(['artifact_id' => 1, 'changeset_id' => 1]);
        $worker_event->shouldReceive('getEventName')->andReturns('Event name');

        $artifact  = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getChangeset')->andReturns(null);
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturns($artifact);

        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $worker_event->shouldReceive('getLogger')->andReturns($logger);
        $logger->shouldReceive('info')->atLeast()->once();

        $this->actions_runner->shouldReceive('processAsyncPostCreationActions')->never();

        $async_actions_runner->process($worker_event);
    }
}
