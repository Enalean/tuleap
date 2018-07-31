<?php

/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
require_once 'common/log/Logger.class.php';

class SystemEvent_GIT_GERRIT_PROJECT_DELETE_BaseTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->repository_factory = mock('GitRepositoryFactory');
        $this->server_factory     = mock('Git_RemoteServer_GerritServerFactory');
        $this->driver             = mock('Git_Driver_Gerrit');
        $this->driver_factory     = stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns($this->driver);
        $this->backend            = mock('Git_Backend_Gitolite');
        $this->repository         = mock('GitRepository');

        $this->event = partial_mock('SystemEvent_GIT_GERRIT_PROJECT_DELETE', array(('getParametersAsArray')));
        $this->event->injectDependencies($this->repository_factory, $this->server_factory, $this->driver_factory);

        $this->forge_project_name = 'Hello_kitty';
        $forge_project = stub('Project')->getUnixName()->returns($this->forge_project_name);

        $this->repository_name = 'mouse';

        stub($this->repository)->getProject()->returns($forge_project);
        stub($this->repository)->getName()->returns($this->repository_name);
        stub($this->repository)->getBackend()->returns($this->backend);

        stub($this->repository_factory)->getRepositoryById()->returns($this->repository);

        $repository_id    = 154;
        $remote_server_id = 33;
        stub($this->event)->getParametersAsArray()->returns(
            array(
                $repository_id,
                $remote_server_id,
            )
        );

        $this->server = mock('Git_RemoteServer_GerritServer');
        stub($this->server_factory)->getServerById()->returns($this->server);
    }

    public function itDeletesGerritProject() {
        $gerrit_project_full_name = $this->forge_project_name. '/'. $this->repository_name;

        expect($this->driver)->deleteProject($this->server, $gerrit_project_full_name)->once();
        expect($this->backend)->setGerritProjectAsDeleted()->once();

        $this->event->process();
    }
}