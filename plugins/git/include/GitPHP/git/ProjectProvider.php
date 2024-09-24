<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\GitPHP;

use Git_Exec;

class ProjectProvider
{
    private $repository;
    private $project;

    /**
     * @throws RepositoryAccessException
     * @throws RepositoryNotExistingException
     */
    public function __construct(\GitRepository $repository)
    {
        $project_root = $repository->getGitRootPath() . $repository->getProject()->getUnixName() . '/';
        $project_path = $repository->getFullName() . '.git';

        $repository_path = $project_root . $project_path;
        $git_exec        = new Git_Exec($repository_path, $repository_path);

        $this->project    = new Project(new BlobDataReader(), $project_root, $project_path, $git_exec);
        $this->repository = $repository;
    }

    /**
     * @return Project
     */
    public function GetProject() // @codingStandardsIgnoreLine
    {
        return $this->project;
    }

    /**
     * @return \GitRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }
}
