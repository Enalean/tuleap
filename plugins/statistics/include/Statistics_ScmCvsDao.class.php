<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;

/**
 * DAO class for CVS statistics
 */
class Statistics_ScmCvsDao extends DataAccessObject
{

    public $groupFilter = '';

    /**
     * Constructor of the class
     *
     * @param LegacyDataAccessInterface $da Data access details
     * @param int $groupId Project Id
     *
     * @return void
     */
    public function __construct(LegacyDataAccessInterface $da, $groupId = null)
    {
        parent::__construct($da);
        if ($groupId) {
            $this->groupFilter = ' AND group_id=' . $this->da->escapeInt($groupId);
        }
    }

    /**
     * Count all CVS read access for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    public function totalRead($startDate, $endDate)
    {
        $sql = "SELECT MONTHNAME(STR_TO_DATE(MONTH(day), '%m')) AS month,
                YEAR(day) AS year,
                SUM(cvs_checkouts) + SUM(cvs_browse) AS count,
                COUNT(DISTINCT(group_id)) AS projects,
                COUNT(DISTINCT(user_id)) AS users
                FROM group_cvs_full_history
                WHERE day BETWEEN DATE_FORMAT(" . $this->da->quoteSmart($startDate) . ", '%Y%m%d') AND DATE_FORMAT(" . $this->da->quoteSmart($endDate) . ", '%Y%m%d')
                  " . $this->groupFilter . "
                GROUP BY YEAR(day), MONTH(day)
                ORDER BY YEAR(day), MONTH(day)";

        return $this->retrieve($sql);
    }

    /**
     * Count all CVS commits for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    public function totalCommits($startDate, $endDate)
    {
        $sql = "SELECT MONTHNAME(STR_TO_DATE(MONTH(day), '%m')) AS month,
                YEAR(day) AS year,
                SUM(cvs_commits) + SUM(cvs_adds) AS count,
                COUNT(DISTINCT(group_id)) AS projects,
                COUNT(DISTINCT(user_id)) AS users
                FROM group_cvs_full_history
                WHERE day BETWEEN DATE_FORMAT(" . $this->da->quoteSmart($startDate) . ", '%Y%m%d') AND DATE_FORMAT(" . $this->da->quoteSmart($endDate) . ", '%Y%m%d')
                  " . $this->groupFilter . "
                GROUP BY YEAR(day), MONTH(day)
                ORDER BY YEAR(day), MONTH(day)";

        return $this->retrieve($sql);
    }

    /**
     * Count CVS read access by project for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    public function readByProject($startDate, $endDate)
    {
        $sql = "SELECT unix_group_name AS project, SUM(cvs_checkouts) + SUM(cvs_browse) AS count
                FROM group_cvs_full_history
                JOIN groups g USING (group_id)
                WHERE day BETWEEN DATE_FORMAT(" . $this->da->quoteSmart($startDate) . ", '%Y%m%d') AND DATE_FORMAT(" . $this->da->quoteSmart($endDate) . ", '%Y%m%d')
                GROUP BY project
                ORDER BY count DESC
                LIMIT 10";

        return $this->retrieve($sql);
    }

    /**
     * Count CVS commits by project for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    public function commitsByProject($startDate, $endDate)
    {
        $sql = "SELECT unix_group_name AS project, SUM(cvs_commits) + SUM(cvs_adds) AS count
                FROM group_cvs_full_history
                JOIN groups g USING (group_id)
                WHERE day BETWEEN DATE_FORMAT(" . $this->da->quoteSmart($startDate) . ", '%Y%m%d') AND DATE_FORMAT(" . $this->da->quoteSmart($endDate) . ", '%Y%m%d')
                GROUP BY project
                ORDER BY count DESC
                LIMIT 10";

        return $this->retrieve($sql);
    }

    /**
     * Count CVS read access by user for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    public function readByUser($startDate, $endDate)
    {
        $sql = "SELECT user_name AS user, SUM(cvs_checkouts) + SUM(cvs_browse) AS count
                FROM group_cvs_full_history
                JOIN user u USING (user_id)
                WHERE day BETWEEN DATE_FORMAT(" . $this->da->quoteSmart($startDate) . ", '%Y%m%d') AND DATE_FORMAT(" . $this->da->quoteSmart($endDate) . ", '%Y%m%d')
                  " . $this->groupFilter . "
                GROUP BY user
                ORDER BY count DESC
                LIMIT 10";

        return $this->retrieve($sql);
    }

    /**
     * Count CVS commits by user for the given period
     *
     * @param String $startDate Period start date
     * @param String $endDate   Period end date
     *
     * @return DataAccessResult
     */
    public function commitsByUser($startDate, $endDate)
    {
        $sql = "SELECT user_name AS user, SUM(cvs_commits) + SUM(cvs_adds) AS count
                FROM group_cvs_full_history
                JOIN user u USING (user_id)
                WHERE day BETWEEN DATE_FORMAT(" . $this->da->quoteSmart($startDate) . ", '%Y%m%d') AND DATE_FORMAT(" . $this->da->quoteSmart($endDate) . ", '%Y%m%d')
                  " . $this->groupFilter . "
                GROUP BY user
                ORDER BY count DESC
                LIMIT 10";

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
    public function repositoriesWithCommit($startDate, $endDate)
    {
        $sql = "SELECT COUNT(DISTINCT(repositoryid)) AS count
                From cvs_commits cc
                JOIN cvs_checkins c ON cc.id = c.commitid
                WHERE cc.comm_when BETWEEN " . $this->da->quoteSmart($startDate) . " AND " . $this->da->quoteSmart($endDate);

        return $this->retrieve($sql);
    }
}
