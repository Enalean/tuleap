<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
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
use Tuleap\Hudson\MinimalHudsonJob;

/**
 * Fetch and distribute HudsonJob (and keep them in cache).
 */
class MinimalHudsonJobFactory // @codingStandardsIgnoreLine
{
    public const API_XML = '/api/xml';

    private $jobs = [];

    /**
     * Returns all HudsonJobs for one owner
     *
     * @param String $owner_type
     * @param int $owner_id
     *
     * @return MinimalHudsonJob[]
     */
    public function getAvailableJobs($owner_type, $owner_id)
    {
        if (! isset($this->jobs[$owner_type][$owner_id])) {
            if ($owner_type == UserDashboardController::LEGACY_DASHBOARD_TYPE) {
                $this->jobs[$owner_type][$owner_id] = $this->getJobsByUser($owner_id);
            } else {
                $this->jobs[$owner_type][$owner_id] = $this->getJobsByGroup($owner_id);
            }
        }
        return $this->jobs[$owner_type][$owner_id];
    }

    private function getJobsByGroup($group_id)
    {
        $dar = $this->getDao()->searchByGroupID($group_id);
        $jobs = [];
        foreach ($dar as $row) {
            try {
                $jobs[$row['job_id']] = $this->getMinimalHudsonJob($row['job_url'], $row['name']);
            } catch (Exception $e) {
                // Do not add unvalid jobs
            }
        }
        return $jobs;
    }

    /**
     * @param $user_id
     * @return MinimalHudsonJob[]
     */
    private function getJobsByUser($user_id)
    {
        $dar = $this->getDao()->searchByUserID($user_id);
        $jobs = [];
        foreach ($dar as $row) {
            try {
                $jobs[$row['job_id']] = $this->getMinimalHudsonJob($row['job_url'], $row['name']);
            } catch (Exception $e) {
                // Do not add unvalid jobs
            }
        }
        return $jobs;
    }

    private function getDao()
    {
        return new PluginHudsonJobDao(CodendiDataAccess::instance());
    }

    /**
     * @return MinimalHudsonJob
     * @throws HudsonJobURLMalformedException
     */
    public function getMinimalHudsonJob($url, $name)
    {
        return new MinimalHudsonJob($name, $this->getJobUrl($url));
    }

    /**
     * @return string
     * @throws HudsonJobURLMalformedException
     */
    private function getJobUrl($url)
    {
        $parsed_url = parse_url($url);

        if (! $parsed_url || ! array_key_exists('scheme', $parsed_url)) {
            throw new HudsonJobURLMalformedException(sprintf(dgettext('tuleap-hudson', 'Wrong Job URL: %1$s'), $url));
        }

        $matches = [];
        if (preg_match(Jenkins_Client::BUILD_WITH_PARAMETERS_REGEXP, $url, $matches)) {
             return $matches['job_url'] . self::API_XML;
        }
        return $url . self::API_XML;
    }
}
