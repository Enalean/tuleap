<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\SVN\Logs;

use Project;
use DataAccessObject;

class QueryBuilder extends DataAccessObject
{
    public const ONE_DAY  = 86400;
    public const NA_LABEL = 'N/A';

    public function buildQuery(Project $project, $span, $who)
    {
        $project_id           = $this->da->escapeInt($project->getID());
        $date                 = $this->da->escapeInt($this->formatSpanDate($span));
        $user_where_condition = $this->exportUserCondition($project, $who);

        $read_label   = $this->da->quoteSmart(dgettext('tuleap-svn', 'Read'));
        $write_label  = $this->da->quoteSmart(dgettext('tuleap-svn', 'Write'));
        $browse_label = $this->da->quoteSmart(dgettext('tuleap-svn', 'Browse'));
        $na_label     = $this->da->quoteSmart(self::NA_LABEL);

        $query = "SELECT
                    user.user_name AS user_name,
                    user.realname AS realname,
                    user.email AS email,
                    plugin_svn_repositories.name AS title,
                    UNIX_TIMESTAMP(plugin_svn_full_history.day) AS time,
                    $na_label AS local_time,
                    $read_label AS type
                  FROM plugin_svn_full_history
                    INNER JOIN plugin_svn_repositories ON (plugin_svn_full_history.repository_id = plugin_svn_repositories.id)
                    INNER JOIN groups ON (
                        groups.group_id = plugin_svn_repositories.project_id
                        AND plugin_svn_repositories.project_id = $project_id
                    )
                    INNER JOIN user USING (user_id)
                  WHERE plugin_svn_full_history.day >= $date
                  AND svn_read_operations > 0
                  $user_where_condition
                UNION
                    SELECT
                    user.user_name AS user_name,
                    user.realname AS realname,
                    user.email AS email,
                    plugin_svn_repositories.name AS title,
                    UNIX_TIMESTAMP(plugin_svn_full_history.day) AS time,
                    $na_label AS local_time,
                    $write_label AS type
                  FROM plugin_svn_full_history
                    INNER JOIN plugin_svn_repositories ON (plugin_svn_full_history.repository_id = plugin_svn_repositories.id)
                    INNER JOIN groups ON (
                        groups.group_id = plugin_svn_repositories.project_id
                        AND plugin_svn_repositories.project_id = $project_id
                    )
                    INNER JOIN user USING (user_id)
                  WHERE plugin_svn_full_history.day >= $date
                  AND svn_write_operations > 0
                  $user_where_condition
                UNION
                    SELECT
                    user.user_name AS user_name,
                    user.realname AS realname,
                    user.email AS email,
                    plugin_svn_repositories.name AS title,
                    UNIX_TIMESTAMP(plugin_svn_full_history.day) AS time,
                    $na_label AS local_time,
                    $browse_label AS type
                  FROM plugin_svn_full_history
                    INNER JOIN plugin_svn_repositories ON (plugin_svn_full_history.repository_id = plugin_svn_repositories.id)
                    INNER JOIN groups ON (
                        groups.group_id = plugin_svn_repositories.project_id
                        AND plugin_svn_repositories.project_id = $project_id
                    )
                    INNER JOIN user USING (user_id)
                  WHERE plugin_svn_full_history.day >= $date
                  AND svn_browse_operations > 0
                  $user_where_condition
                ORDER BY time DESC";

        return $query;
    }

    private function formatSpanDate($span)
    {
        $time_back = localtime((time() - ($span * self::ONE_DAY)), 1);

        // Adjust to midnight this day
        $time_back["tm_sec"] = $time_back["tm_min"] = $time_back["tm_hour"] = 0;
        $begin_date          = mktime(
            $time_back["tm_hour"],
            $time_back["tm_min"],
            $time_back["tm_sec"],
            $time_back["tm_mon"] + 1,
            $time_back["tm_mday"],
            $time_back["tm_year"] + 1900
        );

        return date('Ymd', $begin_date);
    }

    private function exportUserCondition(Project $project, $who)
    {
        if ($who === "allusers") {
            $condition = "";
        } else {
            $users = $this->da->escapeIntImplode($project->getMembersId());
            if ($who === "members") {
                $condition = " AND user.user_id IN ($users) ";
            } else {
                $condition = " AND user.user_id NOT IN ($users) ";
            }
        }

        return $condition;
    }
}
