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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__).'/../GerritTestInterfaces.php';

abstract class Git_Driver_GerritLegacy_baseTest extends TuleapTestCase {

    /**
     * @var GitRepository
     */
    protected $repository;

    /** @var Git_RemoteServer_GerritServer */
    protected $gerrit_server;

    /** @var Project */
    protected $project;

    /**
     * @var RemoteSshCommand
     */
    protected $ssh;

    /** @var Git_Driver_GerritLegacy */
    protected $driver;

    public function setUp()
    {
        parent::setUp();

        $this->project_name    = 'firefox';
        $this->namespace       = 'jean-claude';
        $this->repository_name = 'dusse';

        $this->project = stub('Project')->getUnixName()->returns($this->project_name);

        $this->repository = aGitRepository()
            ->withProject($this->project)
            ->withNamespace($this->namespace)
            ->withName($this->repository_name)
            ->build();

        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');

        $this->ssh    = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $this->logger = mock('BackendLogger');
        $this->driver = new Git_Driver_GerritLegacy($this->ssh, $this->logger);
    }

}
