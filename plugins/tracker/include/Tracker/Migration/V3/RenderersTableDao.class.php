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

class Tracker_Migration_V3_RenderersTableDao extends DataAccessObject
{
    public function create($tv3_id, $tv5_id)
    {
        $this->insertRendererTable($tv3_id, $tv5_id);
    }

    private function insertRendererTable($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO tracker_report_renderer(report_id, renderer_type, name, description, rank)
                SELECT id, 'table', 'Results', '', 1
                FROM tracker_report
                WHERE tracker_id = $tv5_id";
        $this->update($sql);

        //TODO : check it is useful (added by SE)
        $sql = "INSERT INTO tracker_report_renderer_table(renderer_id, chunksz, multisort)
                SELECT RR.id, 15, 0
                FROM tracker_report_renderer RR
                    INNER JOIN tracker_report R ON (R.id = RR.report_id AND R.tracker_id = $tv5_id)";
        $this->update($sql);

        $sql = "INSERT INTO tracker_report_renderer_table_columns(renderer_id, field_id, rank, width)
                SELECT TRR.id, TF.id, ARF.place_result, 0
                FROM artifact_report_field AS ARF
                    INNER JOIN artifact_report AS R ON ((ARF.report_id = 100 AND R.group_artifact_id = 100) OR (R.report_id = ARF.report_id AND R.group_artifact_id = $tv3_id))
                    INNER JOIN tracker_report AS TR ON (TR.old_id = ARF.report_id AND TR.tracker_id = $tv5_id)
                    INNER JOIN tracker_field AS TF ON (TF.name = ARF.field_name AND TF.tracker_id = TR.tracker_id AND TF.use_it = 1)
                    INNER JOIN tracker_report_renderer AS TRR ON (TRR.report_id = TR.id)
                WHERE ARF.show_on_result = 1
                ORDER BY TRR.id, ARF.place_result";
        $this->update($sql);

        $this->update("SET @counter = 0");
        $this->update("SET @previous = NULL");

        $sql = "UPDATE tracker_report_renderer_table_columns 
                INNER JOIN (SELECT @counter := IF(@previous = renderer_id, @counter + 1, 1) AS new_rank, 
                                   @previous := renderer_id, 
                                   tracker_report_renderer_table_columns.* 
                            FROM tracker_report_renderer_table_columns 
                            ORDER BY renderer_id, rank, field_id
                ) as R1 USING(renderer_id,field_id)
                INNER JOIN tracker_report_renderer ON (tracker_report_renderer.id = tracker_report_renderer_table_columns.renderer_id)
                INNER JOIN tracker_report ON (tracker_report_renderer.report_id = tracker_report.id AND tracker_report.tracker_id = $tv5_id)
                SET tracker_report_renderer_table_columns.rank = R1.new_rank";
        $this->update($sql);
    }
}
