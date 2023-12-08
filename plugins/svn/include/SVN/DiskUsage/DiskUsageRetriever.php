<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\SVN\DiskUsage;

use DateTime;
use Psr\Log\LoggerInterface;
use Project;
use Statistics_DiskUsageManager;
use SvnPlugin;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Repository\RepositoryManager;

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
    /**
     * @var DiskUsageDao
     */
    private $disk_usage_dao;
    /**
     * @var \Statistics_DiskUsageDao
     */
    private $dao;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        RepositoryManager $repository_manager,
        Statistics_DiskUsageManager $disk_usage_manager,
        DiskUsageDao $disk_usage_dao,
        \Statistics_DiskUsageDao $dao,
        LoggerInterface $logger,
    ) {
        $this->repository_manager = $repository_manager;
        $this->disk_usage_manager = $disk_usage_manager;
        $this->disk_usage_dao     = $disk_usage_dao;
        $this->dao                = $dao;
        $this->logger             = $logger;
    }

    public function hasCoreStatistics(Project $project): bool
    {
        try {
            $this->repository_manager->getCoreRepository($project);
            return true;
        } catch (CannotFindRepositoryException $exception) {
            return false;
        }
    }

    /**
     * @return int
     */
    public function getDiskUsageForProject(Project $project)
    {
        $this->logger->info("Collecting statistics for project " . $project->getUnixName());
        $yesterday = new DateTime("yesterday midnight");

        if (
            ! $this->hasRepositoriesUpdatedAfterGivenDate($project, $yesterday->getTimestamp())
            && $this->disk_usage_dao->hasRepositories($project->getID())
        ) {
            $this->logger->info("No new commit made on this project since yesterday, duplicate value from DB.");

            return $this->getLastSizeForProject($project);
        }

        $this->logger->info("Project has new commit, collecting disk size data.");
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
     *
     * @return int|false
     */
    private function getSizeOnFileSystem(Repository $repository)
    {
        $path = $repository->getSystemPath();

        return $this->disk_usage_manager->getDirSize($path);
    }

    private function hasRepositoriesUpdatedAfterGivenDate(Project $project, $timestamp)
    {
        return $this->disk_usage_dao->hasRepositoriesUpdatedAfterGivenDate($project->getID(), $timestamp);
    }

    public function getLastSizeForProject(Project $project): int
    {
        $row = $this->dao->getLastSizeForService($project->getID(), SvnPlugin::SERVICE_SHORTNAME);

        return (int) ($row['size'] ?? 0);
    }
}
