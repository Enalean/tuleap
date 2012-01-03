<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

require_once 'pre.php';

/**
 * DAO class for SVN statistics
 */
class Statistics_ScmSvnDao {

    /**
     * Constructor of the class
     *
     * @param Integer $groupId   Project Id
     *
     * @return void
     */
    function __construct($groupId = null) {
    }

    /**
     * Count all SVN read access for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    function totalRead($startDate, $endDate) {
        $sql = "SELECT svn_checkouts, svn_access_count, svn_browse
                FROM group_svn_full_history
                WHERE day >= ".$this->da->quoteSmart($startDate)."
                  AND day < ".$this->da->quoteSmart($endDate);

        return db_query($sql);
    }

    /**
     * Count all SVN commits for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    function totalCommits($startDate, $endDate) {
        $sql = "SELECT svn_commits, svn_adds, svn_deletes
                FROM group_svn_full_history
                WHERE day >= ".$this->da->quoteSmart($startDate)."
                  AND day < ".$this->da->quoteSmart($endDate);

        return db_query($sql);
    }

    /**
     * Count SVN read access by project for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    function readByProject($startDate, $endDate) {
        $sql = "SELECT unix_group_name AS Project, SUM(svn_checkouts) AS checkouts, SUM(svn_access_count) AS access, SUM(svn_browse) AS browses
                FROM group_svn_full_history
                JOIN groups g USING (group_id)
                WHERE day >= ".$this->da->quoteSmart($startDate)."
                  AND day < ".$this->da->quoteSmart($endDate)."
                GROUP BY Project";

        return db_query($sql);
    }

    /**
     * Count SVN commits by project for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    function commitsByProject($startDate, $endDate) {
        $sql = "SELECT unix_group_name AS Project, SUM(svn_commits) AS commits, SUM(svn_adds) AS adds, SUM(svn_deletes) AS deletes
                FROM group_svn_full_history
                JOIN groups g USING (group_id)
                WHERE day >= ".$this->da->quoteSmart($startDate)."
                  AND day < ".$this->da->quoteSmart($endDate)."
                GROUP BY Project";

        return db_query($sql);
    }

    /**
     * Count SVN read access by user for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    function readByUser($startDate, $endDate) {
        $sql = "SELECT user_name AS User, SUM(svn_checkouts) AS checkouts, SUM(svn_access_count) AS access, SUM(svn_browse) AS browses
                FROM group_svn_full_history
                JOIN user u USING (user_id)
                WHERE day >= ".$this->da->quoteSmart($startDate)."
                  AND day < ".$this->da->quoteSmart($endDate)."
                GROUP BY User";

        return db_query($sql);
    }

    /**
     * Count SVN commits by user for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    function commitsByUser($startDate, $endDate) {
        $sql = "SELECT user_name AS User, SUM(svn_commits) AS commits, SUM(svn_adds) AS adds, SUM(svn_deletes) AS deletes
                FROM group_svn_full_history
                JOIN user u USING (user_id)
                WHERE day >= ".$this->da->quoteSmart($startDate)."
                  AND day < ".$this->da->quoteSmart($endDate)."
                GROUP BY User";

        return db_query($sql);
    }

}

?>