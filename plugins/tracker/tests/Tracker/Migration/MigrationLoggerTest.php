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

require_once __DIR__.'/../../bootstrap.php';

class MigrationLoggerTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->backend_logger   = mock('BackendLogger');
        $this->mail_logger      = mock('Tracker_Migration_MailLogger');
        $this->migration_logger = new Tracker_Migration_MigrationLogger($this->backend_logger, $this->mail_logger);
    }

    public function itLogsErrorsInMailLogger()
    {
        expect($this->mail_logger)->error("bla", '*')->once();

        $this->migration_logger->error("bla");
    }

    public function itLogsErrorsInBackendLogger()
    {
        expect($this->backend_logger)->error("bla", '*')->once();

        $this->migration_logger->error("bla");
    }

    public function itLogsWarningsInMailLogger()
    {
        expect($this->mail_logger)->warn("bla", '*')->once();

        $this->migration_logger->warn("bla");
    }

    public function itLogsWarningsInBackendLogger()
    {
        expect($this->backend_logger)->warn("bla", '*')->once();

        $this->migration_logger->warn("bla");
    }

    public function itDoesntLogsInfoInMailLogger()
    {
        expect($this->mail_logger)->info()->never();

        $this->migration_logger->info("bla");
    }

    public function itLogsInfoInBackendLogger()
    {
        expect($this->backend_logger)->info("bla")->once();

        $this->migration_logger->info("bla");
    }

    public function itDoesntLogsDebugInMailLogger()
    {
        expect($this->mail_logger)->debug()->never();

        $this->migration_logger->debug("bla");
    }

    public function itLogsDebugInBackendLogger()
    {
        expect($this->backend_logger)->debug("bla")->once();

        $this->migration_logger->debug("bla");
    }
}
