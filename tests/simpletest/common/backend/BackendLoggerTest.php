<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

class BackendLoggerTest extends TuleapTestCase
{

    private $log_file;
    /** @var BackendLogger */
    private $logger;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::set('codendi_log', '/tmp');
        $this->log_file         = tempnam(ForgeConfig::get('codendi_log'), 'codendi_syslog');
        $this->logger           = new BackendLogger($this->log_file);
    }

    public function itLogsToTheSyslog()
    {
        $this->logger->log(\Psr\Log\LogLevel::INFO, 'toto tata');

        $this->assertPattern('/toto tata/', file_get_contents($this->log_file));
    }

    public function itAddsTheLevelToTheLogMessage()
    {
        $this->logger->info('toto tata');
        $this->assertPattern('/\[info\] toto tata/', file_get_contents($this->log_file));
        $this->logger->debug('hej min van');
        $this->assertPattern('/\[debug\] hej min van/', file_get_contents($this->log_file));
        $this->logger->warning('au dodo');
        $this->assertPattern('/\[warning\] au dodo/', file_get_contents($this->log_file));
        $this->logger->error('arrete!');
        $this->assertPattern('/\[error\] arrete!/', file_get_contents($this->log_file));
    }
}
