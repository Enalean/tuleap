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
 *
 */

namespace Tuleap\Mediawiki\Maintenance;

use Project;
use Backend;
use ProjectManager;
use Logger;
use WrapperLogger;
use MediawikiDao;
use Tuleap\Mediawiki\MediawikiDataDir;

class CleanUnused
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CleanUnusedDao
     */
    private $dao;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var Backend
     */
    private $backend;
    /**
     * @var int
     */
    private $dir_deleted = 0;
    /**
     * @var MediawikiDao
     */
    private $mediawiki_dao;

    public function __construct(Logger $logger, CleanUnusedDao $dao, ProjectManager $project_manager, Backend $backend, MediawikiDao $mediawiki_dao)
    {
        $this->logger          = new WrapperLogger($logger, 'MW Purge');
        $this->dao             = $dao;
        $this->project_manager = $project_manager;
        $this->backend         = $backend;
        $this->mediawiki_dao   = $mediawiki_dao;

        $this->dao->setLogger($this->logger);
    }

    public function purgeProject($project_id)
    {
        $this->logger->info("Purge $project_id");
        $project = $this->project_manager->getProject($project_id);
        if ($project) {
            $database_name = $this->mediawiki_dao->getMediawikiDatabaseName($project);
            $this->purgeOneProject(
                $project,
                array(
                    'project_id'    => $project_id,
                    'database_name' => $database_name,
                ),
                false
            );
        }
        $this->logger->info("Purge Completed");
    }

    public function purge($dry_run, array $force)
    {
        $this->logger->info("Start purge");
        $this->purgeDeletedProjects($dry_run);
        $this->purgeUnusedService($dry_run, $force);
        $this->purgeUsedServicesEmptyWiki($dry_run, $force);
        $this->logger->info("Purge completed");
        $this->logger->info("{$this->dao->getDeletedDatabasesCount()} database(s) deleted");
        $this->logger->info("{$this->dao->getDeletedTablesCount()} table(s) deleted in central DB");
        $this->logger->info("{$this->dir_deleted} directories deleted");
    }

    private function purgeDeletedProjects($dry_run)
    {
        $this->logger->info("Start purge of deleted projects");
        foreach ($this->dao->getDeletionCandidates() as $row) {
            $project = $this->project_manager->getProject($row['project_id']);
            if ($project) {
                $this->purgeOneProject($project, $row, $dry_run);
            }
        }
        $this->logger->info("Purge of deleted projects completed");
    }

    private function purgeUnusedService($dry_run, array $force)
    {
        $this->logger->info("Start purge of unused services");
        foreach ($this->dao->getMediawikiDatabaseInUnusedServices() as $row) {
            $project = $this->project_manager->getProject($row['project_id']);
            if ($project && ($this->isEmpty($project) || $this->isForced($project, $force))) {
                $this->purgeOneProject($project, $row, $dry_run);
            } else {
                $this->logger->warn("Project {$project->getUnixName()} ({$project->getID()}) has mediawiki content but service is desactivated. You should check with project admins");
            }
        }
        $this->logger->info("Purge of unused services completed");
    }

    private function purgeUsedServicesEmptyWiki($dry_run, array $force)
    {
        $this->logger->info("Start purge of used but empty mediawiki");
        foreach ($this->dao->getMediawikiDatabasesInUsedServices() as $row) {
            $project = $this->project_manager->getProject($row['project_id']);
            if ($project && $this->isEmpty($project)) {
                if ($this->isForced($project, $force)) {
                    $this->purgeOneProject($project, $row, $dry_run);
                    $this->dao->desactivateService($project->getID(), $dry_run);
                } else {
                    $this->logger->warn("Project {$project->getUnixName()} ({$project->getID()}) has service activated but no content. You should check with project admins");
                }
            }
        }
        $this->logger->info("End of purge of used but empty mediawiki");
    }

    private function isEmpty(Project $project)
    {
        $row = $this->mediawiki_dao->getMediawikiPagesNumberOfAProject($project);
        if (isset($row['result'])) {
            return (int) $row['result'] === 0;
        }
        throw new \Exception("Unable to get wiki page count in {$project->getID()}");
    }

    private function isForced(Project $project, array $force)
    {
         return in_array((int) $project->getID(), $force, true);
    }

    private function purgeOneProject(Project $project, array $row, $dry_run)
    {
        $this->logger->info("Found candidate ".$row['database_name']);
        $this->dao->purge($row, $dry_run);
        $this->deleteDirectory($project, $dry_run);
    }

    private function deleteDirectory(Project $project, $dry_run)
    {
        $this->logger->info("Delete data dir");
        $data_dir = new MediawikiDataDir();
        $path = $data_dir->getMediawikiDir($project);
        if (is_dir($path)) {
            $this->logger->info("Data dir found $path, remove it");
            if (! $dry_run) {
                $this->backend->recurseDeleteInDir($path);
                rmdir($path);
            }
            $this->dir_deleted++;
            $this->logger->info("Data dir removed");
        }
    }
}
