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


class Cardwall_OnTop_ColumnMappingFieldValueDao extends DataAccessObject {

    public function searchMappingFieldValues($cardwall_tracker_id) {
        $cardwall_tracker_id = $this->da->escapeInt($cardwall_tracker_id);
        $sql = "SELECT *
                FROM plugin_cardwall_on_top_column_mapping_field_value
                WHERE cardwall_tracker_id = $cardwall_tracker_id";
        return $this->retrieve($sql);
    }

    public function save($cardwall_tracker_id, $tracker_id, $field_id, $value_id, $column_id) {
        $cardwall_tracker_id = $this->da->escapeInt($cardwall_tracker_id);
        $tracker_id          = $this->da->escapeInt($tracker_id);
        if ($field_id === null) {
            $field_id = " NULL ";
        } else {
            $field_id = $this->da->escapeInt($field_id);
        }
        $sql = "REPLACE INTO plugin_cardwall_on_top_column_mapping_field_value (cardwall_tracker_id, tracker_id, field_id, value_id, column_id)
                VALUES ($cardwall_tracker_id, $tracker_id, $field_id, $value_id, $column_id)";
        if ($this->update($sql)) {
            return $this->da->affectedRows();
        }
    }

    public function deleteForColumn($cardwall_tracker_id, $column_id) {
        $cardwall_tracker_id = $this->da->escapeInt($cardwall_tracker_id);
        $column_id           = $this->da->escapeInt($column_id);
        $sql = "DELETE FROM plugin_cardwall_on_top_column_mapping_field_value
                WHERE cardwall_tracker_id = $cardwall_tracker_id
                AND column_id  = $column_id";
        return $this->update($sql);
    }

    public function deleteAllFieldValues($cardwall_tracker_id, $tracker_id, $field_id, $column_id) {
        $cardwall_tracker_id = $this->da->escapeInt($cardwall_tracker_id);
        $tracker_id          = $this->da->escapeInt($tracker_id);
        $field_id            = $this->da->escapeInt($field_id);
        $column_id           = $this->da->escapeInt($column_id);
        $sql = "DELETE FROM plugin_cardwall_on_top_column_mapping_field_value
                WHERE cardwall_tracker_id = $cardwall_tracker_id
                AND tracker_id = $tracker_id
                AND field_id   = $field_id
                AND column_id  = $column_id";
        return $this->update($sql);
    }

    public function delete($cardwall_tracker_id, $tracker_id) {
        $cardwall_tracker_id = $this->da->escapeInt($cardwall_tracker_id);
        $tracker_id          = $this->da->escapeInt($tracker_id);
        $sql = "DELETE FROM plugin_cardwall_on_top_column_mapping_field_value
                WHERE tracker_id          = $tracker_id
                  AND cardwall_tracker_id = $cardwall_tracker_id";
        return $this->update($sql);
    }

    public function duplicate($from_cardwall_tracker_id, $to_cardwall_tracker_id, array $tracker_mapping, array $field_mapping, array $column_mapping) {
        //throw new Exception('Todo');
        $from_cardwall_tracker_id = $this->da->escapeInt($from_cardwall_tracker_id);
        $to_cardwall_tracker_id   = $this->da->escapeInt($to_cardwall_tracker_id);

        $to_value_stmt     = " CASE value_id ";

        $to_field_stmt     = " CASE field_id ";
        foreach ($field_mapping as $mapping) {
            $from         = $this->da->escapeInt($mapping['from']);
            $to           = $this->da->escapeInt($mapping['to']);
            $to_field_stmt .= " WHEN $from THEN $to ";

            foreach ($mapping['values'] as $from_value_id => $to_value_id) {
                $from_value_id  = $this->da->escapeInt($from_value_id);
                $to_value_id    = $this->da->escapeInt($to_value_id);
                $to_value_stmt .= " WHEN $from_value_id THEN $to_value_id ";
            }
        }
        $to_value_stmt .= " ELSE NULL ";
        $to_value_stmt .= " END ";

        $to_field_stmt .= " ELSE NULL ";
        $to_field_stmt .= " END ";

        $to_tracker_stmt = " CASE tracker_id ";
        foreach ($tracker_mapping as $from_tracker_id => $to_tracker_id) {
            $from         = $this->da->escapeInt($from_tracker_id);
            $to           = $this->da->escapeInt($to_tracker_id);
            $to_tracker_stmt .= " WHEN $from THEN $to ";
        }
        $to_tracker_stmt .= " ELSE NULL ";
        $to_tracker_stmt .= " END ";

        $to_column_stmt = $this->associativeToSQLCase($column_mapping, 'column_id');

        $sql = "INSERT INTO plugin_cardwall_on_top_column_mapping_field_value (cardwall_tracker_id, tracker_id, field_id, value_id, column_id)
                SELECT $to_cardwall_tracker_id, $to_tracker_stmt, $to_field_stmt, $to_value_stmt, $to_column_stmt
                FROM plugin_cardwall_on_top_column_mapping_field_value
                WHERE cardwall_tracker_id = $from_cardwall_tracker_id";
        return $this->update($sql);
    }

    private function associativeToSQLCase(array $mapping, $field_name) {
        $stmt = " CASE $field_name ";
        foreach ($mapping as $from => $to) {
            $from  = $this->da->escapeInt($from);
            $to    = $this->da->escapeInt($to);
            $stmt .= " WHEN $from THEN $to ";
        }
        $stmt .= " ELSE NULL ";
        $stmt .= " END ";
        return $stmt;
    }
}
?>
