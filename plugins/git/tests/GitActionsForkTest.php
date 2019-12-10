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
class GitActionsForkTests extends TuleapTestCase
{
    private $actions;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->manager = \Mockery::spy(\GitRepositoryManager::class);

        $git_plugin  = mockery_stub(\GitPlugin::class)->areFriendlyUrlsActivated()->returns(false);
        $url_manager = new Git_GitRepositoryUrlManager($git_plugin, new \Tuleap\InstanceBaseURLBuilder());

        $this->actions = new GitActions(
            \Mockery::spy(\Git::class),
            \Mockery::spy(\Git_SystemEventManager::class),
            \Mockery::spy(\GitRepositoryFactory::class),
            $this->manager,
            \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class),
            mockery_stub(\Git_Driver_Gerrit_GerritDriverFactory::class)->getDriver()->returns(\Mockery::spy(\Git_Driver_Gerrit::class)),
            \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class),
            \Mockery::spy(\Git_Driver_Gerrit_ProjectCreator::class),
            \Mockery::spy(\Git_Driver_Gerrit_Template_TemplateFactory::class),
            \Mockery::spy(\ProjectManager::class),
            \Mockery::spy(\GitPermissionsManager::class),
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

    public function itDelegatesForkToGitManager()
    {
        $repositories = array(aGitRepository()->build(), aGitRepository()->build());
        $to_project   = \Mockery::spy(\Project::class);
        $namespace    = 'namespace';
        $scope        = GitRepository::REPO_SCOPE_INDIVIDUAL;
        $user         = \Mockery::spy(\PFUser::class);
        $response     = \Mockery::spy(\Layout::class);
        $redirect_url = '/stuff';
        $forkPermissions = array();

        $this->manager->shouldReceive('forkRepositories')->with($repositories, $to_project, $user, $namespace, $scope, $forkPermissions)->once();

        $this->actions->fork($repositories, $to_project, $namespace, $scope, $user, $response, $redirect_url, $forkPermissions);
    }
}
