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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\LogLevel;

class TruncateLevelLoggerTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Psr\Log\LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
    }

    public function testItLogEverythingByDefault(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, LogLevel::DEBUG);

        $this->logger->shouldReceive('debug')->with("debug message", [])->once();
        $this->logger->shouldReceive('info')->with("info message", [])->once();
        $this->logger->shouldReceive('warning')->with("warn message", [])->once();
        $this->logger->shouldReceive('error')->with("error message", [])->once();

        $truncate_logger->debug("debug message");
        $truncate_logger->info("info message");
        $truncate_logger->warning("warn message");
        $truncate_logger->error("error message");
    }

    public function testItSkipsDebugWhenLevelIsInfo(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, LogLevel::INFO);

        $this->logger->shouldReceive('debug')->never();
        $this->logger->shouldReceive('info')->with("info message", [])->once();
        $this->logger->shouldReceive('warning')->with("warn message", [])->once();
        $this->logger->shouldReceive('error')->with("error message", [])->once();

        $truncate_logger->debug("debug message");
        $truncate_logger->info("info message");
        $truncate_logger->warning("warn message");
        $truncate_logger->error("error message");
    }

    public function testItSkipsDebugAndInfoWhenLevelIsWarning(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, LogLevel::WARNING);

        $this->logger->shouldReceive('debug')->never();
        $this->logger->shouldReceive('info')->never();
        $this->logger->shouldReceive('warning')->with("warn message", [])->once();
        $this->logger->shouldReceive('error')->with("error message", [])->once();

        $truncate_logger->debug("debug message");
        $truncate_logger->info("info message");
        $truncate_logger->warning("warn message");
        $truncate_logger->error("error message");
    }

    public function testItSkipsDebugInfoAndWarnWhenLevelIsWarn(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, LogLevel::ERROR);

        $this->logger->shouldReceive('debug')->never();
        $this->logger->shouldReceive('info')->never();
        $this->logger->shouldReceive('warn')->never();
        $this->logger->shouldReceive('error')->with("error message", [])->once();

        $truncate_logger->debug("debug message");
        $truncate_logger->info("info message");
        $truncate_logger->warning("warn message");
        $truncate_logger->error("error message");
    }

    public function testFallbackToWarningLevelWhenLevelIsUnknown(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, 'warn');

        $this->logger->shouldReceive('debug')->never();
        $this->logger->shouldReceive('info')->never();
        $this->logger->shouldReceive('warning')->with('warn message', [])->once();
        $this->logger->shouldReceive('error')->with('error message', [])->once();

        $truncate_logger->debug('debug message');
        $truncate_logger->info('info message');
        $truncate_logger->warning('warn message');
        $truncate_logger->error('error message');
    }
}
