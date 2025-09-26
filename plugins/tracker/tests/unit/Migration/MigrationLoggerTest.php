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

use ColinODell\PsrTestLogger\TestLogger;
use Psr\Log\LoggerInterface;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MigrationLoggerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private LoggerInterface $migration_logger;
    private TestLogger $mail_logger;
    private TestLogger $backend_logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->backend_logger   = new TestLogger();
        $this->mail_logger      = new TestLogger();
        $this->migration_logger = new Tracker_Migration_MigrationLogger($this->backend_logger, $this->mail_logger);
    }

    public function testItLogsErrorsInMailLogger(): void
    {
        $this->migration_logger->error('bla');

        self::assertTrue($this->mail_logger->hasError('bla'));
    }

    public function testItLogsErrorsInBackendLogger(): void
    {
        $this->migration_logger->error('bla');

        self::assertTrue($this->backend_logger->hasError('bla'));
    }

    public function testItLogsWarningsInMailLogger(): void
    {
        $this->migration_logger->warning('bla');

        self::assertTrue($this->mail_logger->hasWarning('bla'));
    }

    public function testItLogsWarningsInBackendLogger(): void
    {
        $this->migration_logger->warning('bla');

        self::assertTrue($this->backend_logger->hasWarning('bla'));
    }

    public function testItDoesntLogsInfoInMailLogger(): void
    {
        $this->migration_logger->info('bla');

        self::assertFalse($this->mail_logger->hasInfoRecords());
    }

    public function testItLogsInfoInBackendLogger(): void
    {
        $this->migration_logger->info('bla');

        self::assertTrue($this->backend_logger->hasInfo('bla'));
    }

    public function testItDoesntLogsDebugInMailLogger(): void
    {
        $this->migration_logger->debug('bla');

        self::assertFalse($this->mail_logger->hasDebugRecords());
    }

    public function testItLogsDebugInBackendLogger(): void
    {
        $this->migration_logger->debug('bla');

        self::assertTrue($this->backend_logger->hasDebug('bla'));
    }
}
