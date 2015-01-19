<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_XMLImporter_CopyArtifactInformationsAggregatorTest extends TuleapTestCase {

    /** @var Tracker_XMLImporter_CopyArtifactInformationsAggregator */
    private $logger;

    /** @var BackendLogger */
    private $backend_logger;

    public function setUp() {
        $this->backend_logger = mock("BackendLogger");
        $this->logger         = new Tracker_XMLImporter_CopyArtifactInformationsAggregator($this->backend_logger);
    }

    public function itDoesNotContainsAnyMessageIfThereAreNone() {
        $this->assertEqual($this->logger->getAllLogs(), array());
    }

    public function itContainsAllTheLoggedMessages() {
        $this->logger->error("this is an error");
        $this->logger->warn("this is a warning");

        $expected_logs = array(
            "[error] this is an error",
            "[warning] this is a warning"
        );
        $this->assertEqual($this->logger->getAllLogs(), $expected_logs);
    }

    public function itAlsoLogsUsingTheBackendLogger() {
        expect($this->backend_logger)->log()->once();

        $this->logger->error("this is an error");
    }

    public function itOnlyLogsErrorsAndWarningsInTheLogStack() {
        expect($this->backend_logger)->log()->count(4);

        $this->logger->error("this is an error");
        $this->logger->warn("this is a warning");
        $this->logger->info("this is an info");
        $this->logger->debug("this is a debug");

        $expected_logs = array(
            "[error] this is an error",
            "[warning] this is a warning"
        );
        $this->assertEqual($this->logger->getAllLogs(), $expected_logs);

    }
}
