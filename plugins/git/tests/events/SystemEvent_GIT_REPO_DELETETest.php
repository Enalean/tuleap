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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once dirname(__FILE__).'/../bootstrap.php';

class SystemEvent_GIT_REPO_DELETETest extends TuleapTestCase {
    protected $project_id;
    protected $repository_id;
    protected $repository;
    protected $system_event_manager;
    protected $git_repository_factory;

    public function setUp() {
        parent::setUp();

        $this->project_id    = 101;
        $this->repository_id = 69;

        $this->repository = mock('GitRepository');
        stub($this->repository)->getId()->returns($this->repository_id);
        stub($this->repository)->getProjectId()->returns($this->project_id);

        $this->system_event_manager   = mock('SystemEventManager');
        $this->git_repository_factory = mock('GitRepositoryFactory');

        stub($this->git_repository_factory)->getDeletedRepository($this->repository_id)->returns($this->repository);
    }

    public function itDeletesTheRepository() {
        $event = TestHelper::getPartialMock('SystemEvent_GIT_REPO_DELETE', array('getRepositoryFactory'));
        $event->setParameters($this->project_id.SystemEvent::PARAMETER_SEPARATOR.$this->repository_id);
        stub($event)->getRepositoryFactory()->returns($this->git_repository_factory);
        
        $event->process();
    }
}

?>
