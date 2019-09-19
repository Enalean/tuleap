<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
        $this->logger->log('toto tata');

        $this->assertPattern('/toto tata/', file_get_contents($this->log_file));
    }

    public function itAddsTheLevelToTheLogMessage()
    {
        $this->logger->info('toto tata');
        $this->assertPattern('/\[info\] toto tata/', file_get_contents($this->log_file));
        $this->logger->debug('hej min van');
        $this->assertPattern('/\[debug\] hej min van/', file_get_contents($this->log_file));
        $this->logger->warn('au dodo');
        $this->assertPattern('/\[warning\] au dodo/', file_get_contents($this->log_file));
        $this->logger->error('arrete!');
        $this->assertPattern('/\[error\] arrete!/', file_get_contents($this->log_file));
    }

    public function testErrorAppendsStackTraceIfGivenAnError()
    {
        $message = 'an error occured';
        $exception = new Exception('some error');
        $this->logger->error($message, $exception);

        $this->assertLogContainsStackTrace($exception);
        $this->assertLogContainsErrorMessage($exception, $message);
    }

    public function testWarnAppendsStackTraceIfGivenAnError()
    {
        $message = 'an error occured';
        $exception = new Exception('some error');
        $this->logger->warn($message, $exception);

        $this->assertLogContainsStackTrace($exception);
        $this->assertLogContainsErrorMessage($exception, $message);
    }

    private function assertLogContainsStackTrace($exception)
    {
        $trace = $exception->getTraceAsString();
        $this->assertNotEmpty($trace);
        $quoted_trace = preg_quote("$trace");
        $this->assertPattern("%$quoted_trace%m", file_get_contents($this->log_file));
    }

    private function assertLogContainsErrorMessage($exception, $message)
    {
        $error_message = $exception->getMessage();
        $start_of_trace = substr($exception->getTraceAsString(), 0, 20);
        $this->assertPattern("%$message: $error_message:\n$start_of_trace%m", file_get_contents($this->log_file));
    }
}
