<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once dirname(__FILE__).'/../../bootstrap.php';

class Git_Hook_ParseLogTest extends TuleapTestCase {

    private $extract_cross_ref;
    private $log_pushes;
    private $parse_log;
    private $logger;

    public function setUp() {
        parent::setUp();

        $this->extract_cross_ref = mock('Git_Hook_ExtractCrossReferences');
        $this->log_pushes        = mock('Git_Hook_LogPushes');
        $this->logger            = mock('Logger');
        $this->parse_log         = new Git_Hook_ParseLog($this->log_pushes, $this->extract_cross_ref, $this->logger);        
    }

    public function itExecutesExtractOnEachCommit() {
        $push_details = stub('Git_Hook_PushDetails')->getRevisionList()->returns(array('469eaa9'));

        expect($this->extract_cross_ref)->execute($push_details, '469eaa9')->once();

        $this->parse_log->execute($push_details);
    }

    public function itDoesntAttemptToExtractWhenBranchIsDeleted() {
        $push_details = stub('Git_Hook_PushDetails')->getRevisionList()->returns(array());

        expect($this->extract_cross_ref)->execute()->never();

        $this->parse_log->execute($push_details);
    }

    public function itExecutesExtractEvenWhenThereAreErrors() {
        $push_details = mock('Git_Hook_PushDetails');
        stub($push_details)->getRevisionList()->returns(array('469eaa9', '0fb0737'));
        stub($push_details)->getRepository()->returns(mock('GitRepository'));

        expect($this->extract_cross_ref)->execute()->count(2);
        expect($this->extract_cross_ref)->execute($push_details, '0fb0737')->at(1);
        
        expect($this->logger)->error()->once();
        stub($this->extract_cross_ref)->execute($push_details, '469eaa9')->throws(new Git_Command_Exception('whatever', array('whatever'), '234'));

        
        $this->parse_log->execute($push_details);
    }
}

class Git_Hook_ParseLog_CountPushesTest extends TuleapTestCase {

    private $extract_cross_ref;
    private $log_pushes;
    private $parse_log;
    private $logger;

    public function setUp() {
        parent::setUp();

        $this->extract_cross_ref = mock('Git_Hook_ExtractCrossReferences');
        $this->log_pushes        = mock('Git_Hook_LogPushes');
        $this->logger            = mock('Logger');
        $this->parse_log         = new Git_Hook_ParseLog($this->log_pushes, $this->extract_cross_ref, $this->logger);        
    }
    
    public function itLogPush() {
        $push_details = stub('Git_Hook_PushDetails')->getRevisionList()->returns(array('469eaa9'));
        expect($this->log_pushes)->executeForRepository($push_details)->once();
        $this->parse_log->execute($push_details);
    }

}