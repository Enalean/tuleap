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

declare(strict_types=1);

namespace Tuleap\Git\RemoteServer;

use Git_Backend_Gitolite;
use Git_Driver_Gerrit;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use Git_RemoteServer_NotFoundException;
use Git_SystemEventManager;
use GitRepository;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use ProjectManager;
use Tuleap\Git\Exceptions\DeletePluginNotInstalledException;
use Tuleap\Git\Exceptions\RepositoryAlreadyInQueueForMigrationException;
use Tuleap\Git\Exceptions\RepositoryCannotBeMigratedException;
use Tuleap\Git\Exceptions\RepositoryCannotBeMigratedOnRestrictedGerritServerException;
use Tuleap\Git\Exceptions\RepositoryNotMigratedException;
use Tuleap\Git\RemoteServer\Gerrit\MigrationHandler;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MigrationHandlerTest extends TestCase
{
    private Git_SystemEventManager&MockObject $git_system_event_manager;
    private MigrationHandler $handler;
    private Git_RemoteServer_GerritServerFactory&MockObject $server_factory;
    private Git_Driver_Gerrit_GerritDriverFactory&MockObject $driver_factory;
    private Git_Driver_Gerrit_ProjectCreatorStatus&MockObject $project_creator_status;
    private ProjectManager&MockObject $project_manager;
    private PFUser $user;
    private GitRepository $repository;

    public function setUp(): void
    {
        $this->git_system_event_manager = $this->createMock(Git_SystemEventManager::class);
        $this->server_factory           = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->driver_factory           = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $project_history_dao            = $this->createMock(ProjectHistoryDao::class);
        $this->project_creator_status   = $this->createMock(Git_Driver_Gerrit_ProjectCreatorStatus::class);
        $this->project_manager          = $this->createMock(ProjectManager::class);

        $this->handler = new MigrationHandler(
            $this->git_system_event_manager,
            $this->server_factory,
            $this->driver_factory,
            $project_history_dao,
            $this->project_creator_status,
            $this->project_manager
        );

        $this->user       = UserTestBuilder::buildWithDefaults();
        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withId(1)->build();
        $project_history_dao->method('groupAddHistory');
    }

    public function testItThrowsAnExceptionIfRepositoryCannotBeMigrated(): void
    {
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject(ProjectTestBuilder::aProject()->build())->migratedToGerrit()->build();
        $this->project_manager->method('getParentProject')->willReturn(null);

        $remote_server_id   = 1;
        $gerrit_template_id = 'none';

        $this->expectException(RepositoryCannotBeMigratedException::class);
        $this->git_system_event_manager->expects(self::never())->method('queueMigrateToGerrit');

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItThrowsAnExceptionIfRepositoryIsAlreadyInQueueForMigration(): void
    {
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject(ProjectTestBuilder::aProject()->build())->build();

        $remote_server_id   = 1;
        $gerrit_template_id = 'none';

        $this->project_creator_status->method('getStatus')->willReturn(Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE);

        $this->expectException(RepositoryAlreadyInQueueForMigrationException::class);
        $this->git_system_event_manager->expects(self::never())->method('queueMigrateToGerrit');

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItThrowsAnExceptionIfRepositoryWillBeMigratedIntoARestrictedGerritServer(): void
    {
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject(ProjectTestBuilder::aProject()->build())->build();
        $this->project_manager->method('getParentProject')->willReturn(null);

        $remote_server_id   = 1;
        $gerrit_template_id = 'none';

        $this->server_factory->method('getServerById')->with(1)->willReturn($this->repository);
        $this->server_factory->method('getAvailableServersForProject')->willReturn([]);
        $this->project_creator_status->method('getStatus');

        $this->expectException(RepositoryCannotBeMigratedOnRestrictedGerritServerException::class);
        $this->git_system_event_manager->expects(self::never())->method('queueMigrateToGerrit');

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItThrowsAnExceptionIfParentProjectIsNotActive(): void
    {
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject(ProjectTestBuilder::aProject()->build())->build();
        $project    = ProjectTestBuilder::aProject()->withStatusSuspended()->build();
        $this->project_manager->method('getParentProject')->willReturn($project);

        $remote_server_id   = 1;
        $gerrit_template_id = 'none';

        $this->server_factory->method('getServerById')->with(1)->willReturn($this->repository);
        $this->server_factory->method('getAvailableServersForProject')->willReturn([]);
        $this->project_creator_status->method('getStatus');

        $this->expectException(RepositoryNotMigratedException::class);
        $this->git_system_event_manager->expects(self::never())->method('queueMigrateToGerrit');

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItMigratesRepositoryWhenParentIsActive(): void
    {
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject(ProjectTestBuilder::aProject()->build())->build();
        $project    = ProjectTestBuilder::aProject()->build();
        $this->project_manager->method('getParentProject')->willReturn($project);

        $remote_server_id   = 1;
        $gerrit_template_id = 'none';

        $this->server_factory->method('getServerById')->with(1)->willReturn($this->repository);
        $this->server_factory->method('getAvailableServersForProject')->willReturn([1 => $this->repository]);
        $this->project_creator_status->method('getStatus');

        $this->git_system_event_manager->expects(self::once())->method('queueMigrateToGerrit');

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItMigratesRepository(): void
    {
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject(ProjectTestBuilder::aProject()->build())->build();
        $this->project_manager->method('getParentProject')->willReturn(null);

        $remote_server_id   = 1;
        $gerrit_template_id = 'none';

        $this->server_factory->method('getServerById')->with(1)->willReturn($this->repository);
        $this->server_factory->method('getAvailableServersForProject')->willReturn([1 => $this->repository]);
        $this->project_creator_status->method('getStatus');

        $this->git_system_event_manager->expects(self::once())->method('queueMigrateToGerrit');

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItDoesNothingWhenServerDoesNotExist(): void
    {
        $repository         = GitRepositoryTestBuilder::aProjectRepository()->inProject(ProjectTestBuilder::aProject()->build())->build();
        $remote_server_id   = 1;
        $gerrit_template_id = 'none';

        $this->server_factory->method('getServerById')->willThrowException(new Git_RemoteServer_NotFoundException($remote_server_id));
        $this->project_creator_status->method('getStatus');
        $this->project_manager->method('getParentProject');

        $this->expectException(Git_RemoteServer_NotFoundException::class);
        $this->git_system_event_manager->expects(self::never())->method('queueMigrateToGerrit');

        $this->handler->migrate($repository, $remote_server_id, $gerrit_template_id, $this->user);
    }

    public function testItThrowsAnExceptionIfRepositoryIsNotMigrated(): void
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->method('isMigratedToGerrit')->willReturn(false);
        $disconnect_option = '';

        $this->server_factory->method('getServerById');

        $this->expectException(RepositoryNotMigratedException::class);

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function testItDisconnectsWithoutOptionsIfTheRemoteServerDoesNotExist(): void
    {
        $backend = $this->createMock(Git_Backend_Gitolite::class);
        $backend->method('disconnectFromGerrit')->willReturn(true);
        $repository = $this->createMock(GitRepository::class);
        $repository->method('isMigratedToGerrit')->willReturn(true);
        $repository->method('getBackend')->willReturn($backend);
        $repository->method('getRemoteServerId');
        $disconnect_option = '';

        $this->server_factory->method('getServerById');
        $this->git_system_event_manager->expects(self::once())->method('queueRepositoryUpdate');
        $this->git_system_event_manager->expects(self::never())->method('queueRemoteProjectDeletion');
        $this->git_system_event_manager->expects(self::never())->method('queueRemoteProjectReadOnly');

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function testItDisconnectsWithtEmptyOption(): void
    {
        $backend = $this->createMock(Git_Backend_Gitolite::class);
        $backend->method('disconnectFromGerrit')->willReturn(true);
        $repository = $this->createMock(GitRepository::class);
        $repository->method('isMigratedToGerrit')->willReturn(true);
        $server            = $this->createMock(Git_RemoteServer_GerritServer::class);
        $driver            = $this->createMock(Git_Driver_Gerrit::class);
        $disconnect_option = '';

        $repository->method('getBackend')->willReturn($backend);
        $repository->method('getRemoteServerId');
        $driver->method('isDeletePluginEnabled');
        $this->server_factory->method('getServerById')->willReturn($server);
        $this->driver_factory->method('getDriver')->with($server)->willReturn($driver);

        $this->git_system_event_manager->expects(self::once())->method('queueRepositoryUpdate');
        $this->git_system_event_manager->expects(self::never())->method('queueRemoteProjectDeletion');
        $this->git_system_event_manager->expects(self::never())->method('queueRemoteProjectReadOnly');

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function testItDisconnectsWithtReadOnlyOption(): void
    {
        $backend = $this->createMock(Git_Backend_Gitolite::class);
        $backend->method('disconnectFromGerrit')->willReturn(true);
        $repository = $this->createMock(GitRepository::class);
        $repository->method('isMigratedToGerrit')->willReturn(true);
        $server            = $this->createMock(Git_RemoteServer_GerritServer::class);
        $driver            = $this->createMock(Git_Driver_Gerrit::class);
        $disconnect_option = 'read-only';

        $repository->method('getBackend')->willReturn($backend);
        $repository->method('getRemoteServerId');
        $repository->method('getName');
        $repository->method('getProjectId');
        $driver->method('isDeletePluginEnabled');
        $this->server_factory->method('getServerById')->willReturn($server);
        $this->driver_factory->method('getDriver')->with($server)->willReturn($driver);

        $this->git_system_event_manager->expects(self::once())->method('queueRepositoryUpdate');
        $this->git_system_event_manager->expects(self::never())->method('queueRemoteProjectDeletion');
        $this->git_system_event_manager->expects(self::once())->method('queueRemoteProjectReadOnly');

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function testItDisconnectsWithtDeleteOption(): void
    {
        $backend = $this->createMock(Git_Backend_Gitolite::class);
        $backend->method('disconnectFromGerrit')->willReturn(true);
        $repository = $this->createMock(GitRepository::class);
        $repository->method('isMigratedToGerrit')->willReturn(true);
        $server            = $this->createMock(Git_RemoteServer_GerritServer::class);
        $driver            = $this->createMock(Git_Driver_Gerrit::class);
        $disconnect_option = 'delete';

        $driver->method('isDeletePluginEnabled')->with($server)->willReturn(true);
        $repository->method('getBackend')->willReturn($backend);
        $repository->method('getRemoteServerId');
        $repository->method('getName');
        $repository->method('getProjectId');
        $this->server_factory->method('getServerById')->willReturn($server);
        $this->driver_factory->method('getDriver')->with($server)->willReturn($driver);

        $this->git_system_event_manager->expects(self::once())->method('queueRepositoryUpdate');
        $this->git_system_event_manager->expects(self::once())->method('queueRemoteProjectDeletion');
        $this->git_system_event_manager->expects(self::never())->method('queueRemoteProjectReadOnly');

        $this->handler->disconnect($repository, $disconnect_option);
    }

    public function testItThrowsAnExceptionIfDeletePluginNotInstalled(): void
    {
        $backend = $this->createMock(Git_Backend_Gitolite::class);
        $backend->method('disconnectFromGerrit')->willReturn(true);
        $repository = $this->createMock(GitRepository::class);
        $repository->method('isMigratedToGerrit')->willReturn(true);
        $server            = $this->createMock(Git_RemoteServer_GerritServer::class);
        $driver            = $this->createMock(Git_Driver_Gerrit::class);
        $disconnect_option = 'delete';

        $driver->method('isDeletePluginEnabled')->with($server)->willReturn(false);
        $repository->method('getBackend')->willReturn($backend);
        $repository->method('getRemoteServerId');
        $this->server_factory->method('getServerById')->willReturn($server);
        $this->driver_factory->method('getDriver')->with($server)->willReturn($driver);

        $this->git_system_event_manager->expects(self::never())->method('queueRepositoryUpdate');
        $this->git_system_event_manager->expects(self::never())->method('queueRemoteProjectDeletion');
        $this->git_system_event_manager->expects(self::never())->method('queueRemoteProjectReadOnly');

        $this->expectException(DeletePluginNotInstalledException::class);

        $this->handler->disconnect($repository, $disconnect_option);
    }
}
