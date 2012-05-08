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
 * DAO class for Git statistics
 */
class Statistics_ScmGitDao extends DataAccessObject {

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
            $this->groupFilter = ' AND project_id='.$this->da->escapeInt($groupId);
        }
    }

    /**
     * Count all Git pushes for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    function totalPushes($startDate, $endDate) {
        $sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(push_date), '%M') AS month,
                YEAR(FROM_UNIXTIME(push_date)) AS year,
                COUNT(*) AS count,
                COUNT(DISTINCT(repository_id)) AS repositories,
                COUNT(DISTINCT(user_id)) AS users
                FROM plugin_git_log
                JOIN plugin_git USING(repository_id)
                WHERE push_date BETWEEN UNIX_TIMESTAMP(".$this->da->quoteSmart($startDate).") AND UNIX_TIMESTAMP(".$this->da->quoteSmart($endDate).")
                  ".$this->groupFilter."
                 GROUP BY year, month";
                var_dump($sql);
        return $this->retrieve($sql);
    }
}

?>