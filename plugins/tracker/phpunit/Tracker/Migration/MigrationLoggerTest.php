<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

use Psr\Log\LogLevel;

final class MigrationLoggerTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $migration_logger;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Psr\Log\LoggerInterface
     */
    private $mail_logger;

    /**
     * @var \Psr\Log\LoggerInterface|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $backend_logger;

    protected function setUp(): void
    {
        $this->backend_logger   = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->mail_logger      = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->migration_logger = new Tracker_Migration_MigrationLogger($this->backend_logger, $this->mail_logger);
    }

    public function testItLogsErrorsInMailLogger(): void
    {
        $this->mail_logger->shouldReceive('error')->with("bla", [])->once();

        $this->migration_logger->error("bla");
    }

    public function testItLogsErrorsInBackendLogger(): void
    {
        $this->backend_logger->shouldReceive('log')->with(LogLevel::ERROR, "bla", [])->once();

        $this->migration_logger->error("bla");
    }

    public function testItLogsWarningsInMailLogger(): void
    {
        $this->mail_logger->shouldReceive('warning')->with("bla", [])->once();

        $this->migration_logger->warning("bla");
    }

    public function testItLogsWarningsInBackendLogger(): void
    {
        $this->backend_logger->shouldReceive('log')->with(LogLevel::WARNING, "bla", [])->once();

        $this->migration_logger->warning("bla");
    }

    public function testItDoesntLogsInfoInMailLogger(): void
    {
        $this->mail_logger->shouldReceive('info')->never();

        $this->migration_logger->info("bla");
    }

    public function testItLogsInfoInBackendLogger(): void
    {
        $this->backend_logger->shouldReceive('log')->with(LogLevel::INFO, "bla", [])->once();

        $this->migration_logger->info("bla");
    }

    public function testItDoesntLogsDebugInMailLogger(): void
    {
        $this->mail_logger->shouldReceive('debug', [])->never();

        $this->migration_logger->debug("bla");
    }

    public function testItLogsDebugInBackendLogger(): void
    {
        $this->backend_logger->shouldReceive('log')->with(LogLevel::DEBUG, "bla", [])->once();

        $this->migration_logger->debug("bla");
    }
}
