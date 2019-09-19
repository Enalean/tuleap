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

function aGitRepository()
{
    return new Test_Git_RepositoryBuilder();
}

class Test_Git_RepositoryBuilder
{
    private $repository;

    public function __construct()
    {
        $this->repository = new GitRepository();
    }

    public function withPath($path)
    {
        $this->repository->setPath($path);
        return $this;
    }

    public function withNamespace($namespace)
    {
        $this->repository->setNamespace($namespace);
        return $this;
    }

    public function withId($id)
    {
        $this->repository->setId($id);
        return $this;
    }

    public function withName($name)
    {
        $this->repository->setName($name);
        return $this;
    }

    public function withProject(Project $project)
    {
        $this->repository->setProject($project);
        return $this;
    }

    public function withBackend(Git_Backend_Interface $backend)
    {
        $this->repository->setBackend($backend);
        return $this;
    }

    public function withRemoteServerId($id)
    {
        $this->repository->setRemoteServerId($id);
        return $this;
    }

    public function withDescription($description)
    {
        $this->repository->setDescription($description);
        return $this;
    }

    public function withMailPrefix($prefix)
    {
        $this->repository->setMailPrefix($prefix);
        return $this;
    }

    public function withNotifiedEmails($emails)
    {
        $this->repository->setNotifiedMails($emails);
        return $this;
    }


    public function build()
    {
        return $this->repository;
    }
}
