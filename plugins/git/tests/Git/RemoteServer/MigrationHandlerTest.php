<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\RemoteServer\Gerrit;

use Project;
use TuleapTestCase;
use Git_SystemEventManager;
use Git_RemoteServer_NotFoundException;

require_once __DIR__ .'/../../bootstrap.php';

class MigrationHandlerBaseTest extends TuleapTestCase {

    /**
     * @var Git_SystemEventManager
     */
    protected $git_system_event_manager;

    /**
     * @var MigrationHandler
     */
    protected $handler;

    protected $server_factory;
    protected $driver_factory;

    public function setUp() {
        parent::setUp();

        $this->git_system_event_manager = mock('Git_SystemEventManager');
        $this->server_factory           = mock('Git_RemoteServer_GerritServerFactory');
        $this->driver_factory           = mock('Git_Driver_Gerrit_GerritDriverFactory');
        $project_history_dao            = mock('ProjectHistoryDao');
        $this->project_creator_status   = mock('Git_Driver_Gerrit_ProjectCreatorStatus');

        $this->handler = new MigrationHandler(
            $this->git_system_event_manager,
            $this->server_factory,
            $this->driver_factory,
            $project_history_dao,
            $this->project_creator_status
        );
    }
}

class MigrationHandlerMigrateTest extends MigrationHandlerBaseTest {

    public function setUp() {
        parent::setUp();

        $this->user   = mock('PFUser');
        $this->server = aGerritServer()->withId(1)->build();
    }

    public function itThrowsAnExceptionIfRepositoryCannotBeMigrated() {
        $repository = stub('GitRepository')->canMigrateToGerrit()->returns(false);
        stub($repository)->getProject()->returns(\Mockery::spy(Project::class));

        $remote_server_id   = 1;
        $gerrit_template_id = "none";

        $this->expectException('Tuleap\Git\Exceptions\RepositoryCannotBeMigratedException');
        expect($this->git_system_event_manager)->queueMigrateToGerrit()->never();

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function itThrowsAnExceptionIfRepositoryIsAlreadyInQueueForMigration() {
        $repository = stub('GitRepository')->canMigrateToGerrit()->returns(true);
        stub($repository)->getProject()->returns(\Mockery::spy(Project::class));

        $remote_server_id   = 1;
        $gerrit_template_id = "none";

        stub($this->project_creator_status)->getStatus()->returns('QUEUE');

        $this->expectException('Tuleap\Git\Exceptions\RepositoryAlreadyInQueueForMigrationException');
        expect($this->git_system_event_manager)->queueMigrateToGerrit()->never();

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function itThrowsAnExceptionIfRepositoryWillBeMigratedIntoARestrictedGerritServer() {
        $repository = stub('GitRepository')->canMigrateToGerrit()->returns(true);
        stub($repository)->getProject()->returns(\Mockery::spy(Project::class));

        $remote_server_id   = 1;
        $gerrit_template_id = "none";

        stub($this->server_factory)->getServerById(1)->returns($this->server);
        stub($this->server_factory)->getAvailableServersForProject()->returns(array());

        $this->expectException('Tuleap\Git\Exceptions\RepositoryCannotBeMigratedOnRestrictedGerritServerException');
        expect($this->git_system_event_manager)->queueMigrateToGerrit()->never();

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function itMigratesRepository() {
        $repository = stub('GitRepository')->canMigrateToGerrit()->returns(true);
        stub($repository)->getProject()->returns(\Mockery::spy(Project::class));

        $remote_server_id   = 1;
        $gerrit_template_id = "none";

        stub($this->server_factory)->getServerById(1)->returns($this->server);
        stub($this->server_factory)->getAvailableServersForProject()->returns(array(1 => $this->server));

        expect($this->git_system_event_manager)->queueMigrateToGerrit()->once();

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function itDoesNothingWhenServerDoesNotExist() {
        $repository         = stub('GitRepository')->canMigrateToGerrit()->returns(true);
        $remote_server_id   = 1;
        $gerrit_template_id = "none";

        stub($this->server_factory)->getServerById()->throws(new Git_RemoteServer_NotFoundException($remote_server_id));

        $this->expectException('Git_RemoteServer_NotFoundException');
        expect($this->git_system_event_manager)->queueMigrateToGerrit()->never();

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }
}

class MigrationHandlerDisconnectTest extends MigrationHandlerBaseTest {

    public function itThrowsAnExceptionIfRepositoryIsNotMigrated() {
        $repository        = stub('GitRepository')->isMigratedToGerrit()->returns(false);
        $disconnect_option = '';

        $this->expectException('Tuleap\Git\Exceptions\RepositoryNotMigratedException');

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function itDisconnectsWithoutOptionsIfTheRemoteServerDoesNotExist() {
        $backend           = stub('Git_Backend_Gitolite')->disconnectFromGerrit()->returns(true);
        $repository        = stub('GitRepository')->isMigratedToGerrit()->returns(true);
        $disconnect_option = '';

        stub($repository)->getBackend()->returns($backend);

        expect($this->git_system_event_manager)->queueRepositoryUpdate()->once();
        expect($this->git_system_event_manager)->queueRemoteProjectDeletion()->never();
        expect($this->git_system_event_manager)->queueRemoteProjectReadOnly()->never();

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function itDisconnectsWithtEmptyOption() {
        $backend           = stub('Git_Backend_Gitolite')->disconnectFromGerrit()->returns(true);
        $repository        = stub('GitRepository')->isMigratedToGerrit()->returns(true);
        $server            = mock('Git_RemoteServer_GerritServer');
        $driver            = mock('Git_Driver_Gerrit');
        $disconnect_option = '';

        stub($repository)->getBackend()->returns($backend);
        stub($this->server_factory)->getServerById()->returns($server);
        stub($this->driver_factory)->getDriver($server)->returns($driver);

        expect($this->git_system_event_manager)->queueRepositoryUpdate()->once();
        expect($this->git_system_event_manager)->queueRemoteProjectDeletion()->never();
        expect($this->git_system_event_manager)->queueRemoteProjectReadOnly()->never();

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function itDisconnectsWithtReadOnlyOption() {
        $backend           = stub('Git_Backend_Gitolite')->disconnectFromGerrit()->returns(true);
        $repository        = stub('GitRepository')->isMigratedToGerrit()->returns(true);
        $server            = mock('Git_RemoteServer_GerritServer');
        $driver            = mock('Git_Driver_Gerrit');
        $disconnect_option = 'read-only';

        stub($repository)->getBackend()->returns($backend);
        stub($this->server_factory)->getServerById()->returns($server);
        stub($this->driver_factory)->getDriver($server)->returns($driver);

        expect($this->git_system_event_manager)->queueRepositoryUpdate()->once();
        expect($this->git_system_event_manager)->queueRemoteProjectDeletion()->never();
        expect($this->git_system_event_manager)->queueRemoteProjectReadOnly()->once();

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function itDisconnectsWithtDeleteOption() {
        $backend           = stub('Git_Backend_Gitolite')->disconnectFromGerrit()->returns(true);
        $repository        = stub('GitRepository')->isMigratedToGerrit()->returns(true);
        $server            = mock('Git_RemoteServer_GerritServer');
        $driver            = mock('Git_Driver_Gerrit');
        $disconnect_option = 'delete';

        stub($driver)->isDeletePluginEnabled($server)->returns(true);
        stub($repository)->getBackend()->returns($backend);
        stub($this->server_factory)->getServerById()->returns($server);
        stub($this->driver_factory)->getDriver($server)->returns($driver);

        expect($this->git_system_event_manager)->queueRepositoryUpdate()->once();
        expect($this->git_system_event_manager)->queueRemoteProjectDeletion()->once();
        expect($this->git_system_event_manager)->queueRemoteProjectReadOnly()->never();

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function itThrowsAnExceptionIfDeletePluginNotInstalled() {
        $backend           = stub('Git_Backend_Gitolite')->disconnectFromGerrit()->returns(true);
        $repository        = stub('GitRepository')->isMigratedToGerrit()->returns(true);
        $server            = mock('Git_RemoteServer_GerritServer');
        $driver            = mock('Git_Driver_Gerrit');
        $disconnect_option = 'delete';

        stub($driver)->isDeletePluginEnabled($server)->returns(false);
        stub($repository)->getBackend()->returns($backend);
        stub($this->server_factory)->getServerById()->returns($server);
        stub($this->driver_factory)->getDriver($server)->returns($driver);

        expect($this->git_system_event_manager)->queueRepositoryUpdate()->never();
        expect($this->git_system_event_manager)->queueRemoteProjectDeletion()->never();
        expect($this->git_system_event_manager)->queueRemoteProjectReadOnly()->never();

        $this->expectException('Tuleap\Git\Exceptions\DeletePluginNotInstalledException');

        $this->handler->disconnect($repository, $disconnect_option);
    }

}