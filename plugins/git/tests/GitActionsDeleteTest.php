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
class GitActionsDeleteTests extends TuleapTestCase
{
    protected $git_actions;
    protected $project_id;
    protected $repository_id;
    protected $repository;
    protected $git_system_event_manager;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->project_id    = 101;
        $this->repository_id = 69;

        $this->repository = \Mockery::spy(\GitRepository::class);
        stub($this->repository)->getId()->returns($this->repository_id);
        stub($this->repository)->getProjectId()->returns($this->project_id);

        $this->git_system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $controler                  = mockery_stub(\Git::class)->getPlugin()->returns(\Mockery::spy(\GitPlugin::class));
        $git_repository_factory     = \Mockery::spy(\GitRepositoryFactory::class);

        stub($git_repository_factory)->getRepositoryById($this->repository_id)->returns($this->repository);

        $git_plugin  = mockery_stub(\GitPlugin::class)->areFriendlyUrlsActivated()->returns(false);
        $url_manager = new Git_GitRepositoryUrlManager($git_plugin, new \Tuleap\InstanceBaseURLBuilder());

        $this->git_actions = new GitActions(
            $controler,
            $this->git_system_event_manager,
            $git_repository_factory,
            \Mockery::spy(\GitRepositoryManager::class),
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

    public function itMarksRepositoryAsDeleted()
    {
        stub($this->repository)->canBeDeleted()->returns(true);

        $this->repository->shouldReceive('markAsDeleted')->once();

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function itTriggersASystemEventForPhysicalRemove()
    {
        stub($this->repository)->canBeDeleted()->returns(true);

        stub($this->repository)->getBackend()->returns(\Mockery::spy(\Git_Backend_Gitolite::class));

        expect($this->git_system_event_manager)->queueRepositoryDeletion($this->repository)->once();

        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }

    public function itDoesntDeleteWhenRepositoryCannotBeDeleted()
    {
        stub($this->repository)->canBeDeleted()->returns(false);

        $this->repository->shouldReceive('markAsDeleted')->never();
        expect($this->git_system_event_manager)->queueRepositoryDeletion()->never();
        $this->git_actions->deleteRepository($this->project_id, $this->repository_id);
    }
}
