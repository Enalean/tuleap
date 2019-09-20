<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class StatisticsAggregatorDao extends DataAccessObject
{

    public function addStatistic($project_id, $statistic_name)
    {
        $project_id     = $this->getDa()->escapeInt($project_id);
        $statistic_name = $this->getDa()->quoteSmart($statistic_name);
        $current_date   = $this->getDa()->quoteSmart(date('Y-m-d'));

        $sql = "INSERT INTO plugin_statistics_aggregator(project_id, day, name, value)
                VALUES($project_id, $current_date, $statistic_name, 1)
                ON DUPLICATE KEY UPDATE value=value+1";

        $this->update($sql);
    }

    public function getStatistics($statistic_name, $date_start, $date_end)
    {
        $statistic_name = $this->getDa()->quoteSmart($statistic_name);
        $date_start     = $this->getDa()->quoteSmart($date_start);
        $date_end       = $this->getDa()->quoteSmart($date_end);

        $sql = "SELECT project_id AS group_id, SUM(value) AS result
                FROM plugin_statistics_aggregator
                WHERE (day BETWEEN $date_start AND $date_end) AND name=$statistic_name
                GROUP BY project_id";

        return $this->retrieve($sql);
    }
}
