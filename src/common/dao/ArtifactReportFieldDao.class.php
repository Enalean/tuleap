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
class ArtifactReportFieldDao extends DataAccessObject
{
    public function __construct($da)
    {
        parent::__construct($da);
        $this->table_name = 'artifact_report_field';
    }

    public function prepareResultRanking($field_name, $report_id, $rank)
    {
        return $this->prepareRanking('artifact_report_field', $field_name, $report_id, $rank, 'field_name', 'report_id', 'place_result');
    }

    public function prepareQueryRanking($field_name, $report_id, $rank)
    {
        return $this->prepareRanking('artifact_report_field', $field_name, $report_id, $rank, 'field_name', 'report_id', 'place_query');
    }

    public function searchByReportIdAndFieldName($report_id, $field_name)
    {
        $sql = "SELECT *
                FROM " . $this->table_name . "
                WHERE field_name = " . $this->da->quoteSmart($field_name) . "
                  AND report_id  = " . $this->da->escapeInt($report_id);
        return $this->retrieve($sql);
    }

    public function updateResultRanking($field_name, $report_id, $rank)
    {
        $rank = $this->prepareResultRanking($field_name, $report_id, $rank);
        $sql = "UPDATE " . $this->table_name . "
                SET place_result = " . $this->da->escapeInt($rank) . "
                WHERE field_name = " . $this->da->quoteSmart($field_name) . "
                  AND report_id  = " . $this->da->escapeInt($report_id);
        echo $sql . PHP_EOL;
        return $this->update($sql);
    }

    public function resizeColumns($report_id, $new_sizes)
    {
        if (is_array($new_sizes) && count($new_sizes)) {
            $sql = '';
            $set = '';
            $where = '';
            $i = 0;
            foreach ($new_sizes as $field_name => $col_width) {
                if (!$sql) {
                    $sql .= " UPDATE ";
                } else {
                    $sql .= ", ";
                }
                $sql .= $this->table_name . " AS R_$i ";

                if (!$set) {
                    $set .= " SET ";
                } else {
                    $set .= ", ";
                }
                $set .= " R_$i.col_width = " . $this->da->escapeInt($col_width);

                if (!$where) {
                    $where .= " WHERE ";
                } else {
                    $where .= " AND ";
                }
                $where .= " R_$i.field_name = " . $this->da->quoteSmart($field_name);
                $where .= " AND R_$i.report_id  = " . $this->da->escapeInt($report_id);
                $i++;
            }
            $sql .= $set . $where;
            return $this->update($sql);
        }
        return false;
    }
}
