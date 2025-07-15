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
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Queue\WorkerEventContent;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class AsynchronousActionsRunnerTest extends TestCase
{
    private MockObject&ActionsRunner $actions_runner;
    private RetrieveArtifact $artifact_factory;

    protected function setUp(): void
    {
        $this->actions_runner   = $this->createMock(ActionsRunner::class);
        $this->artifact_factory = RetrieveArtifactStub::withArtifacts(
            ArtifactTestBuilder::anArtifact(10)->build()
        );
    }

    public function testActionsAreProcessed(): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(15)->build();
        $artifact  = $this->createMock(Artifact::class);
        $artifact->expects($this->once())->method('getChangeset')->willReturn($changeset);
        $this->artifact_factory = RetrieveArtifactStub::withArtifacts($artifact);

        $async_actions_runner = new AsynchronousActionsRunner($this->actions_runner, $this->artifact_factory);
        $logger               = new TestLogger();

        $worker_event = new WorkerEvent(
            $logger,
            new WorkerEventContent(
                'Event name',
                ['artifact_id' => 1, 'changeset_id' => 15, 'send_notifications' => true]
            )
        );

        $configuration = new PostCreationTaskConfiguration(true);
        $this->actions_runner->expects($this->once())->method('processAsyncPostCreationActions')->with(
            $changeset,
            $configuration
        );

        $async_actions_runner->process($worker_event);
    }

    public function testNotWellFormedPayloadAreHandled(): void
    {
        $async_actions_runner = new AsynchronousActionsRunner($this->actions_runner, $this->artifact_factory);

        $logger       = new TestLogger();
        $worker_event = new WorkerEvent($logger, new WorkerEventContent('Event name', []));

        $this->actions_runner->expects($this->never())->method('processAsyncPostCreationActions');

        $async_actions_runner->process($worker_event);
        self::assertTrue($logger->hasWarningRecords());
        self::assertTrue($logger->hasDebugRecords());
    }

    public function testActionsAreNotProcessedWhenArtifactIsNotFound(): void
    {
        $async_actions_runner = new AsynchronousActionsRunner($this->actions_runner, RetrieveArtifactStub::withNoArtifact());

        $logger       = new TestLogger();
        $worker_event = new WorkerEvent(
            $logger,
            new WorkerEventContent('Event name', ['artifact_id' => 1, 'changeset_id' => 1, 'send_notifications' => true])
        );

        $this->actions_runner->expects($this->never())->method('processAsyncPostCreationActions');

        $async_actions_runner->process($worker_event);

        self::assertTrue($logger->hasInfoRecords());
    }

    public function testActionsAreNotProcessedWhenChangesetIsNotFound(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getChangeset')->willReturn(null);
        $this->artifact_factory = RetrieveArtifactStub::withArtifacts($artifact);

        $async_actions_runner = new AsynchronousActionsRunner($this->actions_runner, $this->artifact_factory);

        $logger       = new TestLogger();
        $worker_event = new WorkerEvent(
            $logger,
            new WorkerEventContent('Event name', ['artifact_id' => 1, 'changeset_id' => 1, 'send_notifications' => true])
        );

        $this->actions_runner->expects($this->never())->method('processAsyncPostCreationActions');

        $async_actions_runner->process($worker_event);
        self::assertTrue($logger->hasInfoRecords());
    }
}
