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

class Tracker_Migration_V3_RenderersGraphDao extends DataAccessObject
{
    public function create($tv3_id, $tv5_id)
    {
        if (! $this->pluginIsInstalled()) {
            return;
        }
        $this->insertRendererTable($tv3_id, $tv5_id);
        $this->createReportForGraphWithoutReport($tv3_id, $tv5_id);
        $this->createReportCriteriaForGraphWithoutReport($tv3_id, $tv5_id);
        $this->insertRendererGraph($tv3_id, $tv5_id);
        //$this->alter();
        $this->reorderRenderer($tv5_id);
        $this->updateGraphChartsWithFirstReport($tv5_id);
        $this->createPieCharts($tv5_id);
        $this->createBarCharts($tv5_id);
        $this->createGanttCharts($tv5_id);
        $this->updateChartsWithFieldId($tv5_id);
    }

    private function pluginIsInstalled()
    {
        $sql_v5 = "SHOW TABLES LIKE 'plugin_graphontrackersv5_chart'";
        $sql_v3 = "SHOW TABLES LIKE 'plugin_graphontrackers_chart'";
        return count($this->retrieve($sql_v5)) > 0 && count($this->retrieve($sql_v3)) > 0;
    }

    private function insertRendererTable($tv3_id, $tv5_id)
    {
        $tv3_id = $this->da->escapeInt($tv3_id);
        $tv5_id = $this->da->escapeInt($tv5_id);

        $sql = "INSERT INTO tracker_report_renderer(old_id, report_id, renderer_type, name, description, rank)
                SELECT G.report_graphic_id, R.id, 'plugin_graphontrackersv5', G.name, G.description, 2
                FROM plugin_graphontrackers_report_graphic AS G, tracker_report AS R
                WHERE G.user_id = R.user_id AND G.scope <> 'P' AND G.group_artifact_id = $tv3_id AND R.tracker_id = $tv5_id";
        $this->update($sql);
    }

    private function createReportForGraphWithoutReport($tv3_id, $tv5_id)
    {
        $tv3_id = $this->da->escapeInt($tv3_id);
        $tv5_id = $this->da->escapeInt($tv5_id);

        $sql = "INSERT INTO tracker_report(old_id, project_id, tracker_id, is_default, user_id, name, description, current_renderer_id, is_query_displayed, is_in_expert_mode)
                SELECT DISTINCT G.report_graphic_id, T.group_id, $tv5_id, 0, G.user_id, G.name, G.description, 0, 1, 0
                FROM plugin_graphontrackers_report_graphic AS G, tracker_report AS R, tracker AS T
                WHERE G.scope <> 'P' AND
                      G.group_artifact_id = $tv3_id AND
                      R.tracker_id = $tv5_id AND
                      R.user_id IS NULL  AND
                      G.report_graphic_id NOT IN (
                        SELECT G.report_graphic_id
                        FROM plugin_graphontrackers_report_graphic AS G
                        INNER JOIN tracker_report AS R ON (G.user_id = R.user_id AND G.scope <> 'P' AND G.group_artifact_id = $tv3_id AND R.tracker_id = $tv5_id)
                       ) AND
                       R.tracker_id = T.id";
        $this->update($sql);
    }

    private function createReportCriteriaForGraphWithoutReport($tv3_id, $tv5_id)
    {
        $tv3_id = $this->da->escapeInt($tv3_id);
        $tv5_id = $this->da->escapeInt($tv5_id);

        $sql = "INSERT INTO tracker_report_criteria(report_id, field_id, rank, is_advanced)
                SELECT R.id, F.id, 1, 0
                FROM plugin_graphontrackers_report_graphic AS G, tracker_report AS R, tracker_field AS F
                WHERE G.scope <> 'P' AND
                      G.group_artifact_id = $tv3_id AND
                      R.tracker_id = $tv5_id AND
                      R.old_id = G.report_graphic_id AND
                      F.tracker_id = R.tracker_id AND
                      F.name = 'artifact_id'";
        $this->update($sql);
    }

    private function insertRendererGraph($tv3_id, $tv5_id)
    {
        $tv3_id = $this->da->escapeInt($tv3_id);
        $tv5_id = $this->da->escapeInt($tv5_id);

        $sql = "INSERT INTO tracker_report_renderer(old_id, report_id, renderer_type, name, description, rank)
                SELECT G.report_graphic_id, R.id, 'plugin_graphontrackersv5', G.name, G.description, 1
                FROM plugin_graphontrackers_report_graphic AS G, tracker_report AS R
                WHERE G.scope <> 'P' AND G.group_artifact_id = $tv3_id AND R.tracker_id = $tv5_id  AND R.old_id = G.report_graphic_id";
        $this->update($sql);

        $sql = "INSERT INTO tracker_report_renderer(old_id, report_id, renderer_type, name, description, rank)
                SELECT G.report_graphic_id, R.id, 'plugin_graphontrackersv5', G.name, G.description, 2
                FROM plugin_graphontrackers_report_graphic AS G, tracker_report AS R
                WHERE G.scope = 'P' AND G.group_artifact_id = $tv3_id AND R.tracker_id = $tv5_id";
        $this->update($sql);
    }

    private function reorderRenderer($tv5_id)
    {
        $this->update("SET @counter = 0");
        $this->update("SET @previous = NULL");

        $sql = "UPDATE tracker_report_renderer
                    INNER JOIN (SELECT @counter := IF(@previous = report_id, @counter + 1, 1) AS new_rank,
                                       @previous := report_id,
                                       tracker_report_renderer.*
                                FROM tracker_report_renderer
                                ORDER BY report_id, rank, id
                    ) as R1 USING(report_id, id)
                INNER JOIN tracker_report ON (tracker_report_renderer.report_id = tracker_report.id
                                              AND tracker_report.tracker_id = $tv5_id)
                SET tracker_report_renderer.rank = R1.new_rank";
        $this->update($sql);
    }

    private function updateGraphChartsWithFirstReport($tv5_id)
    {
        $tv5_id = $this->da->escapeInt($tv5_id);
        $sql = "INSERT INTO plugin_graphontrackersv5_chart(report_graphic_id, old_id, rank, chart_type, title,description, width, height)
                SELECT Re.id, C.id, C.rank, C.chart_type, C.title, C.description, C.width, C.height
                FROM (SELECT tracker_id, MIN(id) AS min_report_id FROM tracker_report WHERE tracker_id = $tv5_id GROUP BY tracker_id) AS M
                    INNER JOIN tracker_report AS R ON (M.tracker_id = R.tracker_id AND R.tracker_id = $tv5_id)
                    INNER JOIN tracker_report_renderer AS Re ON (R.id = Re.report_id AND Re.renderer_type='plugin_graphontrackersv5')
                    INNER JOIN plugin_graphontrackers_chart AS C ON (Re.old_id = C.report_graphic_id)";

        $this->update($sql);
    }

    private function createPieCharts($tv5_id)
    {
        $tv5_id = $this->da->escapeInt($tv5_id);
        $sql = "INSERT INTO plugin_graphontrackersv5_pie_chart(id, field_base)
                SELECT C.id, P.field_base
                FROM plugin_graphontrackers_pie_chart AS P
                    INNER JOIN plugin_graphontrackersv5_chart AS C
                        ON (C.chart_type='pie' AND C.old_id = P.id)
                    INNER JOIN tracker_report_renderer AS RR ON (C.report_graphic_id = RR.id)
                    INNER JOIN tracker_report AS R ON (R.tracker_id = $tv5_id AND R.id = RR.report_id)";
        $this->update($sql);
    }

    private function createBarCharts($tv5_id)
    {
        $tv5_id = $this->da->escapeInt($tv5_id);
        $sql = "INSERT INTO plugin_graphontrackersv5_bar_chart(id, field_base, field_group)
                SELECT C.id, B.field_base, B.field_group
                FROM plugin_graphontrackers_bar_chart AS B
                    INNER JOIN plugin_graphontrackersv5_chart AS C
                        ON (C.chart_type='bar' AND C.old_id = B.id)
                    INNER JOIN tracker_report_renderer AS RR ON (C.report_graphic_id = RR.id)
                    INNER JOIN tracker_report AS R ON (R.tracker_id = $tv5_id AND R.id = RR.report_id)";
        $this->update($sql);
    }

    private function createGanttCharts($tv5_id)
    {
        $tv5_id = $this->da->escapeInt($tv5_id);
        $sql = "INSERT INTO plugin_graphontrackersv5_gantt_chart(id, field_start, field_due, field_finish, field_percentage, field_righttext, scale, as_of_date, summary)
                SELECT C.id, G.field_start, G.field_due, G.field_finish, G.field_percentage, G.field_righttext, G.scale, G.as_of_date, G.summary
                FROM plugin_graphontrackers_gantt_chart AS G
                    INNER JOIN plugin_graphontrackersv5_chart AS C
                        ON (C.chart_type='gantt' AND C.old_id = G.id)
                    INNER JOIN tracker_report_renderer AS RR ON (C.report_graphic_id = RR.id)
                    INNER JOIN tracker_report AS R ON (R.tracker_id = $tv5_id AND R.id = RR.report_id)";
        $this->update($sql);
    }

    private function updateChartsWithFieldId($tv5_id)
    {
        $sql = "UPDATE plugin_graphontrackersv5_pie_chart AS A
                       INNER JOIN plugin_graphontrackersv5_chart AS C ON(A.id = C.id)
                       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
                       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
                       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_base
                                                                                    AND F.tracker_id = $tv5_id)
                SET A.field_base = F.id";
        $this->update($sql);

        $sql = "UPDATE plugin_graphontrackersv5_bar_chart AS A
                       INNER JOIN plugin_graphontrackersv5_chart AS C ON(A.id = C.id)
                       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
                       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
                       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_base
                                                                                    AND F.tracker_id = $tv5_id)
                SET A.field_base = F.id";
        $this->update($sql);

        $sql = "UPDATE plugin_graphontrackersv5_bar_chart AS A
                       INNER JOIN plugin_graphontrackersv5_chart AS C ON(A.id = C.id)
                       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
                       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
                       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_group
                                                                                    AND F.tracker_id = $tv5_id)
                SET A.field_group = F.id";
        $this->update($sql);

        $sql = "UPDATE plugin_graphontrackersv5_gantt_chart AS A
                       INNER JOIN plugin_graphontrackersv5_chart AS C ON(A.id = C.id)
                       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
                       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
                       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_start
                                                                                    AND F.tracker_id = $tv5_id)
                SET A.field_start = F.id";
        $this->update($sql);

        $sql = "UPDATE plugin_graphontrackersv5_gantt_chart AS A
                       INNER JOIN plugin_graphontrackersv5_chart AS C ON(A.id = C.id)
                       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
                       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
                       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_due
                                                                                    AND F.tracker_id = $tv5_id)
                SET A.field_due = F.id";
        $this->update($sql);

        $sql = "UPDATE plugin_graphontrackersv5_gantt_chart AS A
                       INNER JOIN plugin_graphontrackersv5_chart AS C ON(A.id = C.id)
                       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
                       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
                       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_finish
                                                                                    AND F.tracker_id = $tv5_id)
                SET A.field_finish = F.id";
        $this->update($sql);

        $sql = "UPDATE plugin_graphontrackersv5_gantt_chart AS A
                       INNER JOIN plugin_graphontrackersv5_chart AS C ON(A.id = C.id)
                       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
                       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
                       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_percentage
                                                                                    AND F.tracker_id = $tv5_id)
                SET A.field_percentage = F.id";
        $this->update($sql);

        $sql = "UPDATE plugin_graphontrackersv5_gantt_chart AS A
                       INNER JOIN plugin_graphontrackersv5_chart AS C ON(A.id = C.id)
                       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
                       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
                       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_righttext
                                                                                    AND F.tracker_id = $tv5_id)
                SET A.field_righttext = F.id";
        $this->update($sql);

        $sql = "UPDATE plugin_graphontrackersv5_gantt_chart AS A
                       INNER JOIN plugin_graphontrackersv5_chart AS C ON(A.id = C.id)
                       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
                       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
                       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.summary
                                                                                    AND F.tracker_id = $tv5_id)
                SET A.summary = F.id";
        $this->update($sql);
    }
}
