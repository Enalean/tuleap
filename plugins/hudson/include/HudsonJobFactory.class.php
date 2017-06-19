<?php
/**
 * Copyright (c) Enalean, 2011 - 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Dashboard\User\UserDashboardController;


/**
 * Fetch and distribute HudsonJob (and keep them in cache).
 */
class HudsonJobFactory
{
    private $jobs = array();

    /**
     * Returns all HudsonJobs for one owner
     *
     * @param String $owner_type
     * @param Integer $owner_id
     * 
     * @return Array of HudsonJob
     */
    public function getAvailableJobs($owner_type, $owner_id) {
        if (!isset($this->jobs[$owner_type][$owner_id])) {
            if ($owner_type == UserDashboardController::LEGACY_DASHBOARD_TYPE) {
                $this->jobs[$owner_type][$owner_id] = $this->getJobsByUser($owner_id);
            } else {
                $this->jobs[$owner_type][$owner_id] = $this->getJobsByGroup($owner_id);
            }
        }
        return $this->jobs[$owner_type][$owner_id];
    }

    public function getJobsByGroup($group_id) {
        $dar = $this->getDao()->searchByGroupID($group_id);
        $jobs = array();
        foreach ($dar as $row) {
            try {
                $jobs[$row['job_id']] = $this->getHudsonJob($row['job_url'], $row['name']);
            } catch (Exception $e) {
                // Do not add unvalid jobs
            }
        }
        return $jobs;
    }

    public function getJobsByUser($user_id) {
        $dar = $this->getDao()->searchByUserID($user_id);
        $jobs = array();
        foreach ($dar as $row) {
            try {
                $jobs[$row['job_id']] = $this->getHudsonJob($row['job_url'], $row['name']);
            } catch (Exception $e) {
                // Do not add unvalid jobs
            }
        }
        return $jobs;
    }

    protected function getDao() {
        return new PluginHudsonJobDao(CodendiDataAccess::instance());
    }

    /**
     * @return HudsonJob
     */
    private function getHudsonJob($url, $name)
    {
        $http_client = new Http_Client();
        return new HudsonJob($url, $http_client, $name);
    }
}
