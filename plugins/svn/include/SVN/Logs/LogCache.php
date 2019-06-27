<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

use DateTime;

class LogCache
{
    public const WRITE = 'write';
    public const READ  = 'read';

    private $cache         = array();
    private $cache_core    = array();
    private $project_names = array();
    private $user_names    = array();
    private $last_access_date_cache = array();

    public function add($project_name, $repository_name, $user_name, $action_type, DateTime $date)
    {
        $this->updateLastDate($user_name, $date);
        $day = $this->getDay($date);
        if (! isset($this->cache[$project_name][$repository_name][$user_name][$day])) {
            $this->cache[$project_name][$repository_name][$user_name][$day] = array(
                self::READ  => 0,
                self::WRITE => 0,
            );
        }
        $this->cache[$project_name][$repository_name][$user_name][$day][$action_type]++;
        $this->project_names[$project_name] = 1;
        $this->user_names[$user_name] = 1;
    }

    public function addCore($project_name, $user_name, $action_type, DateTime $date)
    {
        $this->updateLastDate($user_name, $date);
        $day = $this->getDay($date);
        if (! isset($this->cache_core[$project_name][$user_name][$day])) {
            $this->cache_core[$project_name][$user_name][$day] = [
                self::READ  => 0,
                self::WRITE => 0,
            ];
        }
        $this->cache_core[$project_name][$user_name][$day][$action_type]++;
    }

    private function getDay(DateTime $date)
    {
        return $date->format('Ymd');
    }

    public function getProjects()
    {
        return $this->cache;
    }

    public function getCoreProjects()
    {
        return $this->cache_core;
    }

    public function getCoreProjectNames()
    {
        return array_keys($this->cache_core);
    }

    public function getProjectNames()
    {
        return array_keys($this->project_names);
    }

    public function getUserNames()
    {
        return array_keys($this->user_names);
    }

    public function getLastAccessTimestamps()
    {
        $users = array();
        foreach ($this->last_access_date_cache as $username => $date) {
            $users[$username] = $date->format('U');
        }
        return $users;
    }

    private function updateLastDate($user_name, $date)
    {
        if (isset($this->last_access_date_cache[$user_name])) {
            if ($date > $this->last_access_date_cache[$user_name]) {
                $this->last_access_date_cache[$user_name] = $date;
            }
        } else {
            $this->last_access_date_cache[$user_name] = $date;
        }
    }
}
