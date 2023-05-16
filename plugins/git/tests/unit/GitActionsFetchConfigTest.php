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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\GlobalResponseMock;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitActionsFetchConfigTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    /**
     * @var GitActions
     */
    private $actions;
    private int $project_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project|(Project&\Mockery\LegacyMockInterface)|(Project&\Mockery\MockInterface)
     */
    private Project|\Mockery\MockInterface|\Mockery\LegacyMockInterface $project;
    private int $repo_id;
    /**
     * @var GitRepository&\Mockery\MockInterface
     */
    private $repo;
    /**
     * @var \PFUser&\Mockery\MockInterface
     */
    private $user;
    /**
     * @var Codendi_Request&\Mockery\MockInterface
     */
    private $request;
    /**
     * @var Git_SystemEventManager&\Mockery\MockInterface
     */
    private $system_event_manager;
    /**
     * @var Git&\Mockery\MockInterface
     */
    private $controller;
    /**
     * @var Git_Driver_Gerrit&\Mockery\MockInterface
     */
    private $driver;
    /**
     * @var Git_RemoteServer_GerritServerFactory&\Mockery\MockInterface
     */
    private $gerrit_server_factory;
    /**
     * @var \Mockery\MockInterface&GitRepositoryFactory
     */
    private $factory;
    /**
     * @var Git_Driver_Gerrit_ProjectCreator&\Mockery\MockInterface
     */
    private $project_creator;
    /**
     * @var GitPermissionsManager&\Mockery\MockInterface
     */
    private $git_permissions_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_id = 458;
        $this->project    = \Mockery::spy(\Project::class);
        $this->project->shouldReceive('getId')->andReturns($this->project_id);

        $this->repo_id = 14;
        $this->repo    = \Mockery::spy(\GitRepository::class);
        $this->repo->shouldReceive('getId')->andReturns($this->repo_id);
        $this->repo->shouldReceive('belongsToProject')->with($this->project)->andReturns(true);

        $this->user = \Mockery::spy(\PFUser::class);

        $this->request              = \Mockery::spy(\Codendi_Request::class);
        $this->system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->controller           = \Mockery::spy(\Git::class);
        $this->driver               = \Mockery::spy(\Git_Driver_Gerrit::class);

        $gerrit_server = \Mockery::spy(\Git_RemoteServer_GerritServer::class);

        $this->gerrit_server_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->gerrit_server_factory->shouldReceive('getServerById')->andReturns($gerrit_server);

        $this->factory = Mockery::mock(\GitRepositoryFactory::class)
            ->shouldReceive('getRepositoryById')
            ->with(14)
            ->andReturn($this->repo)
            ->getMock();

        $this->project_creator         = \Mockery::spy(\Git_Driver_Gerrit_ProjectCreator::class);
        $this->git_permissions_manager = \Mockery::spy(\GitPermissionsManager::class);

        $this->controller->shouldReceive('getRequest')->andReturns($this->request);

        $git_plugin = Mockery::mock(\GitPlugin::class)
            ->shouldReceive('areFriendlyUrlsActivated')
            ->andReturnFalse()
            ->getMock();

        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $this->actions = new GitActions(
            $this->controller,
            $this->system_event_manager,
            $this->factory,
            \Mockery::spy(\GitRepositoryManager::class),
            $this->gerrit_server_factory,
            Mockery::mock(\Git_Driver_Gerrit_GerritDriverFactory::class)
                ->shouldReceive('getDriver')
                ->andReturn($this->driver)
                ->getMock(),
            \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class),
            $this->project_creator,
            \Mockery::spy(\Git_Driver_Gerrit_Template_TemplateFactory::class),
            \Mockery::spy(\ProjectManager::class),
            $this->git_permissions_manager,
            $url_manager,
            \Mockery::spy(\Psr\Log\LoggerInterface::class),
            \Mockery::spy(\ProjectHistoryDao::class),
            \Mockery::spy(\Tuleap\Git\RemoteServer\Gerrit\MigrationHandler::class),
            \Mockery::spy(\Tuleap\Git\RemoteServer\GerritCanMigrateChecker::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedUpdater::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionSaver::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            \Mockery::spy(\Tuleap\Git\Permissions\HistoryValueFormatter::class),
            \Mockery::spy(\Tuleap\Git\Permissions\PermissionChangesDetector::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedEnabler::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedDisabler::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpPermissionFilter::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            \Mockery::spy(\Tuleap\Git\Notifications\UsersToNotifyDao::class),
            \Mockery::spy(\Tuleap\Git\Notifications\UgroupsToNotifyDao::class),
            \Mockery::spy(\UGroupManager::class)
        );
    }

    public function testItReturnsAnErrorIfRepoDoesNotExist(): void
    {
        $this->factory->shouldReceive('getRepositoryById')->andReturns(null);
        $repo_id = 458;

        $GLOBALS['Response']->expects(self::once())->method('sendStatusCode')->with(404);

        $this->actions->fetchGitConfig($repo_id, $this->user, $this->project);
    }

    public function testItReturnsAnErrorIfRepoDoesNotBelongToProject(): void
    {
        $project = \Mockery::spy(\Project::class);
        $this->repo->shouldReceive('belongsToProject')->with($project)->andReturns(false);

        $GLOBALS['Response']->expects(self::once())->method('sendStatusCode')->with(403);

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $project);
    }

    public function testItReturnsAnErrorIfUserIsNotProjectAdmin(): void
    {
        $this->user->shouldReceive('isAdmin')->with($this->project_id)->andReturns(false);
        $this->repo->shouldReceive('isMigratedToGerrit')->andReturns(true);
        $GLOBALS['Response']->expects(self::once())->method('sendStatusCode')->with(401);

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }

    public function testItReturnsAnErrorIfRepoIsNotMigratedToGerrit(): void
    {
        $this->user->shouldReceive('isAdmin')->with($this->project_id)->andReturns(true);
        $this->repo->shouldReceive('isMigratedToGerrit')->andReturns(false);
        $GLOBALS['Response']->expects(self::once())->method('sendStatusCode')->with(500);

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }

    public function testItReturnsAnErrorIfRepoIsGerritServerIsDown(): void
    {
        $this->git_permissions_manager->shouldReceive('userIsGitAdmin')->andReturns(true);
        $this->repo->shouldReceive('isMigratedToGerrit')->andReturns(true);
        $this->project_creator->shouldReceive('getGerritConfig')->andThrows(new Git_Driver_Gerrit_Exception());
        $GLOBALS['Response']->expects(self::once())->method('sendStatusCode')->with(500);

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }
}
