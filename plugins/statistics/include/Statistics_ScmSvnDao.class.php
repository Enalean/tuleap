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

    var $startDate;
    var $endDate;

    /**
     * Constructor of the class
     *
     * @param String  $startDate Period start date
     * @param String  $endDate   Period end date
     * @param Integer $groupId   Project Id
     *
     * @return void
     */
    function __construct($startDate, $endDate, $groupId = null) {
        $this->startDate      = str_replace('-', '', $startDate);
        $this->endDate        = str_replace('-', '', $endDate);
    }

    /**
     * Count all SVN commits for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    function totalCommits() {
        $sql = "SELECT svn_commits, svn_adds, svn_deletes
                FROM group_svn_full_history
                WHERE day > ".$this->startDate."
                  AND day < ".$this->endDate;

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
    function commitsByProject() {
        $sql = "SELECT unix_group_name AS Project, SUM(svn_commits) AS commits, SUM(svn_adds) AS adds, SUM(svn_deletes) AS deletes
                FROM group_svn_full_history
                JOIN groups g USING (group_id)
                WHERE day > ".$this->startDate."
                  AND day < ".$this->endDate."
                GROUP BY Project
                ORDER BY Commits DESC";

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
    function commitsByUser() {
        $sql = "SELECT user_name AS User, SUM(svn_commits) AS commits, SUM(svn_adds) AS adds, SUM(svn_deletes) AS deletes
                FROM group_svn_full_history
                JOIN user u USING (user_id)
                WHERE day > ".$this->startDate."
                  AND day < ".$this->endDate."
                GROUP BY User
                ORDER BY Commits DESC";

        return db_query($sql);
    }

}

?>