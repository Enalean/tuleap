<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Svn\DiskUsage;

use Project;
use Statistics_DiskUsageManager;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\RepositoryManager;

class DiskUsageRetriever
{
    /**
     * @var RepositoryManager
     */
    private $repository_manager;
    /**
     * @var Statistics_DiskUsageManager
     */
    private $disk_usage_manager;

    public function __construct(RepositoryManager $repository_manager, Statistics_DiskUsageManager $disk_usage_manager)
    {
        $this->repository_manager = $repository_manager;
        $this->disk_usage_manager = $disk_usage_manager;
    }

    /**
     * @param Project $project
     *
     * @return int
     */
    public function getDiskUsageForProject(Project $project)
    {
        $repositories  = $this->repository_manager->getRepositoriesInProject($project);
        $svn_disk_size = 0;

        foreach ($repositories as $repository) {
            $size = $this->getSizeOnFileSystem($repository);
            if ($size) {
                $svn_disk_size += $size;
            }
        }

        return $svn_disk_size;
    }

    /**
     * @param Repository $repository
     *
     * @return bool
     */
    private function getSizeOnFileSystem(Repository $repository)
    {
        $path = $repository->getSystemPath();

        return $this->disk_usage_manager->getDirSize($path);
    }
}
