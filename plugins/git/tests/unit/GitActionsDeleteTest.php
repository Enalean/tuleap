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
use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use ProjectManager;
use Psr\Log\NullLogger;
use Tuleap\Git\AsynchronousEvents\GitRepositoryChangeTask;
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
use Tuleap\Test\Stubs\EnqueueTaskStub;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitActionsDeleteTest extends TestCase
{
    private GitActions $git_actions;
    private int $project_id;
    private int $repository_id;
    private GitRepository&MockObject $repository;
    private Git&MockObject $controller;
    private EnqueueTaskStub $enqueuer;

    #[\Override]
    protected function setUp(): void
    {
        $this->project_id    = 101;
        $this->repository_id = 69;

        $this->repository = $this->createMock(GitRepository::class);
        $this->repository->method('getId')->willReturn($this->repository_id);
        $this->repository->method('getProjectId')->willReturn($this->project_id);
        $this->repository->method('getName');
        $this->repository->method('getFullName');

        $this->controller = $this->createMock(Git::class);
        $git_plugin       = $this->createStub(GitPlugin::class);
        $this->controller->method('getPlugin')->willReturn($git_plugin);

        $this->controller->method('getUser');
        $this->controller->method('getRequest');

        $git_repository_factory = $this->createStub(GitRepositoryFactory::class);
        $git_repository_factory->method('getRepositoryById')->with($this->repository_id)->willReturn($this->repository);

        $git_plugin->method('areFriendlyUrlsActivated')->willReturn(false);

        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $this->enqueuer = new EnqueueTaskStub();

        $driver_factory = $this->createStub(Git_Driver_Gerrit_GerritDriverFactory::class);
        $driver_factory->method('getDriver')->willReturn($this->createStub(Git_Driver_Gerrit::class));
        $history_dao       = $this->createStub(ProjectHistoryDao::class);
        $this->git_actions = new GitActions(
            $this->controller,
            $this->createStub(Git_SystemEventManager::class),
            $this->enqueuer,
            $git_repository_factory,
            $this->createMock(Git_RemoteServer_GerritServerFactory::class),
            $driver_factory,
            $this->createStub(Git_Driver_Gerrit_UserAccountManager::class),
            $this->createStub(Git_Driver_Gerrit_ProjectCreator::class),
            $this->createStub(Git_Driver_Gerrit_Template_TemplateFactory::class),
            $this->createStub(ProjectManager::class),
            $this->createStub(GitPermissionsManager::class),
            $url_manager,
            new NullLogger(),
            $history_dao,
            $this->createStub(MigrationHandler::class),
            $this->createStub(GerritCanMigrateChecker::class),
            $this->createStub(FineGrainedUpdater::class),
            $this->createStub(FineGrainedPermissionSaver::class),
            $this->createStub(FineGrainedRetriever::class),
            $this->createStub(HistoryValueFormatter::class),
            $this->createStub(PermissionChangesDetector::class),
            $this->createStub(RegexpFineGrainedEnabler::class),
            $this->createStub(RegexpFineGrainedDisabler::class),
            $this->createStub(RegexpPermissionFilter::class),
            $this->createStub(RegexpFineGrainedRetriever::class),
            $this->createStub(UsersToNotifyDao::class),
            $this->createStub(UgroupsToNotifyDao::class),
            $this->createStub(UGroupManager::class)
        );
        $history_dao->method('groupAddHistory');
    }

    public function testItMarksRepositoryAsDeleted(): void
    {
        $this->controller->method('addInfo');
        $this->controller->expects($this->once())->method('redirect');

        $this->repository->method('canBeDeleted')->willReturn(true);
        $this->repository->expects($this->once())->method('markAsDeleted');

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function testItTriggersAnEventForPhysicalRemove(): void
    {
        $this->controller->method('addInfo');
        $this->controller->expects($this->once())->method('redirect');

        $this->repository->method('canBeDeleted')->willReturn(true);
        $this->repository->method('markAsDeleted');

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);

        self::assertEquals([GitRepositoryChangeTask::fromRepository($this->repository)], $this->enqueuer->queued_tasks);
    }

    public function testItDoesntDeleteWhenRepositoryCannotBeDeleted(): void
    {
        $this->controller->method('addError');
        $this->controller->expects($this->once())->method('redirect');

        $this->repository->method('canBeDeleted')->willReturn(false);
        $this->repository->expects($this->never())->method('markAsDeleted');
        $this->repository->method('getRelativeHTTPUrl');

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);

        self::assertEmpty($this->enqueuer->queued_tasks);
    }
}
