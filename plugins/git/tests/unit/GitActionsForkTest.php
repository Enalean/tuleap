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
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitActionsForkTest extends TestCase
{
    private GitActions $actions;
    private GitRepositoryManager&MockObject $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->manager = $this->createMock(GitRepositoryManager::class);

        $git_plugin = $this->createMock(GitPlugin::class);
        $git_plugin->method('areFriendlyUrlsActivated')->willReturn(false);

        $driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $driver_factory->method('getDriver')->willReturn($this->createMock(Git_Driver_Gerrit::class));
        $controller = $this->createMock(Git::class);
        $controller->method('getUser');
        $controller->method('getRequest');
        $this->actions = new GitActions(
            $controller,
            $this->createMock(Git_SystemEventManager::class),
            $this->createMock(GitRepositoryFactory::class),
            $this->manager,
            $this->createMock(Git_RemoteServer_GerritServerFactory::class),
            $driver_factory,
            $this->createMock(Git_Driver_Gerrit_UserAccountManager::class),
            $this->createMock(Git_Driver_Gerrit_ProjectCreator::class),
            $this->createMock(Git_Driver_Gerrit_Template_TemplateFactory::class),
            $this->createMock(ProjectManager::class),
            $this->createMock(GitPermissionsManager::class),
            new Git_GitRepositoryUrlManager($git_plugin),
            new NullLogger(),
            $this->createMock(ProjectHistoryDao::class),
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
    }

    public function testItDelegatesForkToGitManager(): void
    {
        $repositories    = [
            GitRepositoryTestBuilder::aProjectRepository()->build(),
            GitRepositoryTestBuilder::aProjectRepository()->build(),
        ];
        $to_project      = ProjectTestBuilder::aProject()->build();
        $namespace       = 'namespace';
        $scope           = GitRepository::REPO_SCOPE_INDIVIDUAL;
        $user            = UserTestBuilder::buildWithDefaults();
        $response        = $this->createMock(\Layout::class);
        $redirect_url    = '/stuff';
        $forkPermissions = [];

        $this->manager->expects($this->once())->method('forkRepositories')->with($repositories, $to_project, $user, $namespace, $scope, $forkPermissions);

        $this->actions->fork($repositories, $to_project, $namespace, $scope, $user, $response, $redirect_url, $forkPermissions);
    }
}
