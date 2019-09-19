<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class TruncateLevelLoggerTest extends TuleapTestCase
{

    private $logger;

    public function setUp()
    {
        parent::setUp();
        $this->logger = mock('Logger');
    }

    public function itLogEverythingByDefault()
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, Logger::DEBUG);

        expect($this->logger)->debug("debug message")->once();
        expect($this->logger)->info("info message")->once();
        expect($this->logger)->warn("warn message", '*')->once();
        expect($this->logger)->error("error message", '*')->once();

        $truncate_logger->debug("debug message");
        $truncate_logger->info("info message");
        $truncate_logger->warn("warn message");
        $truncate_logger->error("error message");
    }

    public function itSkipsDebugWhenLevelIsInfo()
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, Logger::INFO);

        expect($this->logger)->debug()->never();
        expect($this->logger)->info("info message")->once();
        expect($this->logger)->warn("warn message", '*')->once();
        expect($this->logger)->error("error message", '*')->once();

        $truncate_logger->debug("debug message");
        $truncate_logger->info("info message");
        $truncate_logger->warn("warn message");
        $truncate_logger->error("error message");
    }

    public function itSkipsDebugAndInfoWhenLevelIsWarn()
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, Logger::WARN);

        expect($this->logger)->debug()->never();
        expect($this->logger)->info()->never();
        expect($this->logger)->warn("warn message", '*')->once();
        expect($this->logger)->error("error message", '*')->once();

        $truncate_logger->debug("debug message");
        $truncate_logger->info("info message");
        $truncate_logger->warn("warn message");
        $truncate_logger->error("error message");
    }

    public function itSkipsDebugInfoAndWarnWhenLevelIsWarn()
    {
        $truncate_logger = new TruncateLevelLogger($this->logger, Logger::ERROR);

        expect($this->logger)->debug()->never();
        expect($this->logger)->info()->never();
        expect($this->logger)->warn()->never();
        expect($this->logger)->error("error message", '*')->once();

        $truncate_logger->debug("debug message");
        $truncate_logger->info("info message");
        $truncate_logger->warn("warn message");
        $truncate_logger->error("error message");
    }
}
