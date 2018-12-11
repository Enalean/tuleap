<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\Statistics;

use Project;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationDAO;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeVerify;
use Tuleap\GitLFS\LFSObject\LFSObjectDAO;

class Collector
{
    /**
     * @var \Statistics_DiskUsageDao
     */
    private $disk_usage_dao;

    /**
     * @var ActionAuthorizationDAO
     */
    private $action_authorization_dao;

    /**
     * @var LFSObjectDAO
     */
    private $lfs_object_dao;

    /**
     * @var \GitRepositoryFactory
     */
    private $git_repository_factory;

    public function __construct(
        \Statistics_DiskUsageDao $disk_usage_dao,
        ActionAuthorizationDAO $action_authorization_dao,
        LFSObjectDAO $lfs_object_dao,
        \GitRepositoryFactory $git_repository_factory
    ) {
        $this->disk_usage_dao           = $disk_usage_dao;
        $this->action_authorization_dao = $action_authorization_dao;
        $this->lfs_object_dao           = $lfs_object_dao;
        $this->git_repository_factory   = $git_repository_factory;
    }

    public function proceedToDiskUsageCollection(array $params)
    {
        $start = microtime(true);
        $row   = $params['project_row'];

        $this->disk_usage_dao->addGroup(
            $row['group_id'],
            \gitlfsPlugin::SERVICE_SHORTNAME,
            $this->getDiskUsageForProject($params['project']),
            $_SERVER['REQUEST_TIME']
        );

        $end = microtime(true);
        $this->registerCollectionTime($params, $end - $start);
    }

    /**
     * @return int
     */
    private function getDiskUsageForProject(Project $project)
    {
        $repositories_ids = $this->getRepositoriesIdsForProject($project);

        if (count($repositories_ids) === 0) {
            return 0;
        }

        return $this->getAuthorizationsDiskUsage($repositories_ids) + $this->getObjectsDiskUsage($repositories_ids);
    }

    /**
     * @return int
     */
    private function getAuthorizationsDiskUsage(array $repositories_ids)
    {
        $date_time = new \DateTimeImmutable();
        $size      = 0;

        $authorizations = $this->action_authorization_dao->searchAuthorizationTypeByRepositoriesIdsAndExpiration(
            new ActionAuthorizationTypeVerify(),
            $repositories_ids,
            $date_time
        );

        foreach ($authorizations as $authorization) {
            $size += (int) $authorization['object_size'];
        }

        return $size;
    }

    /**
     * return int
     */
    private function getObjectsDiskUsage(array $repositories_ids)
    {
        $objects = $this->lfs_object_dao->searchObjectsByRepositoryIds($repositories_ids);
        $size    = 0;

        foreach ($objects as $object) {
            $size += (int) $object['object_size'];
        }

        return $size;
    }

    /**
     * @return array
     */
    private function getRepositoriesIdsForProject(Project $project)
    {
        $repositories     = $this->git_repository_factory->getAllRepositoriesOfProject($project);
        $repositories_ids = [];

        foreach ($repositories as $repository) {
            $repositories_ids[] = $repository->getId();
        }

        return $repositories_ids;
    }

    private function registerCollectionTime($params, $time)
    {
        if (!isset($params['time_to_collect'][\gitlfsPlugin::SERVICE_SHORTNAME])) {
            $params['time_to_collect'][\gitlfsPlugin::SERVICE_SHORTNAME] = 0;
        }

        $params['time_to_collect'][\gitlfsPlugin::SERVICE_SHORTNAME] += $time;
    }
}
