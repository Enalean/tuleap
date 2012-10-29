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

 class Tracker_Migration_V3_RenderersGraphDao extends DataAccessObject {
    public function create($tv3_id, $tv5_id) {
        $this->insertRendererTable($tv3_id, $tv5_id);
        $this->createReportForGraphWithoutReport($tv3_id, $tv5_id);
        $this->createReportCriteriaForGraphWithoutReport($tv3_id, $tv5_id);
        $this->insertRendererGraph($tv3_id, $tv5_id);
        $this->reorderRenderer();
        $this->updateGraphChartsWithFirstReport();
    }
    
    private function insertRendererTable($tv3_id, $tv5_id) {
        $tv3_id = $this->da->escapeInt($tv3_id);
        $tv5_id = $this->da->escapeInt($tv5_id);
        
        $sql = "INSERT INTO tracker_report_renderer(old_id, report_id, renderer_type, name, description, rank)
                SELECT G.report_graphic_id, R.id, 'plugin_graphontrackersv5', G.name, G.description, 2
                FROM plugin_graphontrackers_report_graphic AS G, tracker_report AS R
                WHERE G.user_id = R.user_id AND G.scope <> 'P' AND G.group_artifact_id = $tv3_id AND R.tracker_id = $tv5_id";
        $this->update($sql);
    }
    
    private function createReportForGraphWithoutReport($tv3_id, $tv5_id) {
        $tv3_id = $this->da->escapeInt($tv3_id);
        $tv5_id = $this->da->escapeInt($tv5_id);
        
        $sql = "INSERT INTO tracker_report(old_id, project_id, tracker_id, is_default, user_id, name, description, current_renderer_id, is_query_displayed)
                SELECT DISTINCT G.report_graphic_id, T.group_id, G.group_artifact_id, 0, G.user_id, G.name, G.description, 0,1
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
    
    private function createReportCriteriaForGraphWithoutReport($tv3_id, $tv5_id) {
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
    
    private function insertRendererGraph($tv3_id, $tv5_id) {
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
    
    private function alter() {
    }
    
    private function reorderRenderer() {
        $this->update("SET @counter = 0");
        $this->update("SET @previous = NULL");

        $sql = "UPDATE tracker_report_renderer 
                    INNER JOIN (SELECT @counter := IF(@previous = report_id, @counter + 1, 1) AS new_rank, 
                                       @previous := report_id, 
                                       tracker_report_renderer.*
                                FROM tracker_report_renderer
                                ORDER BY report_id, rank, id
                    ) as R1 USING(report_id, id)
                SET tracker_report_renderer.rank = R1.new_rank";
        $this->update($sql);
    }
    
    private function updateGraphChartsWithFirstReport() {
        $sql = "UPDATE tracker_report_renderer AS R
                    INNER JOIN (SELECT tracker_id, MIN(id) AS min_report_id FROM tracker_report GROUP BY tracker_id) AS M 
                        ON (M.min_report_id = R.report_id AND R.renderer_type='plugin_graphontrackersv5')
                    INNER JOIN plugin_graphontrackers_chart AS C ON (R.old_id = C.report_graphic_id)
                SET C.old_report_graphic_id = C.report_graphic_id, 
                    C.report_graphic_id = R.id";
       $this->update($sql);
       
       $sql = "INSERT INTO plugin_graphontrackersv5_chart(report_graphic_id, old_report_graphic_id, old_id, rank, chart_type, title,description, width, height)
               SELECT Re.id, Re.old_id, C.id, C.rank, C.chart_type, C.title, C.description, C.width, C.height
               FROM (SELECT tracker_id, MIN(id) AS min_report_id FROM tracker_report GROUP BY tracker_id) AS M
                   INNER JOIN tracker_report AS R ON (M.tracker_id = R.tracker_id)
                   INNER JOIN tracker_report_renderer AS Re ON (R.id = Re.report_id AND M.min_report_id < Re.report_id AND Re.renderer_type='plugin_graphontrackersv5')
                   INNER JOIN plugin_graphontrackers_chart AS C ON (Re.old_id = C.old_report_graphic_id)";
       $this->update($sql);
                   
    }
    
 }
 ?>
