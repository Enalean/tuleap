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

class Tracker_Report_CriteriaDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_report_criteria';
    }

    public function searchById($id)
    {
        $id  = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE id = $id ";
        return $this->retrieve($sql);
    }

    public function searchByReportId($report_id)
    {
        $report_id  = $this->da->escapeInt($report_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE report_id = $report_id
                ORDER BY rank";
        return $this->retrieve($sql);
    }

    public function delete($report_id, $field_id)
    {
        $report_id = $this->da->escapeInt($report_id);
        $field_id  = $this->da->escapeInt($field_id);
        $sql = "DELETE FROM $this->table_name WHERE report_id = $report_id AND field_id = $field_id";
        return $this->update($sql);
    }

    public function deleteAll($report_id)
    {
        $report_id = $this->da->escapeInt($report_id);
        $sql = "DELETE FROM $this->table_name WHERE report_id = $report_id";
        return $this->update($sql);
    }

    public function create($report_id, $field_id, $is_advanced = 0)
    {
        $report_id    = $this->da->escapeInt($report_id);
        $field_id     = $this->da->escapeInt($field_id);
        $rank         = (int) $this->prepareRanking('tracker_report_criteria', 0, $report_id, 'end', 'id', 'report_id');
        $is_advanced  = $this->da->escapeInt($is_advanced);
        $sql = "INSERT INTO $this->table_name
                       (report_id, field_id, rank, is_advanced)
                VALUES ($report_id, $field_id, $rank, $is_advanced)";
        return $this->updateAndGetLastId($sql);
    }
    //TODO : remove this method
    public function toggleAdvanced($report_id, $field_id)
    {
        $report_id = $this->da->escapeInt($report_id);
        $field_id  = $this->da->escapeInt($field_id);
        $sql = "UPDATE $this->table_name
                SET is_advanced = 1 - is_advanced
                WHERE report_id = $report_id AND field_id = $field_id";
        return $this->retrieve($sql);
    }

    public function duplicate($from_report_id, $to_report_id, $field_mapping)
    {
        $from_report_id = $this->da->escapeInt($from_report_id);
        $to_report_id   = $this->da->escapeInt($to_report_id);
        $sql = "INSERT INTO $this->table_name (report_id, field_id, rank, is_advanced)
                SELECT $to_report_id, field_id, rank, is_advanced
                FROM $this->table_name
                WHERE report_id = $from_report_id";
        $this->update($sql);

        foreach ($field_mapping as $mapping) {
            $from  = $this->da->escapeInt($mapping['from']);
            $to    = $this->da->escapeInt($mapping['to']);
            $sql = "UPDATE $this->table_name
                    SET field_id = $to
                    WHERE report_id = $to_report_id
                      AND field_id = $from";
            $this->update($sql);
        }
    }
}
