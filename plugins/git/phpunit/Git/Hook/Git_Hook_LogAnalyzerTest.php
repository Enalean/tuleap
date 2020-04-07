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

require_once __DIR__ . '/../../bootstrap.php';

class Git_Hook_LogAnalyzerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $git_exec;
    private $user;
    private $repository;
    private $logger;

    /** @var Git_Hook_LogAnalyzer */
    private $log_analyzer;


    protected function setUp(): void
    {
        parent::setUp();
        $this->git_exec   = \Mockery::spy(\Git_Exec::class);
        $this->repository = \Mockery::spy(\GitRepository::class);
        $this->user       = \Mockery::spy(\PFUser::class);
        $this->logger     = \Mockery::spy(\Psr\Log\LoggerInterface::class);

        $this->log_analyzer = new Git_Hook_LogAnalyzer($this->git_exec, $this->logger);
    }

    public function testItUpdatesBranch(): void
    {
        $this->git_exec->shouldReceive('revList')->with('d8f1e57', '469eaa9')->once()->andReturns(array('469eaa9'));

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/heads/master');
        $this->assertEquals(Git_Hook_PushDetails::ACTION_UPDATE, $push_details->getType());
        $this->assertEquals(array('469eaa9'), $push_details->getRevisionList());
        $this->assertEquals('refs/heads/master', $push_details->getRefname());
    }

    public function testItCreatesBranch(): void
    {
        $this->git_exec->shouldReceive('revListSinceStart')->with('refs/heads/master', '469eaa9')->once()->andReturns(array('469eaa9'));

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, Git_Hook_LogAnalyzer::FAKE_EMPTY_COMMIT, '469eaa9', 'refs/heads/master');
        $this->assertEquals(Git_Hook_PushDetails::ACTION_CREATE, $push_details->getType());
        $this->assertEquals(array('469eaa9'), $push_details->getRevisionList());
        $this->assertEquals('refs/heads/master', $push_details->getRefname());
    }

    public function testItDeletesBranch(): void
    {
        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, '469eaa9', Git_Hook_LogAnalyzer::FAKE_EMPTY_COMMIT, 'refs/heads/master');
        $this->assertEquals(Git_Hook_PushDetails::ACTION_DELETE, $push_details->getType());
        $this->assertEquals(array(), $push_details->getRevisionList());
        $this->assertEquals('refs/heads/master', $push_details->getRefname());
    }

    public function testItTakesNewRevHashToIdentifyRevTypeOnUpdate(): void
    {
        $this->git_exec->shouldReceive('revList')->andReturns(array('469eaa9'));
        $this->git_exec->shouldReceive('getObjectType')->with('469eaa9')->once()->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/heads/master');
        $this->assertEquals(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT, $push_details->getRevType());
    }

    public function testItTakesNewRevHashToIdentifyRevTypeOnCreate(): void
    {
        $this->git_exec->shouldReceive('revListSinceStart')->andReturns(array('469eaa9'));
        $this->git_exec->shouldReceive('getObjectType')->with('469eaa9')->once()->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, Git_Hook_LogAnalyzer::FAKE_EMPTY_COMMIT, '469eaa9', 'refs/heads/master');
        $this->assertEquals(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT, $push_details->getRevType());
    }

    public function testItTakesOldRevHashToIdentifyRevTypeOnDelete(): void
    {
        $this->git_exec->shouldReceive('getObjectType')->with('469eaa9')->once()->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, '469eaa9', Git_Hook_LogAnalyzer::FAKE_EMPTY_COMMIT, 'refs/heads/master');
        $this->assertEquals(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT, $push_details->getRevType());
    }

    public function testItCommitsOnWorkingBranch(): void
    {
        $this->git_exec->shouldReceive('revList')->andReturns(array());
        $this->git_exec->shouldReceive('getObjectType')->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/heads/master');
        $this->assertEquals(Git_Hook_PushDetails::TYPE_BRANCH, $push_details->getRefnameType());
    }

    public function testItCommitsAnUnannotedTag(): void
    {
        $this->git_exec->shouldReceive('revList')->andReturns(array());
        $this->git_exec->shouldReceive('getObjectType')->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/tags/v1.0');
        $this->assertEquals(Git_Hook_PushDetails::TYPE_UNANNOTATED_TAG, $push_details->getRefnameType());
    }

    public function testItCommitsAnAnnotedTag(): void
    {
        $this->git_exec->shouldReceive('revList')->andReturns(array());
        $this->git_exec->shouldReceive('getObjectType')->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_TAG);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/tags/v1.0');
        $this->assertEquals(Git_Hook_PushDetails::TYPE_ANNOTATED_TAG, $push_details->getRefnameType());
    }

    public function testItCommitsOnRemoteTrackingBranch(): void
    {
        $this->git_exec->shouldReceive('revList')->andReturns(array());
        $this->git_exec->shouldReceive('getObjectType')->andReturns(Git_Hook_PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/remotes/bla');
        $this->assertEquals(Git_Hook_PushDetails::TYPE_TRACKING_BRANCH, $push_details->getRefnameType());
    }

    public function testItGeneratesAnEmptyPushDetailWhenCannotExtactRevList(): void
    {
        $this->git_exec->shouldReceive('revList')->andThrows(new Git_Command_Exception('cmd', array('stuff'), '233'));

        $this->logger->shouldReceive('error')->once();

        $push_details = $this->log_analyzer->getPushDetails($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/remotes/bla');
        $this->assertEquals(Git_Hook_PushDetails::ACTION_ERROR, $push_details->getType());
        $this->assertEquals([], $push_details->getRevisionList());
    }
}
