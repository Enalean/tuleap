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
        stub($this->repository_factory)->getRepositoryById()->returns($this->repository);

        $this->event = partial_mock('SystemEvent_GIT_REPO_UPDATE', array('done', 'error'));
        $this->event->setParameters("$this->repository_id");
        $this->event->injectDependencies($this->repository_factory);
    }

    public function itGetsTheRepositoryFromTheFactory() {
        expect($this->repository_factory)->getRepositoryById($this->repository_id)->once();
        $this->event->process();
    }

    public function itDelegatesToBackendRepositoryCreation() {
        expect($this->backend)->updateRepoConf()->once();
        $this->event->process();
    }

    public function itMarksTheEventAsDone() {
        expect($this->event)->done()->once();
        $this->event->process();
    }
}

?>
