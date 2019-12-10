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

require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/builders/aGitRepository.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitActionsFetchConfigTest extends TuleapTestCase
{
    /**
     * @var GitActions
     */
    private $actions;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->project_id = 458;
        $this->project    = \Mockery::spy(\Project::class);
        stub($this->project)->getId()->returns($this->project_id);

        $this->repo_id = 14;
        $this->repo    = \Mockery::spy(\GitRepository::class);
        stub($this->repo)->getId()->returns($this->repo_id);
        stub($this->repo)->belongsToProject($this->project)->returns(true);

        $this->user    = \Mockery::spy(\PFUser::class);

        $this->request = \Mockery::spy(\Codendi_Request::class);
        $this->system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->controller = \Mockery::spy(\Git::class);
        $this->driver = \Mockery::spy(\Git_Driver_Gerrit::class);

        $gerrit_server = \Mockery::spy(\Git_RemoteServer_GerritServer::class);

        $this->gerrit_server_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        stub($this->gerrit_server_factory)->getServerById()->returns($gerrit_server);

        $this->factory = mockery_stub(\GitRepositoryFactory::class)->getRepositoryById(14)->returns($this->repo);

        $this->project_creator = \Mockery::spy(\Git_Driver_Gerrit_ProjectCreator::class);
        $this->git_permissions_manager = \Mockery::spy(\GitPermissionsManager::class);

        stub($this->controller)->getRequest()->returns($this->request);

        $git_plugin  = mockery_stub(\GitPlugin::class)->areFriendlyUrlsActivated()->returns(false);
        $url_manager = new Git_GitRepositoryUrlManager($git_plugin, new \Tuleap\InstanceBaseURLBuilder());

        $this->actions = new GitActions(
            $this->controller,
            $this->system_event_manager,
            $this->factory,
            \Mockery::spy(\GitRepositoryManager::class),
            $this->gerrit_server_factory,
            mockery_stub(\Git_Driver_Gerrit_GerritDriverFactory::class)->getDriver()->returns($this->driver),
            \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class),
            $this->project_creator,
            \Mockery::spy(\Git_Driver_Gerrit_Template_TemplateFactory::class),
            \Mockery::spy(\ProjectManager::class),
            $this->git_permissions_manager,
            $url_manager,
            \Mockery::spy(\Logger::class),
            \Mockery::spy(\Git_Mirror_MirrorDataMapper::class),
            \Mockery::spy(\ProjectHistoryDao::class),
            \Mockery::spy(\GitRepositoryMirrorUpdater::class),
            \Mockery::spy(\Tuleap\Git\RemoteServer\Gerrit\MigrationHandler::class),
            \Mockery::spy(\Tuleap\Git\GerritCanMigrateChecker::class),
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

    public function itReturnsAnErrorIfRepoDoesNotExist()
    {
        stub($this->factory)->getRepositoryById()->returns(null);
        $repo_id = 458;

        $GLOBALS['Response']->shouldReceive('sendStatusCode')->with(404)->once();

        $this->actions->fetchGitConfig($repo_id, $this->user, $this->project);
    }

    public function itReturnsAnErrorIfRepoDoesNotBelongToProject()
    {
        $project = \Mockery::spy(\Project::class);
        stub($this->repo)->belongsToProject($project)->returns(false);

        $GLOBALS['Response']->shouldReceive('sendStatusCode')->with(403)->once();

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $project);
    }

    public function itReturnsAnErrorIfUserIsNotProjectAdmin()
    {
        stub($this->user)->isAdmin($this->project_id)->returns(false);
        stub($this->repo)->isMigratedToGerrit()->returns(true);
        $GLOBALS['Response']->shouldReceive('sendStatusCode')->with(401)->once();

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }

    public function itReturnsAnErrorIfRepoIsNotMigratedToGerrit()
    {
        stub($this->user)->isAdmin($this->project_id)->returns(true);
        stub($this->repo)->isMigratedToGerrit()->returns(false);
        $GLOBALS['Response']->shouldReceive('sendStatusCode')->with(500)->once();

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }

    public function itReturnsAnErrorIfRepoIsGerritServerIsDown()
    {
        stub($this->git_permissions_manager)->userIsGitAdmin()->returns(true);
        stub($this->repo)->isMigratedToGerrit()->returns(true);
        stub($this->project_creator)->getGerritConfig()->throws(new Git_Driver_Gerrit_Exception());
        $GLOBALS['Response']->shouldReceive('sendStatusCode')->with(500)->once();

        $this->actions->fetchGitConfig($this->repo_id, $this->user, $this->project);
    }
}
