<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
require_once dirname(__FILE__).'/../bootstrap.php';

class SystemEvent_GIT_REPO_UPDATETest extends TuleapTestCase {
    private $repository_id = 115;

    public function setUp() {
        parent::setUp();

        $this->backend    = mock('Git_Backend_Gitolite');

        $this->repository = mock('GitRepository');
        stub($this->repository)->getBackend()->returns($this->backend);
        
        $this->repository_factory = mock('GitRepositoryFactory');
        $this->system_event_dao   = mock('SystemEventDao');

        $this->event = partial_mock('SystemEvent_GIT_REPO_UPDATE', array('done', 'warning', 'error'));
        $this->event->setParameters("$this->repository_id");
        $this->event->injectDependencies($this->repository_factory, $this->system_event_dao);
    }

    public function itGetsTheRepositoryFromTheFactory() {
        stub($this->repository_factory)->getRepositoryById()->returns($this->repository);
        expect($this->repository_factory)->getRepositoryById($this->repository_id)->once();
        $this->event->process();
    }

    public function itDelegatesToBackendRepositoryCreation() {
        stub($this->repository_factory)->getRepositoryById()->returns($this->repository);
        expect($this->backend)->updateAllRepoConf()->once();
        $this->event->process();
    }

    public function itMarksTheEventAsDone() {
        stub($this->repository_factory)->getRepositoryById()->returns($this->repository);
        expect($this->event)->done()->once();
        $this->event->process();
    }

    public function itMarksTheEventAsWarningWhenTheRepoDoesNotExist() {
        stub($this->repository_factory)->getRepositoryById()->returns(null);
        expect($this->event)->warning('Unable to find repository, perhaps it was deleted in the mean time?')->once();
        $this->event->process();
    }

    public function itSkipsAllEvents() {
        $project_id = 456;
        stub($this->repository)->getProjectId()->returns($project_id);

        $all_events = array(
            array( 'id' => 111, 'parameters' => '33'),
            array( 'id' => 222, 'parameters' => '5555'),
            array( 'id' => 333, 'parameters' => '66::somestuff'),
            array( 'id' => 444, 'parameters' => '7')
        );

        $all_event_ids = array(111, 222, 333, 444);

        stub($this->repository_factory)->getRepositoryById()->returns($this->repository);
        stub($this->system_event_dao)->searchNewGitRepoUpdateEvents()->returns($all_events);

        expect($this->repository_factory)->getRepositoryById()->count(2);

        $same_project_repositories = array(1, 5555, 458, 66, 7);
        stub($this->backend)->searchOtherRepositoriesInSameProjectFromRepositoryList()->returns($same_project_repositories);

        expect($this->system_event_dao)->markAsRunning($all_event_ids)->once();
        expect($this->system_event_dao)->markAsDone($all_event_ids)->once();

        $this->event->process();
    }
}

?>
