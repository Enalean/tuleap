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

class SystemEvent_GIT_GERRIT_MIGRATION_BaseTest extends TuleapTestCase {

    /**
     * @var SystemEvent_GIT_GERRIT_MIGRATION
     */
    protected $event;
    protected $repository_id = 123;
    protected $driver;
    protected $repository;
    protected $gerrit_server;
    
    public function setUp() {
        parent::setUp();
        
        $this->dao = mock('GitDao');
        
        $this->driver = mock('Git_Driver_Gerrit');
        
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');
        $this->repository = mock('GitRepository');
        
        $gerrit_server_factory = mock('Git_RemoteServer_GerritServerFactory');
        stub($gerrit_server_factory)->getServer($this->repository)->returns($this->gerrit_server);

        $factory = mock('GitRepositoryFactory');
        stub($factory)->getRepositoryById($this->repository_id)->returns($this->repository);
        
        $id= $type= $parameters= $priority= $status= $create_date= $process_date= $end_date= $log = 0;
        $this->event = new SystemEvent_GIT_GERRIT_MIGRATION($id, $type, $parameters, $priority, $status, $create_date, $process_date, $end_date, $log);
        $this->event->setParameters("$this->repository_id");
        $this->event->injectDependencies($this->dao, $this->driver, $factory, $gerrit_server_factory);
        
    }
}
class SystemEvent_GIT_GERRIT_MIGRATION_BackendTest extends SystemEvent_GIT_GERRIT_MIGRATION_BaseTest  {
    
    public function itSwitchesTheBackendToGerrit() {
        expect($this->dao)->switchToGerrit($this->repository_id)->once();
        $this->event->process();
    }
    
}

class SystemEvent_GIT_GERRIT_MIGRATION_CallsToGerritTest extends SystemEvent_GIT_GERRIT_MIGRATION_BaseTest  {
    
    public function itCreatesAProject() { 
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

    public function itRaisesAnErrorIfGerritServerIsUnreachable() {
    }
    public function itRaisesAnErrorIfTheProjectAlreadyExist() {
    }
}
?>
