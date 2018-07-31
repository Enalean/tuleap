<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class SystemEvent_GIT_GERRIT_PROJECT_READONLYTest extends TuleapTestCase {

    public function itMakesGerritProjectReadOnly() {
        $repository_factory = mock('GitRepositoryFactory');
        $server_factory     = mock('Git_RemoteServer_GerritServerFactory');
        $driver             = mock('Git_Driver_Gerrit');
        $driver_factory     = stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns($driver);
        $repository         = mock('GitRepository');
        $forge_project      = stub('Project')->getUnixName()->returns('projname');
        $server             = mock('Git_RemoteServer_GerritServer');

        stub($repository)->getProject()->returns($forge_project);
        stub($repository)->getName()->returns('repo_01');
        stub($server_factory)->getServerById()->returns($server);
        stub($repository_factory)->getRepositoryById()->returns($repository);

        $event = partial_mock('SystemEvent_GIT_GERRIT_PROJECT_READONLY', array(('getParametersAsArray')));
        $event->injectDependencies(
            $repository_factory,
            $server_factory,
            $driver_factory
        );

        $repository_id    = 154;
        $remote_server_id = 33;
        stub($event)->getParametersAsArray()->returns(
            array(
                $repository_id,
                $remote_server_id,
            )
        );

        expect($driver)->makeGerritProjectReadOnly($server, 'projname/repo_01')->once();

        $event->process();
    }

}