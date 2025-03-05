<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

namespace Tuleap\Git;

use Git;
use Git_Backend_Gitolite;
use Git_Driver_Gerrit;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreator;
use Git_Driver_Gerrit_Template_TemplateFactory;
use Git_Driver_Gerrit_UserAccountManager;
use Git_GitRepositoryUrlManager;
use Git_RemoteServer_GerritServerFactory;
use Git_SystemEventManager;
use GitActions;
use GitPermissionsManager;
use GitPlugin;
use GitRepository;
use GitRepositoryFactory;
use GitRepositoryManager;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use ProjectManager;
use Psr\Log\NullLogger;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Git\Permissions\FineGrainedPermissionSaver;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\FineGrainedUpdater;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\Permissions\PermissionChangesDetector;
use Tuleap\Git\Permissions\RegexpFineGrainedDisabler;
use Tuleap\Git\Permissions\RegexpFineGrainedEnabler;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpPermissionFilter;
use Tuleap\Git\RemoteServer\Gerrit\MigrationHandler;
use Tuleap\Git\RemoteServer\GerritCanMigrateChecker;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitActionsDeleteTest extends TestCase
{
    private GitActions $git_actions;
    private int $project_id;
    private int $repository_id;
    private GitRepository&MockObject $repository;
    private Git_SystemEventManager&MockObject $git_system_event_manager;
    private Git&MockObject $controller;

    protected function setUp(): void
    {
        $this->project_id    = 101;
        $this->repository_id = 69;

        $this->repository = $this->createMock(GitRepository::class);
        $this->repository->method('getId')->willReturn($this->repository_id);
        $this->repository->method('getProjectId')->willReturn($this->project_id);
        $this->repository->method('getName');
        $this->repository->method('getFullName');

        $this->git_system_event_manager = $this->createMock(Git_SystemEventManager::class);

        $this->controller = $this->createMock(Git::class);
        $git_plugin       = $this->createMock(GitPlugin::class);
        $this->controller->method('getPlugin')->willReturn($git_plugin);

        $this->controller->method('getUser');
        $this->controller->method('getRequest');

        $git_repository_factory = $this->createMock(GitRepositoryFactory::class);
        $git_repository_factory->method('getRepositoryById')->with($this->repository_id)->willReturn($this->repository);

        $git_plugin->method('areFriendlyUrlsActivated')->willReturn(false);

        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $driver_factory->method('getDriver')->willReturn($this->createMock(Git_Driver_Gerrit::class));
        $history_dao       = $this->createMock(ProjectHistoryDao::class);
        $this->git_actions = new GitActions(
            $this->controller,
            $this->git_system_event_manager,
            $git_repository_factory,
            $this->createMock(GitRepositoryManager::class),
            $this->createMock(Git_RemoteServer_GerritServerFactory::class),
            $driver_factory,
            $this->createMock(Git_Driver_Gerrit_UserAccountManager::class),
            $this->createMock(Git_Driver_Gerrit_ProjectCreator::class),
            $this->createMock(Git_Driver_Gerrit_Template_TemplateFactory::class),
            $this->createMock(ProjectManager::class),
            $this->createMock(GitPermissionsManager::class),
            $url_manager,
            new NullLogger(),
            $history_dao,
            $this->createMock(MigrationHandler::class),
            $this->createMock(GerritCanMigrateChecker::class),
            $this->createMock(FineGrainedUpdater::class),
            $this->createMock(FineGrainedPermissionSaver::class),
            $this->createMock(FineGrainedRetriever::class),
            $this->createMock(HistoryValueFormatter::class),
            $this->createMock(PermissionChangesDetector::class),
            $this->createMock(RegexpFineGrainedEnabler::class),
            $this->createMock(RegexpFineGrainedDisabler::class),
            $this->createMock(RegexpPermissionFilter::class),
            $this->createMock(RegexpFineGrainedRetriever::class),
            $this->createMock(UsersToNotifyDao::class),
            $this->createMock(UgroupsToNotifyDao::class),
            $this->createMock(UGroupManager::class)
        );
        $history_dao->method('groupAddHistory');
    }

    public function testItMarksRepositoryAsDeleted(): void
    {
        $this->controller->method('addInfo');
        $this->controller->expects(self::once())->method('redirect');

        $this->repository->method('canBeDeleted')->willReturn(true);
        $this->repository->expects(self::once())->method('markAsDeleted');
        $this->git_system_event_manager->method('queueRepositoryDeletion');

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function testItTriggersASystemEventForPhysicalRemove(): void
    {
        $this->controller->method('addInfo');
        $this->controller->expects(self::once())->method('redirect');

        $this->repository->method('canBeDeleted')->willReturn(true);
        $this->repository->method('markAsDeleted');
        $this->repository->method('getBackend')->willReturn($this->createMock(Git_Backend_Gitolite::class));

        $this->git_system_event_manager->expects(self::once())->method('queueRepositoryDeletion')->with($this->repository);

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function testItDoesntDeleteWhenRepositoryCannotBeDeleted(): void
    {
        $this->controller->method('addError');
        $this->controller->expects(self::once())->method('redirect');

        $this->repository->method('canBeDeleted')->willReturn(false);
        $this->repository->expects(self::never())->method('markAsDeleted');
        $this->repository->method('getRelativeHTTPUrl');

        $this->git_system_event_manager->expects(self::never())->method('queueRepositoryDeletion');

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }
}
