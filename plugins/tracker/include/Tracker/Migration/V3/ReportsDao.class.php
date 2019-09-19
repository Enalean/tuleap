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

class Tracker_Migration_V3_ReportsDao extends DataAccessObject
{
    public function create($tv3_id, $tv5_id, $group_id)
    {
        $this->insertReport($tv3_id, $tv5_id, $group_id);
        $this->insertReportCriteria($tv5_id);
    }

    private function insertReport($tv3_id, $tv5_id, $group_id)
    {
        $tv3_id   = $this->da->escapeInt($tv3_id);
        $tv5_id   = $this->da->escapeInt($tv5_id);
        $group_id = $this->da->escapeInt($group_id);

        $sql = "INSERT INTO tracker_report(old_id, project_id, tracker_id, is_default, user_id, name, description, current_renderer_id, is_query_displayed, is_in_expert_mode, expert_query)
                SELECT report_id as old_id, G2.group_id as project_id, $tv5_id, is_default, CASE R.scope WHEN 'I' THEN user_id ELSE NULL END AS user_id, R.name, R.description, 0, 1, 0, ''
                FROM artifact_report AS R
                INNER JOIN artifact_group_list AS G ON (G.group_id = $group_id AND G.group_artifact_id = $tv3_id)
                INNER JOIN artifact_group_list AS G2  ON (G.group_artifact_id = G2.group_artifact_id)
                WHERE R.report_id = 100";

        $this->update($sql);

        $sql = "INSERT INTO tracker_report(old_id, project_id, tracker_id, is_default, user_id, name, description, current_renderer_id, is_query_displayed, is_in_expert_mode, expert_query)
                SELECT report_id as old_id, group_id as project_id, $tv5_id, is_default, CASE R.scope
                   WHEN 'I' THEN user_id
                   ELSE NULL END AS user_id, R.name, R.description, 0, 1, 0, ''
                FROM artifact_report AS R INNER JOIN artifact_group_list AS G USING (group_artifact_id)
                WHERE R.report_id <> 100
                  AND R.group_artifact_id = $tv3_id";
        $this->update($sql);
    }

    private function insertReportCriteria($tv5_id)
    {
        $tv5_id   = $this->da->escapeInt($tv5_id);

        $sql = "INSERT INTO tracker_report_criteria(report_id, field_id, rank, is_advanced)
                SELECT R.id, F.id, place_query, 0
                FROM tracker_report AS R
                     INNER JOIN artifact_report_field AS RF ON (R.old_id = RF.report_id)
                     INNER JOIN tracker_field AS F ON(F.name = RF.field_name AND F.tracker_id = $tv5_id)
                WHERE show_on_query = 1
                    AND R.tracker_id = $tv5_id
                ORDER BY R.id, place_query";
        $this->update($sql);

        $this->update("SET @counter = 0");
        $this->update("SET @previous = NULL");

        $sql = "UPDATE tracker_report_criteria
                INNER JOIN (SELECT @counter := IF(@previous = report_id, @counter + 1, 1) AS new_rank,
                           @previous := report_id,
                           tracker_report_criteria.*
                    FROM tracker_report_criteria
                    ORDER BY report_id, rank, field_id
                    ) as R1 USING(report_id,field_id)
                INNER JOIN tracker_report ON (tracker_report.id = tracker_report_criteria.report_id AND tracker_report.tracker_id = $tv5_id)
                    SET tracker_report_criteria.rank = R1.new_rank";
        $this->update($sql);
    }
}
