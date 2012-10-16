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

require_once dirname(__FILE__) .'/../../include/constants.php';
require_once GIT_BASE_DIR .'/events/SystemEvent_GIT_GERRIT_MIGRATION.class.php';
require_once 'common/log/Logger.class.php';

abstract class SystemEvent_GIT_GERRIT_MIGRATION_BaseTest extends TuleapTestCase {

    /**
     * @var SystemEvent_GIT_GERRIT_MIGRATION
     */
    protected $event;
    protected $repository_id    = 123;
    protected $remote_server_id = 12;
    protected $driver;
    protected $repository;
    protected $gerrit_server;
    protected $server_factory;
    
    public function setUp() {
        parent::setUp();
        
        $this->dao = mock('GitDao');
        
        $this->driver = mock('Git_Driver_Gerrit');
        
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');
        $this->repository = mock('GitRepository');
        
        $this->server_factory = mock('Git_RemoteServer_GerritServerFactory');

        $factory = mock('GitRepositoryFactory');
        stub($factory)->getRepositoryById($this->repository_id)->returns($this->repository);
        
        $id= $type= $parameters= $priority= $status= $create_date= $process_date= $end_date= $log = 0;
        $this->event = TestHelper::getPartialMock('SystemEvent_GIT_GERRIT_MIGRATION', array('done', 'error'), 
                                                  array($id, $type, $parameters, $priority, $status, $create_date, $process_date, $end_date, $log));
        $this->event->setParameters("$this->repository_id::$this->remote_server_id");
        $this->logger = mock('Logger');
        $this->event->injectDependencies($this->dao, $this->driver, $factory, $this->server_factory, $this->logger);
        
    }
}

class SystemEvent_GIT_GERRIT_MIGRATION_BackendTest extends SystemEvent_GIT_GERRIT_MIGRATION_BaseTest  {
    
    public function itSwitchesTheBackendToGerrit() {
        stub($this->server_factory)->getServer($this->repository)->returns($this->gerrit_server);
        expect($this->dao)->switchToGerrit($this->repository_id, $this->remote_server_id)->once();
        $this->event->process();
    }
    public function itCallsDoneAndReturnsTrue() {
        stub($this->server_factory)->getServer($this->repository)->returns($this->gerrit_server);
        expect($this->event)->done()->once();
        $this->assertTrue($this->event->process());
    }
    
    public function itInformsAboutMigrationSuccess() {
        stub($this->server_factory)->getServer($this->repository)->returns($this->gerrit_server);
        $remote_project = 'tuleap.net-Firefox/mobile';
        $gerrit_host  = 'gerrit.instance.net';
        stub($this->driver)->createProject()->returns($remote_project);
        stub($this->gerrit_server)->getHost()->returns($gerrit_host);
        expect($this->event)->done("Created project $remote_project on $gerrit_host")->once();
        $this->event->process();
    }
    
    public function itInformsAboutProjectInitialization() {
        stub($this->server_factory)->getServer($this->repository)->returns($this->gerrit_server);
        $remote_project = 'tuleap.net-Firefox/mobile';
        stub($this->driver)->createProject()->returns($remote_project);
        expect($this->logger)->info("Gerrit: Project $remote_project successfully initialized")->once();
        $this->event->process();
    }
    public function itInformsAboutGroupCreation() {
    }
    public function itInformsAboutPermissionsConfiguration() {
    }
    
    public function itInformsAboutAnyGenericFailure() {
        $e = new Exception("failure detail");
        stub($this->driver)->createProject()->throws($e);
        expect($this->event)->error("failure detail")->once();
        expect($this->logger)->error("An error occured while processing event: ".$this->event->verbalizeParameters(null), $e)->once();
        $this->event->process();
    }
    
    public function itInformsAboutAnyGerritRelatedFailureByAddingAPrefix() {
        $e = new Git_Driver_Gerrit_Exception("failure detail");
        stub($this->driver)->createProject()->throws($e);
        expect($this->event)->error("gerrit: failure detail")->once();
        expect($this->logger)->error("Gerrit failure: ".$this->event->verbalizeParameters(null), $e)->once();
        $this->event->process();
    }
    
    public function itInformsAboutAnyServerFactoryFailure() {
        $e = new Exception("failure detail");
        stub($this->server_factory)->getServer()->throws($e);
        expect($this->event)->error("failure detail")->once();
        expect($this->logger)->error("An error occured while processing event: ".$this->event->verbalizeParameters(null), $e)->once();
        $this->event->process();
    }
    
}

class SystemEvent_GIT_GERRIT_MIGRATION_CallsToGerritTest extends SystemEvent_GIT_GERRIT_MIGRATION_BaseTest  {
    
    public function itCreatesAProject() { 
        stub($this->server_factory)->getServer($this->repository)->returns($this->gerrit_server);
        //ssh gerrit gerrit create tuleap.net-Firefox/all/mobile
        expect($this->driver)->createProject($this->gerrit_server, $this->repository)->once();
        $this->event->process();
    }
    
    public function itCreatesContributorsGroup() {   }
    
    public function itCreatesIntegratorsGroup() {    }
    
    public function itCreatesSupermenGroup() {    }
    
    public function itFeedbacksIfUsersNotAddedToGroup() {
        //the following users couldn't be added to their corresponding groups, because they don't exist in Gerrit.
    }

}
?>
