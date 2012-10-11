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

require_once GIT_BASE_DIR .'/GitDao.class.php';
require_once GIT_BASE_DIR .'/Git/Driver/Gerrit.class.php';
require_once GIT_BASE_DIR .'/Git/RemoteServer/GerritServerFactory.class.php';

class SystemEvent_GIT_GERRIT_MIGRATION extends SystemEvent {

    const TYPE = "GIT_GERRIT_MIGRATION";

    /** @var GitDao */
    private $dao;

    /** @var Git_Driver_Gerrit */
    private $driver;

    /** @var GitRepositoryFactory */
    private $repository_factory;

    /** @var Git_RemoteServer_GerritServerFactory */
    private $server_factory;
    
    public function process() {
        $repo_id           = (int)$this->getParameter(0);
        $remote_server_id  = (int)$this->getParameter(1);
        $this->dao->switchToGerrit($repo_id, $remote_server_id);

        $repository = $this->repository_factory->getRepositoryById($repo_id);
        $server     = $this->server_factory->getServer($repository);
        $this->driver->createProject($server, $repository);
    }

    public function verbalizeParameters($with_link) {
        return  $this->parameters;
    }

    public function injectDependencies(
        GitDao $dao,
        Git_Driver_Gerrit $driver,
        GitRepositoryFactory $repository_factory,
        Git_RemoteServer_GerritServerFactory  $server_factory
    ) {
        $this->dao                = $dao;
        $this->driver             = $driver;
        $this->repository_factory = $repository_factory;
        $this->server_factory     = $server_factory;
    }
}

?>