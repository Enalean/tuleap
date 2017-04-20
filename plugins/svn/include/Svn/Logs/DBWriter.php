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

namespace Tuleap\Svn\Logs;

use UserManager;

class DBWriter
{
    /**
     * @var DBWriterDao
     */
    private $dao;
    /**
     * @var UserManager
     */
    private $user_manager;
    private $users_cache        = array();
    private $repositories_cache = array();
    private $day_accesses_cache = array();

    public function __construct(DBWriterDao $dao, UserManager $user_manager)
    {
        $this->dao          = $dao;
        $this->user_manager = $user_manager;
    }

    public function saveFromFile($filename)
    {
        $parser = new Parser();
        $this->save($parser->parse($filename));
    }

    public function save(LogCache $log_cache)
    {
        $this->cacheRepositoryIds($log_cache->getProjectNames());
        foreach ($log_cache->getProjects() as $project_name => $repositories) {
            foreach ($repositories as $repository_name => $user_names) {
                $repository_id = $this->repositories_cache[$project_name][$repository_name];
                foreach ($user_names as $user_name => $days) {
                    $user_id = $this->getUserId($user_name);
                    foreach ($days as $day => $actions) {
                        if ($this->hasRecord($repository_id, $user_id, $day)) {
                            $this->dao->updateAccess($repository_id, $user_id, $day, $actions[LogCache::READ], $actions[LogCache::WRITE]);
                        } else {
                            $this->dao->insertAccess($repository_id, $user_id, $day, $actions[LogCache::READ], $actions[LogCache::WRITE]);
                        }
                    }
                }
            }
        }
        foreach ($log_cache->getLastAccessTimestamps() as $username => $timestamp) {
            $this->dao->updateLastAccessDate($this->getUserId($username), $timestamp);
        }
    }

    private function cacheRepositoryIds(array $project_names)
    {
        if (count($project_names) > 0) {
            $dar = $this->dao->searchRepositoriesForProjects($project_names);
            foreach ($dar as $row) {
                $this->repositories_cache[$row['project_name']][$row['repository_name']] = (int) $row['repository_id'];
            }
        }
    }

    private function getUserId($user_name)
    {
        if (! isset($this->users_cache[$user_name])) {
            $this->users_cache[$user_name] = 0;
            $user = $this->user_manager->findUser($user_name);
            if ($user !== null) {
                $this->users_cache[$user_name] = $user->getId();
            }
        }
        return $this->users_cache[$user_name];
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
                $this->day_accesses_cache[$day][$row['repository_id']][$row['user_id']] = 1;
            }
        }
    }
}
