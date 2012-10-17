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
        $this->userfinder = mock('UserFinder');
        $this->event->injectDependencies($this->dao, $this->driver, $factory, $this->server_factory, $this->logger, $this->userfinder);
        
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
    
    public function itCreatesContributorsGroup() {
        $group_name = 'contributors';
        $permissions_level = Git::PERM_READ;
        $call_order = 0;
        
        $this->expectGroupCreation($group_name, $permissions_level, $call_order);
    }
    
    //do not get members if dynamic group is registered or all users
    //what if Userfinder returns read OR write users when I ask for write
    
    public function itCreatesIntegratorsGroup() {
        $group_name        = 'integrators';
        $permissions_level = Git::PERM_WRITE;
        $call_order        = 1;
    
        $this->expectGroupCreation($group_name, $permissions_level, $call_order);
    }
    
    public function itCreatesSupermenGroup() {
        $group_name        = 'supermen';
        $permissions_level = Git::PERM_WPLUS;
        $call_order        = 2;
    
        $this->expectGroupCreation($group_name, $permissions_level, $call_order);
    }
    
    public function itFeedbacksIfUsersNotAddedToGroup() {
        //the following users couldn't be added to their corresponding groups, because they don't exist in Gerrit.
    }

    public function expectGroupCreation($group_name, $permissions_level, $call_order) {
        $user_list = array(aUser()->withUserName('goyotm')->build(),  aUser()->withUserName('martissonj')->build());
        stub($this->userfinder)->getUsersForWhichTheHighestPermissionIs($permissions_level, $this->repository_id)->returns($user_list);

        stub($this->server_factory)->getServer($this->repository)->returns($this->gerrit_server);
        expect($this->driver)->createGroup($this->gerrit_server, $this->repository, $group_name, $user_list)->at($call_order);
        $this->driver->expectCallCount('createGroup', 3);
        
        $this->event->process();
        
    }

}
?>
