<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
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

namespace Tuleap\Log;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use TruncateLevelLogger;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TruncateLevelLoggerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testItLogEverythingByDefault(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, LogLevel::DEBUG);

        $this->logger->expects($this->once())->method('debug')->with('debug message', []);
        $this->logger->expects($this->once())->method('info')->with('info message', []);
        $this->logger->expects($this->once())->method('warning')->with('warn message', []);
        $this->logger->expects($this->once())->method('error')->with('error message', []);

        $truncate_logger->debug('debug message');
        $truncate_logger->info('info message');
        $truncate_logger->warning('warn message');
        $truncate_logger->error('error message');
    }

    public function testItSkipsDebugWhenLevelIsInfo(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, LogLevel::INFO);

        $this->logger->expects($this->never())->method('debug');
        $this->logger->expects($this->once())->method('info')->with('info message', []);
        $this->logger->expects($this->once())->method('warning')->with('warn message', []);
        $this->logger->expects($this->once())->method('error')->with('error message', []);

        $truncate_logger->debug('debug message');
        $truncate_logger->info('info message');
        $truncate_logger->warning('warn message');
        $truncate_logger->error('error message');
    }

    public function testItSkipsDebugAndInfoWhenLevelIsWarning(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, LogLevel::WARNING);

        $this->logger->expects($this->never())->method('debug');
        $this->logger->expects($this->never())->method('info');
        $this->logger->expects($this->once())->method('warning')->with('warn message', []);
        $this->logger->expects($this->once())->method('error')->with('error message', []);

        $truncate_logger->debug('debug message');
        $truncate_logger->info('info message');
        $truncate_logger->warning('warn message');
        $truncate_logger->error('error message');
    }

    public function testItSkipsDebugInfoAndWarnWhenLevelIsWarn(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, LogLevel::ERROR);

        $this->logger->expects($this->never())->method('debug');
        $this->logger->expects($this->never())->method('info');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->once())->method('error')->with('error message', []);

        $truncate_logger->debug('debug message');
        $truncate_logger->info('info message');
        $truncate_logger->warning('warn message');
        $truncate_logger->error('error message');
    }

    public function testFallbackToWarningLevelWhenLevelIsUnknown(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, 'warn');

        $this->logger->expects($this->never())->method('debug');
        $this->logger->expects($this->never())->method('info');
        $this->logger->expects($this->once())->method('warning')->with('warn message', []);
        $this->logger->expects($this->once())->method('error')->with('error message', []);

        $truncate_logger->debug('debug message');
        $truncate_logger->info('info message');
        $truncate_logger->warning('warn message');
        $truncate_logger->error('error message');
    }
}
