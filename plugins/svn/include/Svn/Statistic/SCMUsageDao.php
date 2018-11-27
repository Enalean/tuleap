<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Svn\Statistic;

use DataAccessObject;

class SCMUsageDao extends DataAccessObject
{
    public function searchAccessesCount($start_date, $end_date, $project_id)
    {
        $start_date        = $this->getDa()->quoteSmart($start_date);
        $end_date          = $this->getDa()->quoteSmart($end_date);
        $filter_by_project = $this->getFilterByProject($project_id);

        $sql = "SELECT YEAR(day) AS year, MONTHNAME(day) AS month,
                    SUM(svn_read_operations) AS nb_read, SUM(svn_write_operations) AS nb_write,
                    SUM(svn_browse_operations) AS nb_browse
                FROM plugin_svn_full_history
                JOIN plugin_svn_repositories ON plugin_svn_repositories.id = plugin_svn_full_history.repository_id
                JOIN groups ON groups.group_id = plugin_svn_repositories.project_id
                WHERE day BETWEEN DATE_FORMAT($start_date, '%Y%m%d') AND DATE_FORMAT($end_date, '%Y%m%d')
                    $filter_by_project
                GROUP BY year, month
                ORDER BY year, MONTH(day)";

        return $this->retrieve($sql);
    }

    public function searchUsersAndProjectsCountWithReadOperations($start_date, $end_date, $project_id)
    {
        $start_date        = $this->getDa()->quoteSmart($start_date);
        $end_date          = $this->getDa()->quoteSmart($end_date);
        $filter_by_project = $this->getFilterByProject($project_id);

        $sql = "SELECT YEAR(day) AS year, MONTHNAME(day) AS month,
                    COUNT(DISTINCT(group_id)) AS nb_project, COUNT(DISTINCT(user_id)) AS nb_user
                FROM plugin_svn_full_history
                JOIN plugin_svn_repositories ON plugin_svn_repositories.id = plugin_svn_full_history.repository_id
                JOIN groups ON groups.group_id = plugin_svn_repositories.project_id
                WHERE day BETWEEN DATE_FORMAT($start_date, '%Y%m%d') AND DATE_FORMAT($end_date, '%Y%m%d')
                    AND svn_read_operations > 0
                    $filter_by_project
                GROUP BY year, month
                ORDER BY year, month";

        return $this->retrieve($sql);
    }

    public function searchUsersAndProjectsCountWithWriteOperations($start_date, $end_date, $project_id)
    {
        $start_date        = $this->getDa()->quoteSmart($start_date);
        $end_date          = $this->getDa()->quoteSmart($end_date);
        $filter_by_project = $this->getFilterByProject($project_id);

        $sql = "SELECT YEAR(day) AS year, MONTHNAME(day) AS month,
                    COUNT(DISTINCT(group_id)) AS nb_project, COUNT(DISTINCT(user_id)) AS nb_user
                FROM plugin_svn_full_history
                JOIN plugin_svn_repositories ON plugin_svn_repositories.id = plugin_svn_full_history.repository_id
                JOIN groups ON groups.group_id = plugin_svn_repositories.project_id
                WHERE day BETWEEN DATE_FORMAT($start_date, '%Y%m%d') AND DATE_FORMAT($end_date, '%Y%m%d')
                    AND svn_write_operations > 0
                    $filter_by_project
                GROUP BY year, month
                ORDER BY year, month";

        return $this->retrieve($sql);
    }

    public function searchTopProject($start_date, $end_date, $project_id)
    {
        $start_date        = $this->getDa()->quoteSmart($start_date);
        $end_date          = $this->getDa()->quoteSmart($end_date);
        $filter_by_project = $this->getFilterByProject($project_id);

        $sql = "SELECT unix_group_name AS project, SUM(svn_write_operations) AS nb_write
                FROM plugin_svn_full_history
                JOIN plugin_svn_repositories ON plugin_svn_repositories.id = plugin_svn_full_history.repository_id
                JOIN groups ON groups.group_id = plugin_svn_repositories.project_id
                WHERE day BETWEEN DATE_FORMAT($start_date, '%Y%m%d') AND DATE_FORMAT($end_date, '%Y%m%d')
                    $filter_by_project
                GROUP BY project
                ORDER BY nb_write DESC
                LIMIT 1";

        return $this->retrieve($sql);
    }

    public function searchTopUser($start_date, $end_date, $project_id)
    {
        $start_date        = $this->getDa()->quoteSmart($start_date);
        $end_date          = $this->getDa()->quoteSmart($end_date);
        $filter_by_project = $this->getFilterByProject($project_id);

        $sql = "SELECT user_name AS user, SUM(svn_write_operations) AS nb_write
                FROM plugin_svn_full_history
                JOIN user ON user.user_id = plugin_svn_full_history.user_id
                JOIN plugin_svn_repositories ON plugin_svn_repositories.id = plugin_svn_full_history.repository_id
                JOIN groups ON groups.group_id = plugin_svn_repositories.project_id
                WHERE day BETWEEN DATE_FORMAT($start_date, '%Y%m%d') AND DATE_FORMAT($end_date, '%Y%m%d')
                    $filter_by_project
                GROUP BY user
                ORDER BY nb_write DESC
                LIMIT 1";

        return $this->retrieve($sql);
    }

    /**
     * @return string
     */
    private function getFilterByProject($project_id)
    {
        if ($project_id !== null) {
            $project_id = $this->getDa()->escapeInt($project_id);
            return 'AND group_id = ' . $project_id;
        }
        return '';
    }
}
