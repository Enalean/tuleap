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
    private $system_event_manager;

    public function setUp() {
        parent::setUp();

        $this->backend    = mock('Git_Backend_Gitolite');

        $this->repository = mock('GitRepository');
        stub($this->repository)->getBackend()->returns($this->backend);

        $this->repository_factory   = mock('GitRepositoryFactory');
        $this->system_event_dao     = mock('SystemEventDao');
        $this->system_event_manager = mock('Git_SystemEventManager');

        $this->event = partial_mock('SystemEvent_GIT_REPO_UPDATE', array('done', 'warning', 'error', 'getId'));
        $this->event->setParameters("$this->repository_id");
        $this->event->injectDependencies(
            $this->repository_factory,
            $this->system_event_dao,
            mock('Logger'),
            $this->system_event_manager
        );
    }

    public function itGetsTheRepositoryFromTheFactory() {
        stub($this->repository_factory)->getRepositoryById()->returns($this->repository);
        expect($this->repository_factory)->getRepositoryById($this->repository_id)->once();
        $this->event->process();
    }

    public function itDelegatesToBackendRepositoryCreation() {
        stub($this->repository_factory)->getRepositoryById()->returns($this->repository);
        expect($this->backend)->updateRepoConf()->once();
        $this->event->process();
    }

    public function itMarksTheEventAsDone() {
        stub($this->repository_factory)->getRepositoryById()->returns($this->repository);
        expect($this->backend)->updateRepoConf()->once()->returns(true);
        expect($this->event)->done()->once();
        $this->event->process();
    }

    public function itMarksTheEventAsWarningWhenTheRepoDoesNotExist() {
        stub($this->repository_factory)->getRepositoryById()->returns(null);
        expect($this->event)->warning('Unable to find repository, perhaps it was deleted in the mean time?')->once();
        $this->event->process();
    }

    public function itMarksTheEventAsDoneWhenTheRepoIsFlaggedAsDeleted() {
        stub($this->repository_factory)->getRepositoryById()->returns(null);
        stub($this->repository_factory)->getDeletedRepository()->returns($this->repository);

        expect($this->event)->done('Unable to update a repository marked as deleted')->once();

        $this->event->process();
    }

    public function itAskToUpdateGrokmirrorManifestFiles() {
        stub($this->repository_factory)->getRepositoryById()->returns($this->repository);

        expect($this->backend)->updateRepoConf()->once()->returns(true);
        expect($this->system_event_manager)->queueGrokMirrorManifest($this->repository)->once();

        $this->event->process();
    }
}
