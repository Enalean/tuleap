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

class SystemEvent_GIT_REPO_FORKTest extends TuleapTestCase {
    private $old_repository;
    private $new_repository;
    private $old_repository_id = 115;
    private $new_repository_id = 123;

    public function setUp() {
        parent::setUp();

        $this->backend    = mock('Git_Backend_Gitolite');

        $this->old_repository = mock('GitRepository');
        stub($this->old_repository)->getBackend()->returns($this->backend);

        $this->new_repository     = mock('GitRepository');
        $this->repository_factory = mock('GitRepositoryFactory');

        $this->event = partial_mock('SystemEvent_GIT_REPO_FORK', array('done', 'warning', 'error'));
        $this->event->setParameters($this->old_repository_id.SystemEvent::PARAMETER_SEPARATOR.$this->new_repository_id);
        $this->event->injectDependencies($this->repository_factory);
    }

    public function itGetsTheRepositoryIdsFromTheFactory() {
        stub($this->repository_factory)->getRepositoryById($this->old_repository_id)->returns($this->old_repository);
        stub($this->repository_factory)->getRepositoryById($this->new_repository_id)->returns($this->new_repository);
        expect($this->repository_factory)->getRepositoryById()->count(2);
        expect($this->repository_factory)->getRepositoryById($this->old_repository_id)->at(0);
        expect($this->repository_factory)->getRepositoryById($this->new_repository_id)->at(1);
        $this->event->process();
    }

    public function itDelegatesToBackendRepositoryCreation() {
        stub($this->repository_factory)->getRepositoryById($this->old_repository_id)->returns($this->old_repository);
        stub($this->repository_factory)->getRepositoryById($this->new_repository_id)->returns($this->new_repository);
        expect($this->backend)->forkOnFilesystem('*', $this->new_repository)->once();
        $this->event->process();
    }

    public function itMarksTheEventAsDone() {
        stub($this->repository_factory)->getRepositoryById($this->old_repository_id)->returns($this->old_repository);
        stub($this->repository_factory)->getRepositoryById($this->new_repository_id)->returns($this->new_repository);
        expect($this->event)->done()->once();
        $this->event->process();
    }

    public function itMarksTheEventAsWarningWhenTheRepoDoesNotExist() {
        stub($this->repository_factory)->getRepositoryById()->returns(null);
        expect($this->event)->warning('Unable to find repository, perhaps it was deleted in the mean time?')->once();
        $this->event->process();
    }
}

?>
