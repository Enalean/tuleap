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

class SystemEvent_GIT_GERRIT_MIGRATION extends SystemEvent {

    const TYPE = "GIT_GERRIT_MIGRATION";

    /** @var GitDao */
    private $dao;

    /** @var Git_Driver_Gerrit */
    private $driver;
    
    /** @var GitRepositoryFactory */
    private $repository_factory;

    public function process() {
        $repo_id = (int)$this->getParameter(0);
        $this->getDao()->switchToGerrit($repo_id);

        $repository = $this->getRepositoryFactory()->getRepositoryById($repo_id);
        $this->getGerritDriver()->createProject($repository);
    }

    public function verbalizeParameters($with_link) {
        return  $this->parameters;
    }

    public function setGitDao(GitDao $dao) {
        $this->dao = $dao;
    }

    public function getDao() {
        if ($this->dao == null) {
            $this->dao = new GitDao();
        }
        return $this->dao;
    }

    public function getGerritDriver() {
        if ($this->driver == null) {
            $this->driver = new Git_Driver_Gerrit();
        }
        return $this->driver;
    }

    public function setGerritDriver(Git_Driver_Gerrit $driver) {
        $this->driver = $driver;
    }

    public function getRepositoryFactory() {
        if ($this->repository_factory == null) {
            $this->repository_factory = new GitRepositoryFactory($this->getDao(), ProjectManager::instance());
        }
        return $this->repository_factory;
    }

    public function setRepositoryFactory(GitRepositoryFactory $repository_factory) {
        $this->repository_factory = $repository_factory;
    }
}

?>