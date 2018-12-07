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
 *
 */

namespace Tuleap\SVN\Logs;

class DBWriterCore
{
    /**
     * @var DBWriterCoreDao
     */
    private $dao;
    private $repositories_cache = [];
    private $day_accesses_cache = [];
    /**
     * @var DBWriterUserCache
     */
    private $user_cache;

    public function __construct(DBWriterCoreDao $dao, DBWriterUserCache $user_cache)
    {
        $this->dao        = $dao;
        $this->user_cache = $user_cache;
    }

    public function save(LogCache $log_cache)
    {
        $this->cacheProjectIds($log_cache->getCoreProjectNames());
        foreach ($log_cache->getCoreProjects() as $project_name => $user_names) {
            $project_id = $this->repositories_cache[$project_name];
            foreach ($user_names as $user_name => $days) {
                $user_id = $this->user_cache->getUserId($user_name);
                foreach ($days as $day => $actions) {
                    if ($this->hasRecord($project_id, $user_id, $day)) {
                        $this->dao->updateAccess($project_id, $user_id, $day, $actions[LogCache::READ] + $actions[LogCache::WRITE]);
                    } else {
                        $this->dao->insertAccess($project_id, $user_id, $day, $actions[LogCache::READ] + $actions[LogCache::WRITE]);
                    }
                }
            }
        }

        foreach ($log_cache->getLastAccessTimestamps() as $username => $timestamp) {
            $this->dao->updateLastAccessDate($this->user_cache->getUserId($username), $timestamp);
        }
    }

    private function cacheProjectIds(array $project_names)
    {
        if (count($project_names) > 0) {
            $dar = $this->dao->searchProjects($project_names);
            foreach ($dar as $row) {
                $this->repositories_cache[$row['project_name']] = (int) $row['project_id'];
            }
        }
    }

    private function hasRecord($repository_id, $user_id, $day)
    {
        $this->cacheAccessPerDay($day);
        return isset($this->day_accesses_cache[$day][$repository_id][$user_id]);
    }

    private function cacheAccessPerDay($day)
    {
        if (! isset($this->day_accesses_cache[$day])) {
            $dar = $this->dao->searchAccessPerDay($day);
            foreach ($dar as $row) {
                $this->day_accesses_cache[$day][$row['project_id']][$row['user_id']] = 1;
            }
        }
    }
}
