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


class Cardwall_OnTop_ColumnMappingFieldDao extends DataAccessObject
{

    public function searchMappingFields($cardwall_tracker_id)
    {
        $cardwall_tracker_id = $this->da->escapeInt($cardwall_tracker_id);
        $sql = "SELECT t1.id AS tracker_id, m.field_id AS field_id
                FROM
                    tracker AS t1
                    INNER JOIN tracker AS t2 ON (
                        t2.group_id = t1.group_id
                        AND t2.id = $cardwall_tracker_id
                        AND t1.deletion_date IS NULL
                    )
                    LEFT JOIN plugin_cardwall_on_top_column_mapping_field AS m
                           ON (t1.id = m.tracker_id AND m.cardwall_tracker_id = $cardwall_tracker_id)";
        return $this->retrieve($sql);
    }

    public function create($cardwall_tracker_id, $tracker_id, $field_id)
    {
        $cardwall_tracker_id = $this->da->escapeInt($cardwall_tracker_id);
        $tracker_id          = $this->da->escapeInt($tracker_id);
        if ($field_id === null) {
            $field_id = " NULL ";
        } else {
            $field_id = $this->da->escapeInt($field_id);
        }
        $sql = "INSERT INTO plugin_cardwall_on_top_column_mapping_field (cardwall_tracker_id, tracker_id, field_id)
                VALUES ($cardwall_tracker_id, $tracker_id, $field_id)";
        return $this->update($sql);
    }

    public function save($cardwall_tracker_id, $tracker_id, $field_id)
    {
        $cardwall_tracker_id = $this->da->escapeInt($cardwall_tracker_id);
        $tracker_id          = $this->da->escapeInt($tracker_id);
        if ($field_id === null) {
            $field_id = " NULL ";
        } else {
            $field_id = $this->da->escapeInt($field_id);
        }
        $sql = "REPLACE INTO plugin_cardwall_on_top_column_mapping_field (cardwall_tracker_id, tracker_id, field_id)
                VALUES ($cardwall_tracker_id, $tracker_id, $field_id)";
        if ($this->update($sql)) {
            return $this->da->affectedRows();
        }
    }

    public function delete($cardwall_tracker_id, $tracker_id)
    {
        $cardwall_tracker_id = $this->da->escapeInt($cardwall_tracker_id);
        $tracker_id          = $this->da->escapeInt($tracker_id);
        $sql = "DELETE FROM plugin_cardwall_on_top_column_mapping_field
                WHERE tracker_id          = $tracker_id
                  AND cardwall_tracker_id = $cardwall_tracker_id";
        return $this->update($sql);
    }

    public function deleteCardwall($cardwall_tracker_id)
    {
        $cardwall_tracker_id = $this->da->escapeInt($cardwall_tracker_id);
        $sql = "DELETE FROM plugin_cardwall_on_top_column_mapping_field
                WHERE cardwall_tracker_id = $cardwall_tracker_id";
        return $this->update($sql);
    }

    public function duplicate($from_cardwall_tracker_id, $to_cardwall_tracker_id, $tracker_mapping, $field_mapping)
    {
        $from_cardwall_tracker_id = $this->da->escapeInt($from_cardwall_tracker_id);
        $to_cardwall_tracker_id   = $this->da->escapeInt($to_cardwall_tracker_id);
        $to_field_stmt     = " CASE field_id ";
        foreach ($field_mapping as $mapping) {
            $from           = $this->da->escapeInt($mapping['from']);
            $to             = $this->da->escapeInt($mapping['to']);
            $to_field_stmt .= " WHEN $from THEN $to ";
        }
        $to_field_stmt .= " ELSE NULL ";
        $to_field_stmt .= " END ";

        $to_tracker_stmt = " CASE tracker_id ";
        foreach ($tracker_mapping as $from_tracker_id => $to_tracker_id) {
            $from             = $this->da->escapeInt($from_tracker_id);
            $to               = $this->da->escapeInt($to_tracker_id);
            $to_tracker_stmt .= " WHEN $from THEN $to ";
        }
        $to_tracker_stmt .= " ELSE NULL ";
        $to_tracker_stmt .= " END ";

        $sql = "INSERT INTO plugin_cardwall_on_top_column_mapping_field (cardwall_tracker_id, tracker_id, field_id)
                SELECT $to_cardwall_tracker_id, $to_tracker_stmt, $to_field_stmt
                FROM plugin_cardwall_on_top_column_mapping_field
                WHERE cardwall_tracker_id = $from_cardwall_tracker_id";
        return $this->update($sql);
    }
}
