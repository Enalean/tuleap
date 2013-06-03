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

class Git_Hook_LogAnalyzerTest extends TuleapTestCase {

    private $git_exec;
    private $user;
    private $repository;

    /** @var Git_Hook_LogAnalyzer */
    private $log_analyzer;
    

    public function setUp() {
        parent::setUp();
        $this->git_exec   = mock('Git_Exec');
        $this->repository = mock('GitRepository');
        $this->user       = mock('PFUser');

        $this->log_analyzer = new Git_Hook_LogAnalyzer($this->git_exec);
    }

    public function itUpdatesBranch() {
        expect($this->git_exec)->revList('d8f1e57', '469eaa9')->once();
        stub($this->git_exec)->revList()->returns(array('469eaa9'));

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/heads/master');
        $this->assertEqual($push_details->getType(), Git_Hook_PushDetails::ACTION_UPDATE);
        $this->assertEqual($push_details->getRevisionList(), array('469eaa9'));
        $this->assertEqual($push_details->getRefname(), 'refs/heads/master');
    }

    public function itCreatesBranch() {
        expect($this->git_exec)->revListSinceStart('refs/heads/master', '469eaa9')->once();
        stub($this->git_exec)->revListSinceStart()->returns(array('469eaa9'));

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, '0000000000000000000000000000000000000000', '469eaa9', 'refs/heads/master');
        $this->assertEqual($push_details->getType(), Git_Hook_PushDetails::ACTION_CREATE);
        $this->assertEqual($push_details->getRevisionList(), array('469eaa9'));
        $this->assertEqual($push_details->getRefname(), 'refs/heads/master');
    }

    public function itDeletesBranch() {
        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, '469eaa9', '0000000000000000000000000000000000000000', 'refs/heads/master');
        $this->assertEqual($push_details->getType(), Git_Hook_PushDetails::ACTION_DELETE);
        $this->assertEqual($push_details->getRevisionList(), array());
        $this->assertEqual($push_details->getRefname(), 'refs/heads/master');
    }

    public function itTakesNewRevHashToIdentifyRevTypeOnUpdate() {
        stub($this->git_exec)->revList()->returns(array('469eaa9'));
        expect($this->git_exec)->getObjectType('469eaa9')->once();
        stub($this->git_exec)->getObjectType()->returns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/heads/master');
        $this->assertEqual($push_details->getRevType(), Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);
    }

    public function itTakesNewRevHashToIdentifyRevTypeOnCreate() {
        stub($this->git_exec)->revListSinceStart()->returns(array('469eaa9'));
        expect($this->git_exec)->getObjectType('469eaa9')->once();
        stub($this->git_exec)->getObjectType()->returns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, '0000000000000000000000000000000000000000', '469eaa9', 'refs/heads/master');
        $this->assertEqual($push_details->getRevType(), Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);
    }

    public function itTakesOldRevHashToIdentifyRevTypeOnDelete() {
        expect($this->git_exec)->getObjectType('469eaa9')->once();
        stub($this->git_exec)->getObjectType()->returns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, '469eaa9', '0000000000000000000000000000000000000000', 'refs/heads/master');
        $this->assertEqual($push_details->getRevType(), Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);
    }

    public function itCommitsOnWorkingBranch() {
        stub($this->git_exec)->revList()->returns(array());
        stub($this->git_exec)->getObjectType()->returns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/heads/master');
        $this->assertEqual($push_details->getRefnameType(), Git_Hook_PushDetails::TYPE_BRANCH);
    }

    public function itCommitsAnUnannotedTag() {
        stub($this->git_exec)->revList()->returns(array());
        stub($this->git_exec)->getObjectType()->returns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/tags/v1.0');
        $this->assertEqual($push_details->getRefnameType(), Git_Hook_PushDetails::TYPE_UNANNOTATED_TAG);
    }

    public function itCommitsAnAnnotedTag() {
        stub($this->git_exec)->revList()->returns(array());
        stub($this->git_exec)->getObjectType()->returns(Git_Hook_PushDetails::OBJECT_TYPE_TAG);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/tags/v1.0');
        $this->assertEqual($push_details->getRefnameType(), Git_Hook_PushDetails::TYPE_ANNOTATED_TAG);
    }

    public function itCommitsOnRemoteTrackingBranch() {
        stub($this->git_exec)->revList()->returns(array());
        stub($this->git_exec)->getObjectType()->returns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/remotes/bla');
        $this->assertEqual($push_details->getRefnameType(), Git_Hook_PushDetails::TYPE_TRACKING_BRANCH);
    }
}

?>
