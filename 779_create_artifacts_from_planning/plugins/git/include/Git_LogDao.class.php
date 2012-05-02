<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/dao/include/DataAccessObject.class.php';

class Git_LogDao extends DataAccessObject {

    /**
     * Return the last push of a given repository
     *
     * @param Integer $repositoryId Id of the repository
     *
     * @return DataAccessResult
     */
    function searchLastPushForRepository($repositoryId) {
        $repositoryId = $this->da->escapeInt($repositoryId);
        $sql = "SELECT log.*
                FROM plugin_git_log log 
                WHERE repository_id = $repositoryId
                ORDER BY push_date DESC
                LIMIT 1";
        return $this->retrieve($sql);
    }

    /**
     * Return the last pushes of a given repository grouped by week
     *
     * @param Integer $repositoryId Id of the repository
     * @param Integer $week         Number of the week
     * @param Integer $year         Year corresponding to the week
     *
     * @return DataAccessResult
     */
    function getRepositoryPushesByWeek($repositoryId, $week, $year) {
        $repositoryId = $this->da->escapeInt($repositoryId);
        $week         = $this->da->escapeInt($week);
        $year         = $this->da->escapeInt($year);
        $sql          = "SELECT COUNT(*) AS pushes,
                             repository_id AS repo,
                             WEEK(FROM_UNIXTIME(push_date)) AS week,
                             YEAR(FROM_UNIXTIME(push_date)) AS year,
                             SUM(commits_number) AS commits
                         FROM plugin_git_log
                         WHERE repository_id = $repositoryId
                           AND WEEK(FROM_UNIXTIME(push_date)) = $week
                           AND YEAR(FROM_UNIXTIME(push_date))= $year
                         GROUP BY year, week, repo";
        return $this->retrieve($sql);
    }

    /**
     * Obtain last git pushes performed by the given user
     *
     * @param Integer $userId Id of the user
     * @param Integer $repoId Id of the git repository
     * @param Integer $offset Offset of the search
     * @param Integer $date   Date from which we start collecting logs
     *
     * @return DataAccessResult
     */
    function getLastPushesByUser($userId, $repoId, $offset, $date) {
        if ($repoId) {
            $condition = "AND l.repository_id = ".$this->da->escapeInt($repoId);
        } else {
            $condition = "";
        }
        if ($offset) {
            $limit = "LIMIT ".$this->da->escapeInt($offset);
        } else {
            $limit = "LIMIT 10";
        }
        $sql = "SELECT g.group_name, r.repository_name, l.push_date, SUM(l.commits_number) AS commits_number
                FROM plugin_git_log l
                JOIN plugin_git r ON l.repository_id = r.repository_id
                JOIN groups g ON g.group_id = r.project_id
                WHERE l.user_id = ".$this->da->escapeInt($userId)."
                  AND r.repository_deletion_date  = '0000-00-00 00:00:00'
                  AND g.status = 'A'
                  AND l.push_date > ".$this->da->escapeInt($date)."
                  ".$condition."
                GROUP BY l.push_date
                ORDER BY g.group_name, r.repository_name, l.push_date DESC
                ".$limit;
        return $this->retrieve($sql);
    }

    /**
     * Obtain repositories containing git pushes by a user in the last given period
     *
     * @param Integer $userId Id of the user
     * @param Integer $date   Date from which we start collecting repostories with pushes
     *
     * @return DataAccessResult
     */
    function getLastPushesRepositories($userId, $date) {
        $sql = "SELECT DISTINCT(r.repository_id), g.group_name, r.repository_name, r.repository_namespace, g.group_id
                FROM plugin_git_log l
                JOIN plugin_git r ON l.repository_id = r.repository_id
                JOIN groups g ON g.group_id = r.project_id
                WHERE l.user_id = ".$this->da->escapeInt($userId)."
                  AND r.repository_deletion_date  = '0000-00-00 00:00:00'
                  AND g.status = 'A'
                  AND l.push_date > ".$this->da->escapeInt($date)."
                ORDER BY g.group_id, r.repository_id, l.push_date DESC";
        return $this->retrieve($sql);
    }

    /**
     * Return the SQL Statement for logs daily pushs
     *
     * @param Integer $groupId
     * @param String $logsCond
     *
     * @return String
     */
    function getSqlStatementForLogsDaily($groupId, $logsCond) {
        return "SELECT log.push_date AS time,
                    user.user_name AS user_name,
                    user.realname AS realname, user.email AS email,
                    r.repository_name AS title
                FROM (SELECT *, push_date AS time from plugin_git_log) AS log, user, plugin_git AS r
                WHERE ".$logsCond."
                  AND r.project_id = ".$this->da->quoteSmart($groupId)."
                  AND log.repository_id = r.repository_id
                ORDER BY time DESC";
    }

}

?>
