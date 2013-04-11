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

require_once dirname(__FILE__).'/../../bootstrap.php';

class Git_RemoteServer_GerritServerFactoryTest extends TuleapTestCase {

    private $server_id          = 1;
    private $host               = 'g.tuleap.net';
    private $ssh_port           = 32915;
    private $http_port          = 8080;
    private $login              = 'chuck';
    private $identity_file      = '/home/chuck/.ssh/id_rsa';
    private $replication_key    = 'ssh-rsa blablabla john@cake.do';

    private $alternate_server_id = 2;
    private $alternate_host      = 'h.tuleap.net';
    /** @var Git_SystemEventManager */
    private $system_event_manager;
    /** @var Git_RemoteServer_GerritServer  */
    private $main_gerrit_server;
    /** @var Git_RemoteServer_GerritServerFactory  */
    private $factory;

    public function setUp() {
        parent::setUp();
        $dar_1 = array(
            'id'                => $this->server_id,
            'host'              => $this->host,
            'ssh_port'          => $this->ssh_port,
            'http_port'         => $this->http_port,
            'login'             => $this->login,
            'identity_file'     => $this->identity_file,
            'ssh_key'           => $this->replication_key,
        );
        $dar_2 = array(
            'id'                => $this->alternate_server_id,
            'host'              => $this->alternate_host,
            'ssh_port'          => $this->ssh_port,
            'http_port'         => $this->http_port,
            'login'             => $this->login,
            'identity_file'     => $this->identity_file,
            'ssh_key'           => $this->replication_key,
        );

        $git_dao   = mock('GitDao');
        $this->dao = mock('Git_RemoteServer_Dao');
        $this->system_event_manager = mock('Git_SystemEventManager');

        stub($this->dao)->searchAll()->returnsDar($dar_1, $dar_2);
        stub($this->dao)->searchAllByProjectId()->returnsDar($dar_1);
        stub($this->dao)->searchById($this->server_id)->returnsDar($dar_1);
        stub($this->dao)->searchById()->returnsEmptyDar();
        $this->factory = new Git_RemoteServer_GerritServerFactory($this->dao, $git_dao, $this->system_event_manager);

        $this->main_gerrit_server = new Git_RemoteServer_GerritServer(
            $this->server_id,
            $this->host,
            $this->ssh_port,
            $this->http_port,
            $this->login,
            $this->identity_file,
            $this->replication_key
        );
        $this->alternate_gerrit_server  = new Git_RemoteServer_GerritServer(
            $this->alternate_server_id,
            $this->alternate_host,
            $this->ssh_port,
            $this->http_port,
            $this->login,
            $this->identity_file,
            $this->replication_key
        );
        stub($git_dao)->isRemoteServerUsed($this->server_id)->returns(true);
        stub($git_dao)->isRemoteServerUsed($this->alternate_server_id)->returns(false);
    }

    public function itThrowsAnExceptionIfThereIsNoSuchServer() {
        $unexisting_server_id   = 34;
        $repo = aGitRepository()->withRemoteServerId($unexisting_server_id)->build();
        try {
            $this->factory->getServer($repo);
            $this->fail('Should have thrown GerritServerNotFoundException');
        } catch (Git_RemoteServer_NotFoundException $e) {
            $this->assertEqual($e->getMessage(), "No server found with the id: $unexisting_server_id");
        }
    }
    
    public function itReturnsAGerritServer() {
        $repo   = aGitRepository()->withRemoteServerId($this->server_id)->build();
        
        $server = $this->factory->getServer($repo);

        $this->assertIsA($server, 'Git_RemoteServer_GerritServer');
    }

    public function itGetsAllServers() {
        $servers = $this->factory->getServers();
        $this->assertIsA($servers[$this->server_id], 'Git_RemoteServer_GerritServer');
        $this->assertIsA($servers[$this->alternate_server_id], 'Git_RemoteServer_GerritServer');
    }

    public function itGetsAllServersForAGivenProject() {
        $project = stub('Project')->getId()->returns(458);
        expect($this->dao)->searchAllByProjectId(458)->once();
        $servers = $this->factory->getServersForProject($project);
        $this->assertIsA($servers[$this->server_id], 'Git_RemoteServer_GerritServer');
    }

    public function itSavesAnExistingServer() {
        $this->main_gerrit_server->setLogin('new_login');
        expect($this->dao)->save($this->server_id, $this->host, $this->ssh_port, $this->http_port, 'new_login', $this->identity_file, $this->replication_key)->once();
        $this->factory->save($this->main_gerrit_server);
    }

    public function itTriggersKeyUpdateEventOnSave() {
        $this->main_gerrit_server->setLogin('new_login');
        expect($this->system_event_manager)->queueGerritReplicationKeyUpdate($this->main_gerrit_server)->once();
        $this->factory->save($this->main_gerrit_server);
    }

    public function itSetsIdOfNewServerOnSave() {
        $new_server = new Git_RemoteServer_GerritServer(
            0,
            $this->host,
            $this->ssh_port,
            $this->http_port,
            $this->login,
            $this->identity_file,
            $this->replication_key
        );
        stub($this->dao)->save()->returns(113);
        $this->factory->save($new_server);
        $this->assertEqual($new_server->getId(), 113);
    }

    public function itDeletesAnExistingServer() {
        expect($this->dao)->delete($this->alternate_server_id)->once();
        $this->factory->delete($this->alternate_gerrit_server);
    }

    public function itTriggersKeyUpdateEventOnDelete() {
        expect($this->system_event_manager)->queueGerritReplicationKeyUpdate($this->alternate_gerrit_server)->once();
        $this->factory->delete($this->alternate_gerrit_server);
    }

    public function itDoesNotDeleteUsedServer() {
        expect($this->dao)->delete($this->server_id)->never();
        $this->factory->delete($this->main_gerrit_server);
    }

}

?>
