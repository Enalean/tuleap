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

require_once __DIR__.'/../bootstrap.php';

class WorkflowBackendLogger_StartEndTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->backend_logger = mock('BackendLogger');
    }

    public function itLogsMethod() {
        $logger = new WorkflowBackendLogger($this->backend_logger);
        expect($this->backend_logger)->log('[WF] ┌ Start theMethod()', Feedback::DEBUG)->once();
        $logger->start('theMethod');
    }

    public function itLogsOptionalArgument() {
        $logger = new WorkflowBackendLogger($this->backend_logger);
        expect($this->backend_logger)->log('[WF] ┌ Start theMethod(1, a)', Feedback::DEBUG)->once();
        $logger->start('theMethod', 1, 'a');
    }

    public function itWorksAlsoWorksForEndMethod() {
        $logger = new WorkflowBackendLogger($this->backend_logger);
        expect($this->backend_logger)->log('[WF] └ End theMethod(1, a)', Feedback::DEBUG)->once();
        $logger->end('theMethod', 1, 'a');
    }

    public function itIncrementsOnStartAndDecrementsOnEnd() {
        $logger = new WorkflowBackendLogger($this->backend_logger);
        expect($this->backend_logger)->log('[WF] ┌ Start method()', Feedback::DEBUG)->at(0);
        expect($this->backend_logger)->log('[WF] │ ┌ Start subMethod()', Feedback::DEBUG)->at(1);
        expect($this->backend_logger)->log('[WF] │ └ End subMethod()', Feedback::DEBUG)->at(2);
        expect($this->backend_logger)->log('[WF] └ End method()', Feedback::DEBUG)->at(3);
        $logger->start('method');
        $logger->start('subMethod');
        $logger->end('subMethod');
        $logger->end('method');
    }

    public function itIncludesTheFingerprint() {
        $logger = new WorkflowBackendLogger($this->backend_logger);
        expect($this->backend_logger)->log('[WF] [12345] toto', Feedback::DEBUG)->once();
        $logger->defineFingerprint(12345);
        $logger->debug('toto');
    }

    public function itDoesNotChangeTheFingerprint() {
        $logger = new WorkflowBackendLogger($this->backend_logger);
        expect($this->backend_logger)->log('[WF] [12345] toto', Feedback::DEBUG)->once();
        $logger->defineFingerprint(12345);
        $logger->defineFingerprint(67890);
        $logger->debug('toto');
    }
}
