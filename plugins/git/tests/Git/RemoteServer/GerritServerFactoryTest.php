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
require_once GIT_BASE_DIR.'/Git/RemoteServer/GerritServerFactory.class.php';
require_once dirname(__FILE__).'/../../builders/aGitRepository.php';

class Git_RemoteServer_GerritServerFactoryTest extends TuleapTestCase {

    private $server_id          = 1;
    private $host               = 'g.tuleap.net';
    private $ssh_port           = 32915;
    private $http_port          = 8080;
    private $login              = 'chuck';
    private $identity_file      = '/home/chuck/.ssh/id_rsa';
    private $replication_key    = 'my_replication-key';

    private $alternate_server_id = 2;
    private $alternate_host      = 'h.tuleap.net';

    public function setUp() {
        parent::setUp();
        $dar_1 = array(
            'id'                => $this->server_id,
            'host'              => $this->host,
            'ssh_port'          => $this->ssh_port,
            'http_port'         => $this->http_port,
            'login'             => $this->login,
            'identity_file'     => $this->identity_file,
            'replication_key'   =>  $this->replication_key,
        );
        $dar_2 = array(
            'id'                => $this->alternate_server_id,
            'host'              => $this->alternate_host,
            'ssh_port'          => $this->ssh_port,
            'http_port'         => $this->http_port,
            'login'             => $this->login,
            'identity_file'     => $this->identity_file,
            'replication_key'   =>  $this->replication_key,
        );

        $git_dao   = mock('GitDao');
        $this->dao = mock('Git_RemoteServer_Dao');
        stub($this->dao)->searchAll()->returnsDar($dar_1, $dar_2);
        stub($this->dao)->searchById($this->server_id)->returnsDar($dar_1);
        stub($this->dao)->searchById()->returnsEmptyDar();
        $this->factory = new Git_RemoteServer_GerritServerFactory($this->dao, $git_dao);

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
        $this->assertEqual($server, $this->main_gerrit_server);
    }

    public function itGetsAllServers() {
        $servers = $this->factory->getServers();
        $this->assertEqual($servers, array(1 => $this->main_gerrit_server, 2 => $this->alternate_gerrit_server));
    }

    public function itSavesAnExistingServer() {
        $this->main_gerrit_server->setLogin('new_login');
        expect($this->dao)->save($this->server_id, $this->host, $this->ssh_port, $this->http_port, 'new_login', $this->identity_file)->once();
        $this->factory->save($this->main_gerrit_server);
    }

    public function itDeletesAnExistingServer() {
        expect($this->dao)->delete($this->alternate_server_id)->once();
        $this->factory->delete($this->alternate_gerrit_server);
    }

    public function itDoesNotDeleteUsedServer() {
        expect($this->dao)->delete($this->server_id)->never();
        $this->factory->delete($this->main_gerrit_server);
    }

}

?>
