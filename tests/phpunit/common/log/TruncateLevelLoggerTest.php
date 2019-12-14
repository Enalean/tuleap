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
use PHPUnit\Framework\TestCase;

class TruncateLevelLoggerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = \Mockery::spy(\Logger::class);
    }

    public function testItLogEverythingByDefault(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, Logger::DEBUG);

        $this->logger->shouldReceive('debug')->with("debug message")->once();
        $this->logger->shouldReceive('info')->with("info message")->once();
        $this->logger->shouldReceive('warn')->with("warn message", \Mockery::any())->once();
        $this->logger->shouldReceive('error')->with("error message", \Mockery::any())->once();

        $truncate_logger->debug("debug message");
        $truncate_logger->info("info message");
        $truncate_logger->warn("warn message");
        $truncate_logger->error("error message");
    }

    public function testItSkipsDebugWhenLevelIsInfo(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, Logger::INFO);

        $this->logger->shouldReceive('debug')->never();
        $this->logger->shouldReceive('info')->with("info message")->once();
        $this->logger->shouldReceive('warn')->with("warn message", \Mockery::any())->once();
        $this->logger->shouldReceive('error')->with("error message", \Mockery::any())->once();

        $truncate_logger->debug("debug message");
        $truncate_logger->info("info message");
        $truncate_logger->warn("warn message");
        $truncate_logger->error("error message");
    }

    public function testItSkipsDebugAndInfoWhenLevelIsWarn(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, Logger::WARN);

        $this->logger->shouldReceive('debug')->never();
        $this->logger->shouldReceive('info')->never();
        $this->logger->shouldReceive('warn')->with("warn message", \Mockery::any())->once();
        $this->logger->shouldReceive('error')->with("error message", \Mockery::any())->once();

        $truncate_logger->debug("debug message");
        $truncate_logger->info("info message");
        $truncate_logger->warn("warn message");
        $truncate_logger->error("error message");
    }

    public function testItSkipsDebugInfoAndWarnWhenLevelIsWarn(): void
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, Logger::ERROR);

        $this->logger->shouldReceive('debug')->never();
        $this->logger->shouldReceive('info')->never();
        $this->logger->shouldReceive('warn')->never();
        $this->logger->shouldReceive('error')->with("error message", \Mockery::any())->once();

        $truncate_logger->debug("debug message");
        $truncate_logger->info("info message");
        $truncate_logger->warn("warn message");
        $truncate_logger->error("error message");
    }
}
