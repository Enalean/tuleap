<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

/**
 *  Data Access Object for PluginHudsonJob
 */
class PluginHudsonJobDao extends DataAccessObject
{
    /**
    * Gets all jobs in the db
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchAll()
    {
        $sql = "SELECT * FROM plugin_hudson_job";
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginHudsonJob by Codendi group ID
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchByGroupID($group_id)
    {
        $sql = sprintf(
            "SELECT *
                        FROM plugin_hudson_job
                        WHERE group_id = %s",
            $this->da->quoteSmart($group_id)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginHudsonJob by job ID
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchByJobID($job_id)
    {
        $sql = sprintf(
            "SELECT *
                        FROM plugin_hudson_job
                        WHERE job_id = %s",
            $this->da->quoteSmart($job_id)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginHudsonJob by job name
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchByJobName($job_name, $group_id)
    {
        $sql = sprintf(
            "SELECT *
                        FROM plugin_hudson_job
                        WHERE name = %s AND group_id = %s",
            $this->da->quoteSmart($job_name),
            $this->da->quoteSmart($group_id)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginHudsonJob by user ID
    * means "all the jobs of all projects the user is member of"
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function searchByUserID($user_id)
    {
        $sql = sprintf(
            "SELECT j.*
                        FROM plugin_hudson_job j, user u, user_group ug
                        WHERE ug.group_id = j.group_id AND
                              u.user_id = ug.user_id AND
                              u.user_id = %s",
            $this->da->quoteSmart($user_id)
        );
        return $this->retrieve($sql);
    }

    /**
    * create a row in the table plugin_hudson_job
    * @return inserted job id if there is no error
    */
    public function createHudsonJob(
        $project_id,
        $hudson_job_url,
        $job_name,
        $use_svn_trigger,
        $token,
        $svn_paths,
    ) {
        $project_id      = $this->da->quoteSmart($project_id);
        $hudson_job_url  = $this->da->quoteSmart($hudson_job_url);
        $job_name        = $this->da->quoteSmart($job_name);
        $use_svn_trigger = $this->da->escapeInt($use_svn_trigger);
        $token           = ($token !== null) ? $this->da->quoteSmart($token) : $this->da->quoteSmart('');
        $svn_paths       = $this->da->quoteSmart($svn_paths);

        $sql = "INSERT INTO plugin_hudson_job (group_id, job_url, name, use_svn_trigger, token, svn_paths)
                VALUES ($project_id, $hudson_job_url, $job_name, $use_svn_trigger, $token, $svn_paths)";

        return $this->updateAndGetLastId($sql);
    }

    public function updateHudsonJob(
        $job_id,
        $hudson_job_url,
        $job_name,
        $use_svn_trigger,
        $token,
        $svn_paths,
    ) {
        $job_id          = $this->da->quoteSmart($job_id);
        $hudson_job_url  = $this->da->quoteSmart($hudson_job_url);
        $job_name        = $this->da->quoteSmart($job_name);
        $use_svn_trigger = $this->da->escapeInt($use_svn_trigger);
        $token           = ($token !== null) ? $this->da->quoteSmart($token) : $this->da->quoteSmart('');
        $svn_paths       = $this->da->quoteSmart($svn_paths);

        $sql = "UPDATE plugin_hudson_job
                SET job_url = $hudson_job_url,
                name = $job_name,
                use_svn_trigger = $use_svn_trigger,
                token = $token,
                svn_paths = $svn_paths
                WHERE job_id = $job_id";

        return $this->update($sql);
    }

    public function deleteHudsonJob($job_id)
    {
        $sql     = sprintf(
            "DELETE FROM plugin_hudson_job WHERE job_id = %s",
            $this->da->quoteSmart($job_id)
        );
        $updated = $this->update($sql);
        return $updated;
    }

    public function deleteHudsonJobsByGroupID($group_id)
    {
        $sql     = sprintf(
            "DELETE FROM plugin_hudson_job WHERE group_id = %s",
            $this->da->quoteSmart($group_id)
        );
        $updated = $this->update($sql);
        return $updated;
    }

    /**
    * Get jobs number
    *
    * @param int $groupId Id of the project
    *
    * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
    */
    public function countJobs($groupId = null)
    {
        $condition = '';
        if ($groupId) {
            $condition = "AND group_id = " . $this->da->escapeInt($groupId);
        }
        $sql = "SELECT COUNT(*) AS count
                FROM plugin_hudson_job
                JOIN `groups` USING (group_id)
                WHERE status = 'A'
                  " . $condition;
        return $this->retrieve($sql);
    }
}
