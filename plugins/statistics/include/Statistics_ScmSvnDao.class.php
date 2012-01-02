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
     * Count all SVN commits for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    function returnStatsFromDB($startDate, $endDate) {
        $sql = "SELECT COUNT(1) AS commits
                FROM svn_commits
                WHERE date > UNIX_TIMESTAMP('".$startDate."')
                  AND date < UNIX_TIMESTAMP('".$endDate."')";

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
    function returnStatsFromDBByProject($startDate, $endDate) {
        $sql = "SELECT unix_group_name AS Project, COUNT(1) AS Commits
                FROM svn_commits
                JOIN groups g USING (group_id)
                WHERE date > UNIX_TIMESTAMP('".$startDate."')
                  AND date < UNIX_TIMESTAMP('".$endDate."')
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
    function returnStatsFromDBByUser($startDate, $endDate) {
        $sql = "SELECT user_name AS User, COUNT(1) AS Commits
                FROM svn_commits s
                JOIN user u ON u.user_id = s.whoid
                WHERE date > UNIX_TIMESTAMP('".$startDate."')
                  AND date < UNIX_TIMESTAMP('".$endDate."')
                GROUP BY User
                ORDER BY Commits DESC";

        return db_query($sql);
    }

}

?>