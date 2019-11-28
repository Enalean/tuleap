<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
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

require_once __DIR__.'/../../bootstrap.php';

class Git_Hook_LogAnalyzerTest extends TuleapTestCase
{

    private $git_exec;
    private $user;
    private $repository;
    private $logger;

    /** @var Git_Hook_LogAnalyzer */
    private $log_analyzer;


    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->git_exec   = \Mockery::spy(\Git_Exec::class);
        $this->repository = \Mockery::spy(\GitRepository::class);
        $this->user       = \Mockery::spy(\PFUser::class);
        $this->logger     = \Mockery::spy(\Logger::class);

        $this->log_analyzer = new Git_Hook_LogAnalyzer($this->git_exec, $this->logger);
    }

    public function itUpdatesBranch()
    {
        $this->git_exec->shouldReceive('revList')->with('d8f1e57', '469eaa9')->once()->andReturns(array('469eaa9'));

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/heads/master');
        $this->assertEqual($push_details->getType(), Git_Hook_PushDetails::ACTION_UPDATE);
        $this->assertEqual($push_details->getRevisionList(), array('469eaa9'));
        $this->assertEqual($push_details->getRefname(), 'refs/heads/master');
    }

    public function itCreatesBranch()
    {
        $this->git_exec->shouldReceive('revListSinceStart')->with('refs/heads/master', '469eaa9')->once()->andReturns(array('469eaa9'));

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, Git_Hook_LogAnalyzer::FAKE_EMPTY_COMMIT, '469eaa9', 'refs/heads/master');
        $this->assertEqual($push_details->getType(), Git_Hook_PushDetails::ACTION_CREATE);
        $this->assertEqual($push_details->getRevisionList(), array('469eaa9'));
        $this->assertEqual($push_details->getRefname(), 'refs/heads/master');
    }

    public function itDeletesBranch()
    {
        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, '469eaa9', Git_Hook_LogAnalyzer::FAKE_EMPTY_COMMIT, 'refs/heads/master');
        $this->assertEqual($push_details->getType(), Git_Hook_PushDetails::ACTION_DELETE);
        $this->assertEqual($push_details->getRevisionList(), array());
        $this->assertEqual($push_details->getRefname(), 'refs/heads/master');
    }

    public function itTakesNewRevHashToIdentifyRevTypeOnUpdate()
    {
        $this->git_exec->shouldReceive('revList')->andReturns(array('469eaa9'));
        $this->git_exec->shouldReceive('getObjectType')->with('469eaa9')->once()->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/heads/master');
        $this->assertEqual($push_details->getRevType(), Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);
    }

    public function itTakesNewRevHashToIdentifyRevTypeOnCreate()
    {
        $this->git_exec->shouldReceive('revListSinceStart')->andReturns(array('469eaa9'));
        $this->git_exec->shouldReceive('getObjectType')->with('469eaa9')->once()->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, Git_Hook_LogAnalyzer::FAKE_EMPTY_COMMIT, '469eaa9', 'refs/heads/master');
        $this->assertEqual($push_details->getRevType(), Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);
    }

    public function itTakesOldRevHashToIdentifyRevTypeOnDelete()
    {
        $this->git_exec->shouldReceive('getObjectType')->with('469eaa9')->once()->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, '469eaa9', Git_Hook_LogAnalyzer::FAKE_EMPTY_COMMIT, 'refs/heads/master');
        $this->assertEqual($push_details->getRevType(), Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);
    }

    public function itCommitsOnWorkingBranch()
    {
        $this->git_exec->shouldReceive('revList')->andReturns(array());
        $this->git_exec->shouldReceive('getObjectType')->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/heads/master');
        $this->assertEqual($push_details->getRefnameType(), Git_Hook_PushDetails::TYPE_BRANCH);
    }

    public function itCommitsAnUnannotedTag()
    {
        $this->git_exec->shouldReceive('revList')->andReturns(array());
        $this->git_exec->shouldReceive('getObjectType')->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/tags/v1.0');
        $this->assertEqual($push_details->getRefnameType(), Git_Hook_PushDetails::TYPE_UNANNOTATED_TAG);
    }

    public function itCommitsAnAnnotedTag()
    {
        $this->git_exec->shouldReceive('revList')->andReturns(array());
        $this->git_exec->shouldReceive('getObjectType')->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_TAG);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/tags/v1.0');
        $this->assertEqual($push_details->getRefnameType(), Git_Hook_PushDetails::TYPE_ANNOTATED_TAG);
    }

    public function itCommitsOnRemoteTrackingBranch()
    {
        $this->git_exec->shouldReceive('revList')->andReturns(array());
        $this->git_exec->shouldReceive('getObjectType')->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/remotes/bla');
        $this->assertEqual($push_details->getRefnameType(), Git_Hook_PushDetails::TYPE_TRACKING_BRANCH);
    }

    public function itGeneratesAnEmptyPushDetailWhenCannotExtactRevList()
    {
        $this->git_exec->shouldReceive('revList')->andThrows(new Git_Command_Exception('cmd', array('stuff'), '233'));

        $this->logger->shouldReceive('error')->once();

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/remotes/bla');
        $this->assertEqual($push_details->getType(), Git_Hook_PushDetails::ACTION_ERROR);
        $this->assertEqual($push_details->getRevisionList(), array());
    }
}
