<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WorkflowBackendLoggerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private LoggerInterface&MockObject $backend_logger;

    protected function setUp(): void
    {
        $this->backend_logger = $this->createMock(\Psr\Log\LoggerInterface::class);
    }

    public function testLogsMethod(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::DEBUG);
        $this->backend_logger->expects($this->once())->method('debug')->with('[WF] ┌ Start theMethod()', []);
        $logger->start('theMethod');
    }

    public function testLogsOptionalArgument(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::DEBUG);
        $this->backend_logger->expects($this->once())->method('debug')->with('[WF] ┌ Start theMethod(1, a)', []);
        $logger->start('theMethod', 1, 'a');
    }

    public function testWorksAlsoWorksForEndMethod(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::DEBUG);
        $this->backend_logger->expects($this->once())->method('debug')->with('[WF] └ End theMethod(1, a)', []);
        $logger->end('theMethod', 1, 'a');
    }

    public function testIncrementsOnStartAndDecrementsOnEnd(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::DEBUG);
        $this->backend_logger->expects($this->exactly(4))->method('debug')
            ->willReturnCallback(static fn (string $message) => match ($message) {
                '[WF] ┌ Start method()' => true,
                '[WF] │ ┌ Start subMethod()' => true,
                '[WF] │ └ End subMethod()' => true,
                '[WF] └ End method()' => true,
            });
        $logger->start('method');
        $logger->start('subMethod');
        $logger->end('subMethod');
        $logger->end('method');
    }

    public function testIncludesTheFingerprint(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::DEBUG);
        $this->backend_logger->expects($this->once())->method('debug')->with('[WF] [12345] toto', []);
        $logger->defineFingerprint(12345);
        $logger->debug('toto');
    }

    public function testDoesNotChangeTheFingerprint(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::DEBUG);
        $this->backend_logger->expects($this->once())->method('debug')->with('[WF] [12345] toto', []);
        $logger->defineFingerprint(12345);
        $logger->defineFingerprint(67890);
        $logger->debug('toto');
    }

    public function testLogLevelIsRespected(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::INFO);
        $this->backend_logger->expects($this->never())->method('debug');
        $logger->start('theMethod');
    }
}
