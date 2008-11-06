<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class ArtifactReportFieldDao extends DataAccessObject {
    function __construct($da) {
        parent::__construct($da);
        $this->table_name = 'artifact_report_field';
    }
    
    function prepareResultRanking($field_name, $report_id, $rank) {
        return $this->prepareRanking($field_name, $report_id, $rank, 'field_name', 'report_id', 'place_result');
    }
    
    function prepareQueryRanking($field_name, $report_id, $rank) {
        return $this->prepareRanking($field_name, $report_id, $rank, 'field_name', 'report_id', 'place_query');
    }
    
    function searchByReportIdAndFieldName($report_id, $field_name) {
        $sql = "SELECT *
                FROM ". $this->table_name ."
                WHERE field_name = ". $this->da->quoteSmart($field_name) ."
                  AND report_id  = ". $this->da->escapeInt($report_id);
        return $this->retrieve($sql);
    }
    
    function updateResultRanking($field_name, $report_id, $rank) {
        $rank = $this->prepareResultRanking($field_name, $report_id, $rank);
        $sql = "UPDATE ". $this->table_name ."
                SET place_result = ". $this->da->escapeInt($rank) ."
                WHERE field_name = ". $this->da->quoteSmart($field_name) ."
                  AND report_id  = ". $this->da->escapeInt($report_id);
        echo $sql . PHP_EOL;
        return $this->update($sql);
    }
}
?>
