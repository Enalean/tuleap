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

use ColinODell\PsrTestLogger\TestLogger;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Queue\WorkerEventContent;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class AsynchronousJiraRunnerTest extends TestCase
{
    private JiraRunner&MockObject $jira_runner;
    private PendingJiraImportDao&MockObject $dao;
    private PendingJiraImportBuilder&MockObject $builder;
    private AsynchronousJiraRunner $async_runner;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->jira_runner = $this->createMock(JiraRunner::class);
        $this->dao         = $this->createMock(PendingJiraImportDao::class);
        $this->builder     = $this->createMock(PendingJiraImportBuilder::class);

        $this->logger = new TestLogger();

        $this->async_runner = new AsynchronousJiraRunner($this->jira_runner, $this->dao, $this->builder);
    }

    public function testItDoesNotProcessAnythingIfPayloadDoesNotContainsSufficientInformation(): void
    {
        $event = new WorkerEvent(
            $this->logger,
            new WorkerEventContent(
                AsynchronousJiraRunner::TOPIC,
                []
            )
        );

        $this->async_runner->process($event);
        self::assertTrue($this->logger->hasErrorThatContains('The payload for tuleap.tracker.creation.jira seems to be malformed'));
        self::assertTrue($this->logger->hasDebugThatContains("Malformed payload for tuleap.tracker.creation.jira: array (\n)"));
    }

    public function testItDoesNotProcessAnythingIfPendingJiraImportCannotBeFound(): void
    {
        $event = new WorkerEvent(
            $this->logger,
            new WorkerEventContent(
                AsynchronousJiraRunner::TOPIC,
                ['pending_jira_import_id' => 123]
            )
        );

        $this->dao->expects(self::once())->method('searchById')->with(123)->willReturn(false);

        $this->async_runner->process($event);
        self::assertTrue($this->logger->hasErrorThatContains('Not able to process an event tuleap.tracker.creation.jira, the pending jira import #123 can not be found.'));
    }

    public function testItDoesNotProcessAnythingIfPendingJiraImportCannotBeBuilt(): void
    {
        $event = new WorkerEvent(
            $this->logger,
            new WorkerEventContent(
                AsynchronousJiraRunner::TOPIC,
                ['pending_jira_import_id' => 123]
            )
        );

        $this->dao->expects(self::once())->method('searchById')->with(123)->willReturn(['data_from_db']);

        $this->builder->expects(self::once())->method('buildFromRow')->with(['data_from_db'])
            ->willThrowException(new UnableToBuildPendingJiraImportException('Project is not active'));

        $this->async_runner->process($event);
        self::assertTrue($this->logger->hasErrorThatContains('Not able to process an event tuleap.tracker.creation.jira, the pending jira import #123 can not be built: Project is not active'));
    }

    public function testItAsksToTheRunnerToProcessTheImport(): void
    {
        $event = new WorkerEvent(
            $this->logger,
            new WorkerEventContent(
                AsynchronousJiraRunner::TOPIC,
                ['pending_jira_import_id' => 123]
            )
        );

        $this->dao->expects(self::once())->method('searchById')->with(123)->willReturn(['data_from_db']);

        $import = $this->createStub(PendingJiraImport::class);
        $this->builder->expects(self::once())->method('buildFromRow')->with(['data_from_db'])->willReturn($import);

        $this->jira_runner->expects(self::once())->method('processAsyncJiraImport')->with($import);

        $this->async_runner->process($event);
    }
}
