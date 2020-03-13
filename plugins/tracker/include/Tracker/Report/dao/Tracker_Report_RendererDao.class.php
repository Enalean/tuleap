<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_Report_RendererDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_report_renderer';
    }

    public function searchById($id)
    {
        $id      = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE id = $id ";
        return $this->retrieve($sql);
    }

    public function searchByReportId($report_id)
    {
        $report_id = $this->da->escapeInt($report_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE report_id = $report_id
                ORDER BY rank, name";
        return $this->retrieve($sql);
    }

    public function searchByIdAndReportId($id, $report_id)
    {
        $id      = $this->da->escapeInt($id);
        $report_id = $this->da->escapeInt($report_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE id = $id AND report_id = $report_id";
        return $this->retrieve($sql);
    }

    public function create($report_id, $type, $name, $description, $rank)
    {
        $report_id   = $this->da->escapeInt($report_id);
        $type        = $this->da->quoteSmart($type);
        $name        = $this->da->quoteSmart($name);
        $description = $this->da->quoteSmart($description);
        $rank        = (int) $this->prepareRanking('tracker_report_renderer', 0, $report_id, $rank, 'id', 'report_id');
        $sql = "INSERT INTO $this->table_name
                (report_id, renderer_type, name, description, rank)
                VALUES ($report_id, $type, $name, $description, $rank)";
        return $this->updateAndGetLastId($sql);
    }

    public function move($id, $report_id, $rank)
    {
        $id   = $this->da->escapeInt($id);
        $report_id   = $this->da->escapeInt($report_id);
        $rank = (int) $this->prepareRanking('tracker_report_renderer', $id, $report_id, $rank, 'id', 'report_id');
        $sql = "UPDATE $this->table_name SET rank = $rank WHERE id = $id";
        return $this->update($sql);
    }

    public function save($id, $name, $description, $rank)
    {
        $id          = $this->da->escapeInt($id);
        $name        = $this->da->quoteSmart($name);
        $description = $this->da->quoteSmart($description);
        $rank        = $this->da->escapeInt($rank);
        $sql = "UPDATE $this->table_name SET
                   name          = $name,
                   description   = $description,
                   rank          = $rank
                WHERE id = $id ";
        return $this->update($sql);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM $this->table_name WHERE id = " . $this->da->escapeInt($id);
        return $this->update($sql);
    }

    public function rename($id, $name, $description)
    {
        $id   = $this->da->escapeInt($id);
        $name = $this->da->quoteSmart($name);
        $description = $this->da->quoteSmart($description);
        $sql = "UPDATE $this->table_name SET
                   name        = $name,
                   description = $description
                WHERE id = $id ";
        return $this->update($sql);
    }

    public function duplicate($from_renderer_id, $to_report_id)
    {
        $from_renderer_id = $this->da->escapeInt($from_renderer_id);
        $to_report_id     = $this->da->escapeInt($to_report_id);
        $sql = "INSERT INTO $this->table_name (report_id, renderer_type, name, description, rank)
                SELECT $to_report_id, renderer_type, name, description, rank
                FROM $this->table_name
                WHERE id = $from_renderer_id";
        return $this->updateAndGetLastId($sql);
    }

    public function forceOrder($report_id, $renderers_order)
    {
        $report_id = $this->da->escapeInt($report_id);
        $case = array();
        foreach ($renderers_order as $rank => $id) {
            $rank = $this->da->escapeInt($rank);
            $id   = $this->da->escapeInt($id);

            $case[] = "WHEN $id THEN $rank ";
        }
        if (count($case)) {
            $case = 'CASE id ' . implode('', $case) . ' ELSE rank END';
            $sql = "UPDATE $this->table_name
                    SET rank = $case
                    WHERE report_id = $report_id";
            return $this->update($sql);
        }
    }
}
