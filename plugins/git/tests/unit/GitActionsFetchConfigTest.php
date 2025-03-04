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

use Codendi_Request;
use Git;
use Git_Driver_Gerrit;
use Git_Driver_Gerrit_Exception;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreator;
use Git_Driver_Gerrit_Template_TemplateFactory;
use Git_Driver_Gerrit_UserAccountManager;
use Git_GitRepositoryUrlManager;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use Git_SystemEventManager;
use GitActions;
use GitPermissionsManager;
use GitPlugin;
use GitRepository;
use GitRepositoryFactory;
use GitRepositoryManager;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
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
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitActionsFetchConfigTest extends TestCase
{
    use GlobalResponseMock;

    private GitActions $actions;
    private int $project_id;
    private Project $project;
    private int $repo_id;
    private GitRepository $repo;
    private PFUser&MockObject $user;
    private Git_Driver_Gerrit&MockObject $driver;
    private GitRepositoryFactory&MockObject $factory;
    private Git_Driver_Gerrit_ProjectCreator&MockObject $project_creator;
    private GitPermissionsManager&MockObject $git_permissions_manager;

    protected function setUp(): void
    {
        $this->project_id = 458;
        $this->project    = ProjectTestBuilder::aProject()->withId($this->project_id)->build();
        $this->repo_id    = 14;
        $this->repo       = GitRepositoryTestBuilder::aProjectRepository()->withId($this->repo_id)->inProject($this->project)->build();
        $this->user       = $this->createMock(PFUser::class);

        $controller   = $this->createMock(Git::class);
        $this->driver = $this->createMock(Git_Driver_Gerrit::class);

        $gerrit_server = $this->createMock(Git_RemoteServer_GerritServer::class);
        $gerrit_server->method('getCloneSSHUrl');

        $gerrit_server_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $gerrit_server_factory->method('getServerById')->willReturn($gerrit_server);

        $this->factory = $this->createMock(GitRepositoryFactory::class);
        $this->factory->method('getRepositoryById')->willReturnCallback(fn(int $id) => match ($id) {
            14      => $this->repo,
            default => null,
        });

        $this->project_creator         = $this->createMock(Git_Driver_Gerrit_ProjectCreator::class);
        $this->git_permissions_manager = $this->createMock(GitPermissionsManager::class);

        $controller->method('getRequest')->willReturn(new Codendi_Request([]));
        $controller->method('getUser')->willReturn($this->user);

        $git_plugin = $this->createMock(GitPlugin::class);
        $git_plugin->method('areFriendlyUrlsActivated')->willReturn(false);

        $server_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $server_factory->method('getDriver')->willReturn($this->driver);

        $this->actions = new GitActions(
            $controller,
            $this->createMock(Git_SystemEventManager::class),
            $this->factory,
            $this->createMock(GitRepositoryManager::class),
            $gerrit_server_factory,
            $server_factory,
            $this->createMock(Git_Driver_Gerrit_UserAccountManager::class),
            $this->project_creator,
            $this->createMock(Git_Driver_Gerrit_Template_TemplateFactory::class),
            $this->createMock(ProjectManager::class),
            $this->git_permissions_manager,
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

    public function testItReturnsAnErrorIfRepoDoesNotExist(): void
    {
        $this->factory->method('getRepositoryById')->willReturn(null);
        $repo_id = 458;

        $GLOBALS['Response']->expects(self::once())->method('sendStatusCode')->with(404);

        $this->actions->fetchGitConfig($repo_id, $this->user, $this->project);
    }

    public function testItReturnsAnErrorIfRepoDoesNotBelongToProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withId($this->project_id + 1)->build();

        $GLOBALS['Response']->expects(self::once())->method('sendStatusCode')->with(403);

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $project);
    }

    public function testItReturnsAnErrorIfUserIsNotProjectAdmin(): void
    {
        $this->user->method('isAdmin')->with($this->project_id)->willReturn(false);
        $this->git_permissions_manager->method('userIsGitAdmin')->willReturn(false);
        $this->repo->setRemoteServerId(1);
        $GLOBALS['Response']->expects(self::once())->method('sendStatusCode')->with(401);

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }

    public function testItReturnsAnErrorIfRepoIsNotMigratedToGerrit(): void
    {
        $this->user->method('isAdmin')->with($this->project_id)->willReturn(true);
        $GLOBALS['Response']->expects(self::once())->method('sendStatusCode')->with(500);

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }

    public function testItReturnsAnErrorIfRepoIsGerritServerIsDown(): void
    {
        $this->git_permissions_manager->method('userIsGitAdmin')->willReturn(true);
        $this->repo->setRemoteServerId(1);
        $this->project_creator->method('getGerritConfig')->willThrowException(new Git_Driver_Gerrit_Exception());
        $this->project_creator->method('removeTemporaryDirectory');
        $GLOBALS['Response']->expects(self::once())->method('sendStatusCode')->with(500);
        $this->driver->method('getGerritProjectName');

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }
}
