<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

require_once __DIR__ .'/../../bootstrap.php';

class Git_RemoteServer_GerritServerFactoryTest extends TuleapTestCase
{

    private $server_id          = 1;
    private $host               = 'g.tuleap.net';
    private $ssh_port           = 32915;
    private $http_port          = 8080;
    private $login              = 'chuck';
    private $identity_file      = '/home/chuck/.ssh/id_rsa';
    private $replication_key    = 'ssh-rsa blablabla john@cake.do';
    private $use_ssl            = false;
    private $gerrit_version     = '2.5';
    private $http_password      = 'azerty';
    private $auth_type          = 'Digest';

    private $alternate_server_id      = 2;
    private $alternate_host           = 'h.tuleap.net';
    private $alternate_gerrit_version = '2.8+';
    /** @var Git_SystemEventManager */
    private $system_event_manager;
    /** @var Git_RemoteServer_GerritServer  */
    private $main_gerrit_server;
    /** @var Git_RemoteServer_GerritServerFactory  */
    private $factory;

    /** @var ProjectManager */
    private $project_manager;

    private $dar_1;
    private $dar_2;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->dar_1 = array(
            'id'                   => $this->server_id,
            'host'                 => $this->host,
            'ssh_port'             => $this->ssh_port,
            'http_port'            => $this->http_port,
            'login'                => $this->login,
            'identity_file'        => $this->identity_file,
            'ssh_key'              => $this->replication_key,
            'use_ssl'              => $this->use_ssl,
            'gerrit_version'       => $this->gerrit_version,
            'http_password'        => $this->http_password,
            'auth_type'            => $this->auth_type,
            'replication_password' => '',
        );
        $this->dar_2 = array(
            'id'                => $this->alternate_server_id,
            'host'              => $this->alternate_host,
            'ssh_port'          => $this->ssh_port,
            'http_port'         => $this->http_port,
            'login'             => $this->login,
            'identity_file'     => $this->identity_file,
            'ssh_key'           => $this->replication_key,
            'use_ssl'           => $this->use_ssl,
            'gerrit_version'    => $this->alternate_gerrit_version,
            'http_password'     => $this->http_password,
            'auth_type'         => $this->auth_type,
            'replication_password' => '',
        );

        $git_dao   = safe_mock(GitDao::class);
        $this->dao = \Mockery::spy(Git_RemoteServer_Dao::class);
        $this->system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);

        $this->dao->shouldReceive('searchAll')->andReturns([$this->dar_1, $this->dar_2]);
        $this->dao->shouldReceive('getById')->with($this->server_id)->andReturns($this->dar_1);
        $this->dao->shouldReceive('getById')->andReturns([]);

        $this->project_manager = \Mockery::spy(\ProjectManager::class);

        $this->factory = new Git_RemoteServer_GerritServerFactory($this->dao, $git_dao, $this->system_event_manager, $this->project_manager);

        $this->main_gerrit_server = new Git_RemoteServer_GerritServer(
            $this->server_id,
            $this->host,
            $this->ssh_port,
            $this->http_port,
            $this->login,
            $this->identity_file,
            $this->replication_key,
            $this->use_ssl,
            $this->gerrit_version,
            $this->http_password,
            '',
            $this->auth_type
        );
        $this->alternate_gerrit_server  = new Git_RemoteServer_GerritServer(
            $this->alternate_server_id,
            $this->alternate_host,
            $this->ssh_port,
            $this->http_port,
            $this->login,
            $this->identity_file,
            $this->replication_key,
            $this->use_ssl,
            $this->alternate_gerrit_version,
            $this->http_password,
            '',
            $this->auth_type
        );
        $git_dao->shouldReceive('isRemoteServerUsed')->with($this->server_id)->andReturns(true);
        $git_dao->shouldReceive('isRemoteServerUsed')->with($this->alternate_server_id)->andReturns(false);
    }

    public function itThrowsAnExceptionIfThereIsNoSuchServer()
    {
        $unexisting_server_id   = 34;
        $repo = aGitRepository()->withRemoteServerId($unexisting_server_id)->build();
        try {
            $this->factory->getServer($repo);
            $this->fail('Should have thrown GerritServerNotFoundException');
        } catch (Git_RemoteServer_NotFoundException $e) {
            $this->assertEqual($e->getMessage(), "No server found with the id: $unexisting_server_id");
        }
    }

    public function itReturnsAGerritServer()
    {
        $repo   = aGitRepository()->withRemoteServerId($this->server_id)->build();

        $server = $this->factory->getServer($repo);

        $this->assertIsA($server, 'Git_RemoteServer_GerritServer');
    }

    public function itGetsAllServers()
    {
        $servers = $this->factory->getServers();
        $this->assertIsA($servers[$this->server_id], 'Git_RemoteServer_GerritServer');
        $this->assertIsA($servers[$this->alternate_server_id], 'Git_RemoteServer_GerritServer');
    }

    public function itGetsAllServersForAGivenProject()
    {
        $this->project_manager->shouldReceive('getChildProjects')->andReturns(array());
        $project = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(458)->getMock();
        $this->dao->shouldReceive('searchAllByProjectId')->with(458)->andReturn([$this->dar_1])->once();
        $servers = $this->factory->getServersForProject($project);
        $this->assertIsA($servers[$this->server_id], 'Git_RemoteServer_GerritServer');
    }

    public function itReturnsChildServers()
    {
        $parent     = \Mockery::spy(\Project::class, ['getID' => 369, 'getUnixName' => false, 'isPublic' => false]);
        $child1      = \Mockery::spy(\Project::class, ['getID' => 933, 'getUnixName' => false, 'isPublic' => false]);
        $child2      = \Mockery::spy(\Project::class, ['getID' => 934, 'getUnixName' => false, 'isPublic' => false]);

        $this->project_manager->shouldReceive('getChildProjects')->with(369)->andReturns(array($child1, $child2));
        $this->project_manager->shouldReceive('getChildProjects')->andReturns(array());

        $this->dao->shouldReceive('searchAllByProjectId')->with(933)->andReturns([$this->dar_1]);
        $this->dao->shouldReceive('searchAllByProjectId')->with(934)->andReturns([$this->dar_2]);
        $this->dao->shouldReceive('searchAllByProjectId')->andReturns([]);

        $servers = $this->factory->getServersForProject($parent);
        $this->assertCount($servers, 2);
        $this->assertIsA($servers[$this->server_id], 'Git_RemoteServer_GerritServer');
        $this->assertEqual($servers[$this->server_id]->getId(), $this->server_id);
        $this->assertIsA($servers[$this->alternate_server_id], 'Git_RemoteServer_GerritServer');
        $this->assertEqual($servers[$this->alternate_server_id]->getId(), $this->alternate_server_id);
    }

    public function itReturnsAllProjectChildren()
    {
        $parent      = \Mockery::spy(\Project::class, ['getID' => 369, 'getUnixName' => false, 'isPublic' => false]);
        $child1      = \Mockery::spy(\Project::class, ['getID' => 933, 'getUnixName' => false, 'isPublic' => false]);
        $child2      = \Mockery::spy(\Project::class, ['getID' => 934, 'getUnixName' => false, 'isPublic' => false]);
        $grandchild  = \Mockery::spy(\Project::class, ['getID' => 96, 'getUnixName' => false, 'isPublic' => false]);

        $this->project_manager->shouldReceive('getChildProjects')->with(369)->andReturns(array($child1, $child2));
        $this->project_manager->shouldReceive('getChildProjects')->with(933)->andReturns(array($grandchild));
        $this->project_manager->shouldReceive('getChildProjects')->andReturns(array());

        $this->dao->shouldReceive('searchAllByProjectId')->andReturn([])->times(4);

        $this->factory->getServersForProject($parent);
    }

    public function itReturnsOnlyOneServerEvenWhenThereAreSeveral()
    {
        $parent     = \Mockery::spy(\Project::class, ['getID' => 369, 'getUnixName' => false, 'isPublic' => false]);
        $child      = \Mockery::spy(\Project::class, ['getID' => 933, 'getUnixName' => false, 'isPublic' => false]);

        $this->project_manager->shouldReceive('getChildProjects')->with(369)->andReturns(array($child));
        $this->project_manager->shouldReceive('getChildProjects')->andReturns(array());

        $this->dao->shouldReceive('searchAllByProjectId')->with(369)->andReturns([$this->dar_1]);
        $this->dao->shouldReceive('searchAllByProjectId')->with(933)->andReturns([$this->dar_1]);

        $servers = $this->factory->getServersForProject($parent);
        $this->assertCount($servers, 1);
    }


    public function itSavesAnExistingServer()
    {
        $this->main_gerrit_server->setLogin('new_login');
        $this->dao->shouldReceive('save')->with($this->server_id, $this->host, $this->ssh_port, $this->http_port, 'new_login', $this->identity_file, $this->replication_key, $this->use_ssl, $this->gerrit_version, $this->http_password, $this->auth_type)->once();
        $this->factory->save($this->main_gerrit_server);
    }

    public function itTriggersKeyUpdateEventOnSave()
    {
        $this->main_gerrit_server->setLogin('new_login');
        $this->system_event_manager->shouldReceive('queueGerritReplicationKeyUpdate')->with($this->main_gerrit_server)->once();
        $this->factory->save($this->main_gerrit_server);
    }

    public function itSetsIdOfNewServerOnSave()
    {
        $new_server = new Git_RemoteServer_GerritServer(
            0,
            $this->host,
            $this->ssh_port,
            $this->http_port,
            $this->login,
            $this->identity_file,
            $this->replication_key,
            $this->use_ssl,
            $this->gerrit_version,
            $this->http_password,
            '',
            $this->auth_type
        );
        $this->dao->shouldReceive('save')->andReturns(113);
        $this->factory->save($new_server);
        $this->assertEqual($new_server->getId(), 113);
    }

    public function itDeletesAnExistingServer()
    {
        $this->dao->shouldReceive('delete')->with($this->alternate_server_id)->once();
        $this->factory->delete($this->alternate_gerrit_server);
    }

    public function itTriggersKeyUpdateEventOnDelete()
    {
        $this->system_event_manager->shouldReceive('queueGerritReplicationKeyUpdate')->with($this->alternate_gerrit_server)->once();
        $this->factory->delete($this->alternate_gerrit_server);
    }

    public function itDoesNotDeleteUsedServer()
    {
        $this->dao->shouldReceive('delete')->with($this->server_id)->never();
        $this->factory->delete($this->main_gerrit_server);
    }
}
