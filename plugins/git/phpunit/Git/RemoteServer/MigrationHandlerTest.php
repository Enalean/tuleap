<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use GitRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Git_SystemEventManager;
use Git_RemoteServer_NotFoundException;

require_once __DIR__ . '/../../bootstrap.php';

class MigrationHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    public function setUp(): void
    {
        parent::setUp();

        $this->git_system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->server_factory           = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->driver_factory           = \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class);
        $project_history_dao            = \Mockery::spy(\ProjectHistoryDao::class);
        $this->project_creator_status   = \Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class);
        $this->project_manager          = \Mockery::spy(\ProjectManager::class);

        $this->handler = new MigrationHandler(
            $this->git_system_event_manager,
            $this->server_factory,
            $this->driver_factory,
            $project_history_dao,
            $this->project_creator_status,
            $this->project_manager
        );

        $this->user   = \Mockery::spy(\PFUser::class);
        $this->server = $this->buildMockedRepository(1);
    }

    private function buildMockedRepository(int $id): GitRepository
    {
        $repositrory = Mockery::mock(GitRepository::class);
        $repositrory->shouldReceive('getId')->andReturn($id);

        return $repositrory;
    }

    public function testItThrowsAnExceptionIfRepositoryCannotBeMigrated(): void
    {
        $repository = \Mockery::spy(\GitRepository::class)->shouldReceive('canMigrateToGerrit')->andReturns(false)->getMock();
        $this->project_manager->shouldReceive('getParentProject')->andReturns(null);
        $repository->shouldReceive('getProject')->andReturns(\Mockery::spy(Project::class));

        $remote_server_id   = 1;
        $gerrit_template_id = "none";

        $this->expectException('Tuleap\Git\Exceptions\RepositoryCannotBeMigratedException');
        $this->git_system_event_manager->shouldReceive('queueMigrateToGerrit')->never();

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItThrowsAnExceptionIfRepositoryIsAlreadyInQueueForMigration(): void
    {
        $repository = \Mockery::spy(\GitRepository::class)->shouldReceive('canMigrateToGerrit')->andReturns(true)->getMock();
        $repository->shouldReceive('getProject')->andReturns(\Mockery::spy(Project::class));

        $remote_server_id   = 1;
        $gerrit_template_id = "none";

        $this->project_creator_status->shouldReceive('getStatus')->andReturns('QUEUE');

        $this->expectException('Tuleap\Git\Exceptions\RepositoryAlreadyInQueueForMigrationException');
        $this->git_system_event_manager->shouldReceive('queueMigrateToGerrit')->never();

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItThrowsAnExceptionIfRepositoryWillBeMigratedIntoARestrictedGerritServer(): void
    {
        $repository = \Mockery::spy(\GitRepository::class)->shouldReceive('canMigrateToGerrit')->andReturns(true)->getMock();
        $this->project_manager->shouldReceive('getParentProject')->andReturns(null);
        $repository->shouldReceive('getProject')->andReturns(\Mockery::spy(Project::class));

        $remote_server_id   = 1;
        $gerrit_template_id = "none";

        $this->server_factory->shouldReceive('getServerById')->with(1)->andReturns($this->server);
        $this->server_factory->shouldReceive('getAvailableServersForProject')->andReturns(array());

        $this->expectException('Tuleap\Git\Exceptions\RepositoryCannotBeMigratedOnRestrictedGerritServerException');
        $this->git_system_event_manager->shouldReceive('queueMigrateToGerrit')->never();

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItThrowsAnExceptionIfParentProjectIsNotActive(): void
    {
        $repository = \Mockery::spy(\GitRepository::class)->shouldReceive('canMigrateToGerrit')->andReturns(true)->getMock();
        $project = \Mockery::spy(Project::class);
        $this->project_manager->shouldReceive('getParentProject')->andReturns($project);
        $project->shouldReceive('isActive')->andReturns(false);
        $repository->shouldReceive('getProject')->andReturns(\Mockery::spy(Project::class));

        $remote_server_id   = 1;
        $gerrit_template_id = "none";

        $this->server_factory->shouldReceive('getServerById')->with(1)->andReturns($this->server);
        $this->server_factory->shouldReceive('getAvailableServersForProject')->andReturns(array());

        $this->expectException('Tuleap\Git\Exceptions\RepositoryNotMigratedException');
        $this->git_system_event_manager->shouldReceive('queueMigrateToGerrit')->never();

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItMigratesRepositoryWhenParentIsActive(): void
    {
        $repository = \Mockery::spy(\GitRepository::class)->shouldReceive('canMigrateToGerrit')->andReturns(true)->getMock();
        $project = \Mockery::spy(Project::class);
        $this->project_manager->shouldReceive('getParentProject')->andReturns($project);
        $project->shouldReceive('isActive')->andReturns(true);
        $repository->shouldReceive('getProject')->andReturns(\Mockery::spy(Project::class));

        $remote_server_id   = 1;
        $gerrit_template_id = "none";

        $this->server_factory->shouldReceive('getServerById')->with(1)->andReturns($this->server);
        $this->server_factory->shouldReceive('getAvailableServersForProject')->andReturns(array(1 => $this->server));

        $this->git_system_event_manager->shouldReceive('queueMigrateToGerrit')->once();

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItMigratesRepository(): void
    {
        $repository = \Mockery::spy(\GitRepository::class)->shouldReceive('canMigrateToGerrit')->andReturns(true)->getMock();
        $this->project_manager->shouldReceive('getParentProject')->andReturns(null);
        $repository->shouldReceive('getProject')->andReturns(\Mockery::spy(Project::class));

        $remote_server_id   = 1;
        $gerrit_template_id = "none";

        $this->server_factory->shouldReceive('getServerById')->with(1)->andReturns($this->server);
        $this->server_factory->shouldReceive('getAvailableServersForProject')->andReturns(array(1 => $this->server));

        $this->git_system_event_manager->shouldReceive('queueMigrateToGerrit')->once();

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItDoesNothingWhenServerDoesNotExist(): void
    {
        $repository         = \Mockery::spy(\GitRepository::class)->shouldReceive('canMigrateToGerrit')->andReturns(true)->getMock();
        $remote_server_id   = 1;
        $gerrit_template_id = "none";

        $this->server_factory->shouldReceive('getServerById')->andThrows(new Git_RemoteServer_NotFoundException($remote_server_id));

        $this->expectException('Git_RemoteServer_NotFoundException');
        $this->git_system_event_manager->shouldReceive('queueMigrateToGerrit')->never();

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItThrowsAnExceptionIfRepositoryIsNotMigrated(): void
    {
        $repository        = \Mockery::spy(\GitRepository::class)->shouldReceive('isMigratedToGerrit')->andReturns(false)->getMock();
        $disconnect_option = '';

        $this->expectException('Tuleap\Git\Exceptions\RepositoryNotMigratedException');

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function testItDisconnectsWithoutOptionsIfTheRemoteServerDoesNotExist(): void
    {
        $backend           = \Mockery::spy(\Git_Backend_Gitolite::class)->shouldReceive('disconnectFromGerrit')->andReturns(true)->getMock();
        $repository        = \Mockery::spy(\GitRepository::class)->shouldReceive('isMigratedToGerrit')->andReturns(true)->getMock();
        $disconnect_option = '';

        $repository->shouldReceive('getBackend')->andReturns($backend);

        $this->git_system_event_manager->shouldReceive('queueRepositoryUpdate')->once();
        $this->git_system_event_manager->shouldReceive('queueRemoteProjectDeletion')->never();
        $this->git_system_event_manager->shouldReceive('queueRemoteProjectReadOnly')->never();

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function testItDisconnectsWithtEmptyOption(): void
    {
        $backend           = \Mockery::spy(\Git_Backend_Gitolite::class)->shouldReceive('disconnectFromGerrit')->andReturns(true)->getMock();
        $repository        = \Mockery::spy(\GitRepository::class)->shouldReceive('isMigratedToGerrit')->andReturns(true)->getMock();
        $server            = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $driver            = \Mockery::spy(\Git_Driver_Gerrit::class);
        $disconnect_option = '';

        $repository->shouldReceive('getBackend')->andReturns($backend);
        $this->server_factory->shouldReceive('getServerById')->andReturns($server);
        $this->driver_factory->shouldReceive('getDriver')->with($server)->andReturns($driver);

        $this->git_system_event_manager->shouldReceive('queueRepositoryUpdate')->once();
        $this->git_system_event_manager->shouldReceive('queueRemoteProjectDeletion')->never();
        $this->git_system_event_manager->shouldReceive('queueRemoteProjectReadOnly')->never();

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function testItDisconnectsWithtReadOnlyOption(): void
    {
        $backend           = \Mockery::spy(\Git_Backend_Gitolite::class)->shouldReceive('disconnectFromGerrit')->andReturns(true)->getMock();
        $repository        = \Mockery::spy(\GitRepository::class)->shouldReceive('isMigratedToGerrit')->andReturns(true)->getMock();
        $server            = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $driver            = \Mockery::spy(\Git_Driver_Gerrit::class);
        $disconnect_option = 'read-only';

        $repository->shouldReceive('getBackend')->andReturns($backend);
        $this->server_factory->shouldReceive('getServerById')->andReturns($server);
        $this->driver_factory->shouldReceive('getDriver')->with($server)->andReturns($driver);

        $this->git_system_event_manager->shouldReceive('queueRepositoryUpdate')->once();
        $this->git_system_event_manager->shouldReceive('queueRemoteProjectDeletion')->never();
        $this->git_system_event_manager->shouldReceive('queueRemoteProjectReadOnly')->once();

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function testItDisconnectsWithtDeleteOption(): void
    {
        $backend           = \Mockery::spy(\Git_Backend_Gitolite::class)->shouldReceive('disconnectFromGerrit')->andReturns(true)->getMock();
        $repository        = \Mockery::spy(\GitRepository::class)->shouldReceive('isMigratedToGerrit')->andReturns(true)->getMock();
        $server            = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $driver            = \Mockery::spy(\Git_Driver_Gerrit::class);
        $disconnect_option = 'delete';

        $driver->shouldReceive('isDeletePluginEnabled')->with($server)->andReturns(true);
        $repository->shouldReceive('getBackend')->andReturns($backend);
        $this->server_factory->shouldReceive('getServerById')->andReturns($server);
        $this->driver_factory->shouldReceive('getDriver')->with($server)->andReturns($driver);

        $this->git_system_event_manager->shouldReceive('queueRepositoryUpdate')->once();
        $this->git_system_event_manager->shouldReceive('queueRemoteProjectDeletion')->once();
        $this->git_system_event_manager->shouldReceive('queueRemoteProjectReadOnly')->never();

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function testItThrowsAnExceptionIfDeletePluginNotInstalled(): void
    {
        $backend           = \Mockery::spy(\Git_Backend_Gitolite::class)->shouldReceive('disconnectFromGerrit')->andReturns(true)->getMock();
        $repository        = \Mockery::spy(\GitRepository::class)->shouldReceive('isMigratedToGerrit')->andReturns(true)->getMock();
        $server            = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $driver            = \Mockery::spy(\Git_Driver_Gerrit::class);
        $disconnect_option = 'delete';

        $driver->shouldReceive('isDeletePluginEnabled')->with($server)->andReturns(false);
        $repository->shouldReceive('getBackend')->andReturns($backend);
        $this->server_factory->shouldReceive('getServerById')->andReturns($server);
        $this->driver_factory->shouldReceive('getDriver')->with($server)->andReturns($driver);

        $this->git_system_event_manager->shouldReceive('queueRepositoryUpdate')->never();
        $this->git_system_event_manager->shouldReceive('queueRemoteProjectDeletion')->never();
        $this->git_system_event_manager->shouldReceive('queueRemoteProjectReadOnly')->never();

        $this->expectException('Tuleap\Git\Exceptions\DeletePluginNotInstalledException');

        $this->handler->disconnect($repository, $disconnect_option);
    }
}
