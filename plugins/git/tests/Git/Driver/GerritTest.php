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

require_once dirname(__FILE__).'/../../../include/constants.php';
require_once dirname(__FILE__).'/../../builders/aGitRepository.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit.class.php';
require_once 'common/include/Config.class.php';
class Git_Driver_Gerrit_createTest extends TuleapTestCase {

    protected $host = 'tuleap.example.com';

    /**
     * @var GitRepository
     */
    protected $repository;
    
    /** @var Git_RemoteServer_GerritServer */
    protected $gerrit_server;

    /**
     * @var RemoteSshCommand
     */
    protected $ssh;

    public function setUp() {
        parent::setUp();
        Config::store();
        Config::set('sys_default_domain', $this->host);

        $project = stub('Project')->getUnixName()->returns('firefox');

        $this->repository = aGitRepository()
            ->withProject($project)
            ->withNamespace('jean-claude')
            ->withName('dusse')
            ->build();

        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');
        
        $this->ssh    = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $this->driver = new Git_Driver_Gerrit($this->ssh);
    }

    public function tearDown() {
        parent::tearDown();
        Config::restore();
    }

    public function itExecutesTheCreateCommandOnTheGerritServer() {
        expect($this->ssh)->execute($this->gerrit_server, "gerrit create-project tuleap.example.com-firefox/jean-claude/dusse")->once();
        $this->driver->createProject($this->gerrit_server, $this->repository);
    }
    
    public function itReturnsTheNameOfTheCreatedProject() {
        $project_name = $this->driver->createProject($this->gerrit_server, $this->repository);
        $this->assertEqual($project_name, $this->host."-firefox/jean-claude/dusse");
    }
    
    public function _itCallsTheRealThing() {
        $r = new GitRepository();
        $r->setName('dusse');
        $r->setNamespace('jean_claude');
        //$p = new Project(array('unix_group_name' => 'LesBronzes', 'group_id' => 50));
        $p = stub('Project' )->getUnixName()->returns('LesBronzes');
        $r->setProject($p);

        $driver = new Git_Driver_Gerrit(new Git_Driver_Gerrit_RemoteSSHCommand());
        $driver->createProject($r);
    }
    
    public function itRaisesAGerritDriverException() {
        $std_err = 'fatal: project "someproject" exists';
        $command = "gerrit create-project tuleap.example.com-firefox/jean-claude/dusse";
        stub($this->ssh)->execute()->throws(new RemoteSSHCommandFailure(Git_Driver_Gerrit::EXIT_CODE,'',$std_err));
        try {
            $this->driver->createProject($this->gerrit_server, $this->repository);
            $this->fail('An exception was expected');
        } catch (Git_Driver_Gerrit_Exception $e) {
            $this->assertEqual($e->getMessage(),"Command: $command".PHP_EOL."Error: $std_err");
        }
    }
    
    public function itDoesntTransformExcetpionsThatArentRelatedToGerrit() {
        $std_err = 'some gerrit exception';
        $this->expectException('RemoteSSHCommandFailure');
        stub($this->ssh)->execute()->throws(new RemoteSSHCommandFailure(255,'',$std_err));
        $this->driver->createProject($this->gerrit_server, $this->repository);
    }
}
?>
