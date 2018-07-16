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

require_once dirname(__FILE__).'/../bootstrap.php';
require_once 'common/log/Logger.class.php';

abstract class SystemEvent_GIT_GERRIT_MIGRATION_BaseTest extends TuleapTestCase {

    /**
     * @var SystemEvent_GIT_GERRIT_MIGRATION
     */
    protected $event;
    protected $repository_id         = 123;
    protected $deleted_repository_id = 124;
    protected $remote_server_id      = 12;
    protected $repository;
    protected $gerrit_server;
    protected $server_factory;
    protected $gitolite_backend;
    protected $user_manager;

    public function setUp() {
        parent::setUp();

        $this->dao              = mock('GitDao');
        $this->gerrit_server    = mock('Git_RemoteServer_GerritServer');
        $this->server_factory   = mock('Git_RemoteServer_GerritServerFactory');
        $this->project_creator  = mock('Git_Driver_Gerrit_ProjectCreator');
        $this->gitolite_backend = mock('Git_Backend_Gitolite');
        $this->repository       = mock('GitRepository');
        $this->user_manager     = mock('UserManager');
        stub($this->repository)->getBackend()->returns($this->gitolite_backend);

        $factory = mock('GitRepositoryFactory');
        stub($factory)->getRepositoryById($this->repository_id)->returns($this->repository);

        $id= $type= $parameters= $priority= $status= $create_date= $process_date= $end_date= $log = 0;
        $this->event = TestHelper::getPartialMock('SystemEvent_GIT_GERRIT_MIGRATION', array('done', 'warning', 'error'),
                                                  array($id, $type, $parameters, $priority, $status, $create_date, $process_date, $end_date, $log));
        $this->event->setParameters("$this->repository_id::$this->remote_server_id::true");
        $this->logger = mock('Logger');
        $this->event->injectDependencies($this->dao, $factory, $this->server_factory, $this->logger, $this->project_creator, mock('Git_GitRepositoryUrlManager'), $this->user_manager, mock('MailBuilder'));
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

    public function itUpdatesGitolitePermissionsToForbidPushesByAnyoneButGerrit() {
        stub($this->server_factory)->getServer($this->repository)->returns($this->gerrit_server);
        expect($this->gitolite_backend)->updateRepoConf()->once();
        $this->assertTrue($this->event->process());
    }

    public function itInformsAboutMigrationSuccess() {
        stub($this->server_factory)->getServer($this->repository)->returns($this->gerrit_server);
        $remote_project = 'tuleap.net-Firefox/mobile';
        $gerrit_url     = 'https://gerrit.example.com:8888/';
        stub($this->project_creator)->createGerritProject()->returns($remote_project);
        stub($this->gerrit_server)->getBaseUrl()->returns($gerrit_url);
        expect($this->event)->done("Created project $remote_project on $gerrit_url")->once();
        $this->event->process();
    }

    public function itInformsAboutAnyGenericFailure() {
        stub($this->user_manager)->getUserById()->returns(aUser()->withId(0)->build());
        stub($this->server_factory)->getServer($this->repository)->returns($this->gerrit_server);
        $e = new Exception("failure detail");
        stub($this->project_creator)->createGerritProject()->throws($e);
        expect($this->event)->error("failure detail")->once();
        expect($this->logger)->error("An error occured while processing event: ".$this->event->verbalizeParameters(null), $e)->once();
        $this->event->process();
    }

    public function itInformsAboutAnyGerritRelatedFailureByAddingAPrefix() {
        stub($this->user_manager)->getUserById()->returns(aUser()->withId(0)->build());
        stub($this->server_factory)->getServer($this->repository)->returns($this->gerrit_server);
        $e = new Git_Driver_Gerrit_Exception("failure detail");
        stub($this->project_creator)->createGerritProject()->throws($e);
        expect($this->event)->error("gerrit: failure detail")->once();
        expect($this->logger)->error("Gerrit failure: ".$this->event->verbalizeParameters(null), $e)->once();
        $this->event->process();
    }

    public function itInformsAboutAnyServerFactoryFailure() {
        stub($this->user_manager)->getUserById()->returns(aUser()->withId(0)->build());
        $e = new Exception("failure detail");
        stub($this->server_factory)->getServer()->throws($e);
        expect($this->event)->error("failure detail")->once();
        expect($this->logger)->error("An error occured while processing event: ".$this->event->verbalizeParameters(null), $e)->once();
        $this->event->process();
    }

    public function itMarksTheEventAsWarningWhenTheRepoDoesNotExist() {
        $this->event->setParameters("$this->deleted_repository_id::$this->remote_server_id");
        expect($this->event)->error('Unable to find repository, perhaps it was deleted in the mean time?')->once();
        $this->event->process();
    }
}

class SystemEvent_GIT_GERRIT_MIGRATION_CallsToProjectCreatorTest extends SystemEvent_GIT_GERRIT_MIGRATION_BaseTest  {

    public function itCreatesAProject() {
        stub($this->server_factory)->getServer($this->repository)->returns($this->gerrit_server);
        //ssh gerrit gerrit create tuleap.net-Firefox/all/mobile
        expect($this->project_creator)->createGerritProject($this->gerrit_server, $this->repository, "true")->once();
        $this->event->process();
    }
}
