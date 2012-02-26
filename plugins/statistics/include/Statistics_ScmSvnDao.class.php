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

/**
 * DAO class for SVN statistics
 */
class Statistics_ScmSvnDao extends DataAccessObject {

    var $groupFilter = '';

    /**
     * Constructor of the class
     *
     * @param DataAccess $da      Data access details
     * @param Integer    $groupId Project Id
     *
     * @return void
     */
    function __construct(DataAccess $da, $groupId = null) {
        parent::__construct($da);
        if ($groupId) {
            $this->groupFilter = ' AND group_id='.$this->da->escapeInt($groupId);
        }
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
        $sql = "SELECT MONTHNAME(STR_TO_DATE(MONTH(day), '%m')) AS month,
                YEAR(day) AS year,
                svn_checkouts + svn_access_count + svn_browse AS count,
                COUNT(DISTINCT(group_id)) AS projects,
                COUNT(DISTINCT(user_id)) AS users
                FROM group_svn_full_history
                WHERE day BETWEEN DATE_FORMAT(".$this->da->quoteSmart($startDate).", '%Y%m%d') AND DATE_FORMAT(".$this->da->quoteSmart($endDate).", '%Y%m%d')
                  ".$this->groupFilter."
                GROUP BY YEAR(day), MONTH(day)";

        return $this->retrieve($sql);
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
        $sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(date), '%M') AS month,
                YEAR(FROM_UNIXTIME(date)) AS year,
                COUNT(*) AS count,
                COUNT(DISTINCT(group_id)) AS projects,
                COUNT(DISTINCT(whoid)) AS users
                FROM svn_commits
                WHERE date BETWEEN UNIX_TIMESTAMP(".$this->da->quoteSmart($startDate).") AND UNIX_TIMESTAMP(".$this->da->quoteSmart($endDate).")
                  ".$this->groupFilter."
                GROUP BY year, month";

        return $this->retrieve($sql);
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
        $sql = "SELECT unix_group_name AS project, SUM(svn_checkouts) + SUM(svn_access_count) + SUM(svn_browse) AS count
                FROM group_svn_full_history
                JOIN groups g USING (group_id)
                WHERE day BETWEEN DATE_FORMAT(".$this->da->quoteSmart($startDate).", '%Y%m%d') AND DATE_FORMAT(".$this->da->quoteSmart($endDate).", '%Y%m%d')
                GROUP BY project
                ORDER BY count DESC
                LIMIT 10";

        return $this->retrieve($sql);
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
        $sql = "SELECT unix_group_name AS project, COUNT(c.id) AS count
                FROM svn_commits c
                JOIN groups g USING (group_id)
                WHERE date BETWEEN UNIX_TIMESTAMP(".$this->da->quoteSmart($startDate).") AND UNIX_TIMESTAMP(".$this->da->quoteSmart($endDate).")
                GROUP BY project
                ORDER BY count DESC
                LIMIT 10";

        return $this->retrieve($sql);
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
        $sql = "SELECT user_name AS user, SUM(svn_checkouts) + SUM(svn_access_count) + SUM(svn_browse) AS count
                FROM group_svn_full_history
                JOIN user u USING (user_id)
                WHERE day BETWEEN DATE_FORMAT(".$this->da->quoteSmart($startDate).", '%Y%m%d') AND DATE_FORMAT(".$this->da->quoteSmart($endDate).", '%Y%m%d')
                  ".$this->groupFilter."
                GROUP BY user
                ORDER BY count DESC
                LIMIT 10";

        return $this->retrieve($sql);
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
        $sql = "SELECT user_name AS user, COUNT(c.id) AS count
                FROM svn_commits c
                JOIN user u ON user_id = whoid
                WHERE date BETWEEN UNIX_TIMESTAMP(".$this->da->quoteSmart($startDate).") AND UNIX_TIMESTAMP(".$this->da->quoteSmart($endDate).")
                  ".$this->groupFilter."
                GROUP BY user
                ORDER BY count DESC
                LIMIT 10";

        return $this->retrieve($sql);
    }

    /**
     * Number of SVN repo having handeled at least 1 commit
     * in the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    function repositoriesEvolutionForPeriod($startDate, $endDate) {
        $sql = "SELECT MONTH(FROM_UNIXTIME(date)) AS month,
                YEAR(FROM_UNIXTIME(date)) AS year ,
                COUNT(DISTINCT(repositoryid)) AS repo_count
                FROM svn_commits 
                WHERE date BETWEEN UNIX_TIMESTAMP(".$this->da->quoteSmart($startDate).") AND UNIX_TIMESTAMP(".$this->da->quoteSmart($endDate).")
                GROUP BY year, month";

        return $this->retrieve($sql);
    }

    /**
     * Number of repositories containing commits for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    function repositoriesWithCommit($startDate, $endDate) {
        $sql = "SELECT COUNT(DISTINCT(group_id)) AS count
                FROM svn_commits
                WHERE date BETWEEN UNIX_TIMESTAMP(".$this->da->quoteSmart($startDate).") AND UNIX_TIMESTAMP(".$this->da->quoteSmart($endDate).")";

        return $this->retrieve($sql);
    }

}

?>