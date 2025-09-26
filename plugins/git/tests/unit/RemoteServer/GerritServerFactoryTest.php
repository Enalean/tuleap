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

declare(strict_types=1);

namespace Tuleap\Git\RemoteServer;

use Git_RemoteServer_Dao;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use Git_RemoteServer_NotFoundException;
use Git_SystemEventManager;
use GitDao;
use GitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GerritServerFactoryTest extends TestCase
{
    private int $server_id                   = 1;
    private string $host                     = 'g.tuleap.net';
    private int $ssh_port                    = 32915;
    private int $http_port                   = 8080;
    private string $login                    = 'chuck';
    private string $identity_file            = '/home/chuck/.ssh/id_rsa';
    private string $replication_key          = 'ssh-rsa blablabla john@cake.do';
    private bool $use_ssl                    = false;
    private string $gerrit_version           = '2.5';
    private string $http_password            = 'azerty';
    private int $alternate_server_id         = 2;
    private string $alternate_host           = 'h.tuleap.net';
    private string $alternate_gerrit_version = '2.8+';
    private Git_SystemEventManager&MockObject $system_event_manager;
    private Git_RemoteServer_GerritServer $main_gerrit_server;
    private Git_RemoteServer_GerritServerFactory $factory;
    private ProjectManager&MockObject $project_manager;
    private array $dar_1;
    private array $dar_2;
    private Git_RemoteServer_Dao&MockObject $dao;
    private Git_RemoteServer_GerritServer $alternate_gerrit_server;

    #[\Override]
    protected function setUp(): void
    {
        $this->dar_1 = [
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
            'replication_password' => '',
        ];
        $this->dar_2 = [
            'id'                   => $this->alternate_server_id,
            'host'                 => $this->alternate_host,
            'ssh_port'             => $this->ssh_port,
            'http_port'            => $this->http_port,
            'login'                => $this->login,
            'identity_file'        => $this->identity_file,
            'ssh_key'              => $this->replication_key,
            'use_ssl'              => $this->use_ssl,
            'gerrit_version'       => $this->alternate_gerrit_version,
            'http_password'        => $this->http_password,
            'replication_password' => '',
        ];

        $git_dao                    = $this->createMock(GitDao::class);
        $this->dao                  = $this->createMock(Git_RemoteServer_Dao::class);
        $this->system_event_manager = $this->createMock(Git_SystemEventManager::class);

        $this->dao->method('searchAll')->willReturn([$this->dar_1, $this->dar_2]);
        $this->dao->method('getById')->willReturnCallback(fn($id) => $id === $this->server_id ? $this->dar_1 : []);

        $this->project_manager = $this->createMock(ProjectManager::class);

        $this->factory = new Git_RemoteServer_GerritServerFactory($this->dao, $git_dao, $this->system_event_manager, $this->project_manager);

        $this->main_gerrit_server      = new Git_RemoteServer_GerritServer(
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
            ''
        );
        $this->alternate_gerrit_server = new Git_RemoteServer_GerritServer(
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
            ''
        );
        $git_dao->method('isRemoteServerUsed')->willReturnCallback(fn($id) => match ($id) {
            $this->server_id           => true,
            $this->alternate_server_id => false,
        });
    }

    private function buildGitRepository(int $remote_server_id): GitRepository
    {
        return GitRepositoryTestBuilder::aProjectRepository()->migratedToGerrit($remote_server_id)->build();
    }

    public function testItThrowsAnExceptionIfThereIsNoSuchServer(): void
    {
        $unexisting_server_id = 34;
        $repo                 = $this->buildGitRepository($unexisting_server_id);
        try {
            $this->factory->getServer($repo);
            self::fail('Should have thrown GerritServerNotFoundException');
        } catch (Git_RemoteServer_NotFoundException $e) {
            self::assertEquals("No server found with the id: $unexisting_server_id", $e->getMessage());
        }
    }

    public function testItReturnsAGerritServer(): void
    {
        $repo   = $this->buildGitRepository($this->server_id);
        $server = $this->factory->getServer($repo);

        self::assertInstanceOf(Git_RemoteServer_GerritServer::class, $server);
    }

    public function testItGetsAllServers(): void
    {
        $servers = $this->factory->getServers();
        self::assertInstanceOf(Git_RemoteServer_GerritServer::class, $servers[$this->server_id]);
        self::assertInstanceOf(Git_RemoteServer_GerritServer::class, $servers[$this->alternate_server_id]);
    }

    public function testItGetsAllServersForAGivenProject(): void
    {
        $this->project_manager->method('getChildProjects')->willReturn([]);
        $project = ProjectTestBuilder::aProject()->withId(458)->build();
        $this->dao->expects($this->once())->method('searchAllByProjectId')->with(458)->willReturn([$this->dar_1]);
        $servers = $this->factory->getServersForProject($project);
        self::assertInstanceOf(Git_RemoteServer_GerritServer::class, $servers[$this->server_id]);
    }

    public function testItReturnsChildServers(): void
    {
        $parent = ProjectTestBuilder::aProject()->withId(369)->withAccessPrivate()->build();
        $child1 = ProjectTestBuilder::aProject()->withId(933)->withAccessPrivate()->build();
        $child2 = ProjectTestBuilder::aProject()->withId(934)->withAccessPrivate()->build();

        $this->project_manager->method('getChildProjects')->willReturnCallback(fn($id) => match ((int) $id) {
            369     => [$child1, $child2],
            default => [],
        });

        $this->dao->method('searchAllByProjectId')->willReturnCallback(fn($id) => match ((int) $id) {
            933     => [$this->dar_1],
            934     => [$this->dar_2],
            default => [],
        });

        $servers = $this->factory->getServersForProject($parent);
        self::assertCount(2, $servers);
        self::assertInstanceOf(Git_RemoteServer_GerritServer::class, $servers[$this->server_id]);
        self::assertEquals($this->server_id, $servers[$this->server_id]->getId());
        self::assertInstanceOf(Git_RemoteServer_GerritServer::class, $servers[$this->alternate_server_id]);
        self::assertEquals($this->alternate_server_id, $servers[$this->alternate_server_id]->getId());
    }

    public function testItReturnsAllProjectChildren(): void
    {
        $parent     = ProjectTestBuilder::aProject()->withId(369)->withAccessPrivate()->build();
        $child1     = ProjectTestBuilder::aProject()->withId(933)->withAccessPrivate()->build();
        $child2     = ProjectTestBuilder::aProject()->withId(934)->withAccessPrivate()->build();
        $grandchild = ProjectTestBuilder::aProject()->withId(96)->withAccessPrivate()->build();

        $this->project_manager->method('getChildProjects')->willReturnCallback(fn($id) => match ((int) $id) {
            369     => [$child1, $child2],
            933     => [$grandchild],
            default => [],
        });

        $this->dao->expects($this->exactly(4))->method('searchAllByProjectId')->willReturn([]);

        $this->factory->getServersForProject($parent);
    }

    public function testItReturnsOnlyOneServerEvenWhenThereAreSeveral(): void
    {
        $parent = ProjectTestBuilder::aProject()->withId(369)->withAccessPrivate()->build();
        $child  = ProjectTestBuilder::aProject()->withId(933)->withAccessPrivate()->build();

        $this->project_manager->method('getChildProjects')->willReturnCallback(fn($id) => match ((int) $id) {
            369     => [$child],
            default => [],
        });
        $this->dao->method('searchAllByProjectId')->willReturnCallback(fn($id) => match ((int) $id) {
            369, 933 => [$this->dar_1],
        });

        $servers = $this->factory->getServersForProject($parent);
        self::assertCount(1, $servers);
    }

    public function testItSavesAnExistingServer(): void
    {
        $this->main_gerrit_server->setLogin('new_login');
        $this->dao->expects($this->once())->method('save')
            ->with($this->server_id, $this->host, $this->ssh_port, $this->http_port, 'new_login', $this->identity_file, $this->replication_key, $this->use_ssl, $this->gerrit_version, $this->http_password);
        $this->system_event_manager->method('queueGerritReplicationKeyUpdate');
        $this->factory->save($this->main_gerrit_server);
    }

    public function testItTriggersKeyUpdateEventOnSave(): void
    {
        $this->main_gerrit_server->setLogin('new_login');
        $this->system_event_manager->expects($this->once())->method('queueGerritReplicationKeyUpdate')->with($this->main_gerrit_server);
        $this->dao->method('save');
        $this->factory->save($this->main_gerrit_server);
    }

    public function testItSetsIdOfNewServerOnSave(): void
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
            ''
        );
        $this->dao->method('save')->willReturn(113);
        $this->system_event_manager->method('queueGerritReplicationKeyUpdate');
        $this->factory->save($new_server);
        self::assertEquals(113, $new_server->getId());
    }

    public function testItDeletesAnExistingServer(): void
    {
        $this->dao->expects($this->once())->method('delete')->with($this->alternate_server_id);
        $this->system_event_manager->method('queueGerritReplicationKeyUpdate');
        $this->factory->delete($this->alternate_gerrit_server);
    }

    public function testItTriggersKeyUpdateEventOnDelete(): void
    {
        $this->system_event_manager->expects($this->once())->method('queueGerritReplicationKeyUpdate')->with($this->alternate_gerrit_server);
        $this->dao->method('delete');
        $this->factory->delete($this->alternate_gerrit_server);
    }

    public function testItDoesNotDeleteUsedServer(): void
    {
        $this->dao->expects($this->never())->method('delete')->with($this->server_id);
        $this->factory->delete($this->main_gerrit_server);
    }
}
