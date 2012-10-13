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


class Cardwall_OnTop_ColumnDao extends DataAccessObject {

    public function searchColumnsByTrackerId($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM plugin_cardwall_on_top_column
                WHERE tracker_id = $tracker_id
                ORDER BY id";
        return $this->retrieve($sql);
    }

    public function create($tracker_id, $label) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $label      = $this->da->quoteSmart($label);
        $sql = "INSERT INTO plugin_cardwall_on_top_column (tracker_id, label)
                VALUES ($tracker_id, $label)";
        return $this->update($sql);
    }

    public function save($tracker_id, $id, $label) {
        $id         = $this->da->escapeInt($id);
        $tracker_id = $this->da->escapeInt($tracker_id);
        $label      = $this->da->quoteSmart($label);
        $sql = "UPDATE plugin_cardwall_on_top_column
                SET label = $label
                WHERE tracker_id = $tracker_id
                  AND id = $id";
        if ($this->update($sql)) {
            return $this->da->affectedRows();
        }
    }

    public function delete($tracker_id, $id) {
        $id         = $this->da->escapeInt($id);
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "DELETE FROM plugin_cardwall_on_top_column
                WHERE tracker_id = $tracker_id
                  AND id = $id";
        return $this->update($sql);
    }

    public function duplicate($from_tracker_id, $to_tracker_id, &$mapping) {
        $from_tracker_id = $this->da->escapeInt($from_tracker_id);
        $to_tracker_id   = $this->da->escapeInt($to_tracker_id);
        $sql = "SELECT id
                FROM plugin_cardwall_on_top_column
                WHERE tracker_id = $from_tracker_id
                ORDER BY id ASC";
        $mapping['plugin_cardwall_column_mapping'] = array();
        $at_least_on_column = false;
        foreach ($this->retrieve($sql) as $row) {
            $from_column_id = $row['id'];
            $sql = "INSERT INTO plugin_cardwall_on_top_column (tracker_id, label, bg_red, bg_green, bg_blue)
                    SELECT $to_tracker_id, label, bg_red, bg_green, bg_blue
                    FROM plugin_cardwall_on_top_column
                    WHERE id = $from_column_id";
            if ($to_column_id = $this->updateAndGetLastId($sql)) {
                $at_least_on_column = true;
                $mapping['plugin_cardwall_column_mapping'][$from_column_id] = $to_column_id;
            }
        }
    }
}
?>
