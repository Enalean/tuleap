<?php
/**
 * Copyright (c) Enalean, 2012 - 2019. All Rights Reserved.
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

require_once 'bootstrap.php';

class GitRepositoryManager_DeleteAllRepositoriesTest extends TuleapTestCase
{
    private $project;
    private $git_repository_manager;
    private $git_system_event_manager;
    private $dao;
    private $backup_directory;

    public function setUp()
    {
        parent::setUp();
        $this->project_id           = 42;
        $this->project              = stub('Project')->getID()->returns($this->project_id);
        $this->repository_factory   = mock('GitRepositoryFactory');
        $this->git_system_event_manager = mock('Git_SystemEventManager');
        $this->dao                  = safe_mock(GitDao::class);
        $this->backup_directory     = "/tmp/";
        $this->mirror_updater       = mock('GitRepositoryMirrorUpdater');
        $this->mirror_data_mapper   = mock('Git_Mirror_MirrorDataMapper');

        $this->git_repository_manager = new GitRepositoryManager(
            $this->repository_factory,
            $this->git_system_event_manager,
            $this->dao,
            $this->backup_directory,
            $this->mirror_updater,
            $this->mirror_data_mapper,
            mock('Tuleap\Git\Permissions\FineGrainedPermissionReplicator'),
            mock('ProjectHistoryDao'),
            mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
            mock(EventManager::class)
        );
    }

    public function itDeletesNothingWhenThereAreNoRepositories()
    {
        stub($this->repository_factory)->getAllRepositories()->returns(array());
        $this->repository_factory->expectOnce('getAllRepositories', array($this->project));

        $this->git_repository_manager->deleteProjectRepositories($this->project);
    }

    public function itDeletesEachRepository()
    {
        $repository_1_id = 1;
        $repository_1    = mock('GitRepository');
        $repository_1->expectOnce('forceMarkAsDeleted');
        stub($repository_1)->getId()->returns($repository_1_id);
        stub($repository_1)->getProjectId()->returns($this->project);
        stub($repository_1)->getBackend()->returns(mock('Git_Backend_Gitolite'));

        $repository_2_id = 2;
        $repository_2    = mock('GitRepository');
        $repository_2->expectOnce('forceMarkAsDeleted');
        stub($repository_2)->getId()->returns($repository_2_id);
        stub($repository_2)->getProjectId()->returns($this->project);
        stub($repository_2)->getBackend()->returns(mock('Git_Backend_Gitolite'));

        expect($this->git_system_event_manager)->queueRepositoryDeletion()->count(2);
        expect($this->git_system_event_manager)->queueRepositoryDeletion($repository_1)->at(0);
        expect($this->git_system_event_manager)->queueRepositoryDeletion($repository_2)->at(1);

        stub($this->repository_factory)->getAllRepositories()->returns(array($repository_1, $repository_2));

        $this->git_repository_manager->deleteProjectRepositories($this->project);
    }
}
