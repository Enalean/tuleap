<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class WorkflowBackendLogger_StartEndTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->logger = partial_mock('WorkflowBackendLogger', array('log'));
    }

    public function itLogsMethod() {
        expect($this->logger)->log('Start theMethod()', 'debug')->once();
        $this->logger->start('theMethod');
    }

    public function itLogsOptionalArgument() {
        expect($this->logger)->log('Start theMethod(1, a)', 'debug')->once();
        $this->logger->start('theMethod', 1, 'a');
    }

    public function itWorksAlsoWorksForEndMethod() {
        expect($this->logger)->log('End theMethod(1, a)', 'debug')->once();
        $this->logger->end('theMethod', 1, 'a');
    }
}
