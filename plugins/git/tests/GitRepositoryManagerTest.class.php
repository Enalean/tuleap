<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../include/GitRepositoryManager.class.php';

class GitRepositoryManager_DeleteAllRepositoriesTest extends TuleapTestCase {
    private $project;
    private $git_repository_manager;

    public function setUp() {
        parent::setUp();
        $this->project_id           = 42;
        $this->project              = stub('Project')->getID()->returns($this->project_id);
        $this->repository_factory   = mock('GitRepositoryFactory');
        $this->system_event_manager = mock('SystemEventManager');

        $this->git_repository_manager = new GitRepositoryManager($this->repository_factory, $this->system_event_manager);
    }

    public function itDeletesNothingWhenThereAreNoRepositories() {
        stub($this->repository_factory)->getAllRepositories()->returns(array());
        $this->repository_factory->expectOnce('getAllRepositories', array($this->project));

        $this->git_repository_manager->deleteProjectRepositories($this->project);
    }

    public function itDeletesEachRepository() {
        $repository_1_id = 1;
        $repository_1    = mock('GitRepository');
        $repository_1->expectOnce('forceMarkAsDeleted');
        stub($repository_1)->getId()->returns($repository_1_id);
        stub($repository_1)->getProjectId()->returns($this->project);

        $repository_2_id = 2;
        $repository_2    = mock('GitRepository');
        $repository_2->expectOnce('forceMarkAsDeleted');
        stub($repository_2)->getId()->returns($repository_2_id);
        stub($repository_2)->getProjectId()->returns($this->project);

        $this->system_event_manager->expectCallCount('createEvent', 2);

        $this->system_event_manager->expectAt(0, 'createEvent', array(
            'GIT_REPO_DELETE',
            $this->project_id.SystemEvent::PARAMETER_SEPARATOR.$repository_1_id,
            '*'
        ));

        $this->system_event_manager->expectAt(1, 'createEvent', array(
            'GIT_REPO_DELETE',
            $this->project_id.SystemEvent::PARAMETER_SEPARATOR.$repository_2_id,
            '*'
        ));

        stub($this->repository_factory)->getAllRepositories()->returns(array($repository_1, $repository_2));

        $this->git_repository_manager->deleteProjectRepositories($this->project);
    }
}

?>
