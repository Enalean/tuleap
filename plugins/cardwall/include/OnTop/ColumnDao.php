<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Tracker\TrackerEventTrackersDuplicated;

class Cardwall_OnTop_ColumnDao extends DataAccessObject
{
    public function searchColumnsByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "SELECT *
                FROM plugin_cardwall_on_top_column
                WHERE tracker_id = $tracker_id
                ORDER BY id";
        return $this->retrieve($sql);
    }

    /**
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface|false
     */
    public function searchByColumnId(int $column_id)
    {
        $column_id = $this->da->escapeInt($column_id);
        $sql       = "SELECT *
            FROM plugin_cardwall_on_top_column
            WHERE id = $column_id";
        return $this->retrieve($sql);
    }

    public function createWithcolor($tracker_id, $label, $red, $green, $blue)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $label      = $this->da->quoteSmart($label);
        $red        = $this->da->escapeInt($red, CODENDI_DB_NULL);
        $green      = $this->da->escapeInt($green, CODENDI_DB_NULL);
        $blue       = $this->da->escapeInt($blue, CODENDI_DB_NULL);

        $sql = "INSERT INTO plugin_cardwall_on_top_column (tracker_id, label, bg_red, bg_green, bg_blue)
                VALUES ($tracker_id, $label, $red, $green, $blue)";

        return $this->updateAndGetLastId($sql);
    }

    public function createWithTLPColor($tracker_id, $label, $tlp_color_name)
    {
        $tracker_id     = $this->da->escapeInt($tracker_id);
        $label          = $this->da->quoteSmart($label);
        $tlp_color_name = $this->da->quoteSmart($tlp_color_name);

        $sql = "INSERT INTO plugin_cardwall_on_top_column (tracker_id, label, bg_red, bg_green, bg_blue, tlp_color_name)
                VALUES ($tracker_id, $label, null, null, null, $tlp_color_name)";

        return $this->updateAndGetLastId($sql);
    }

    public function create($tracker_id, $label)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $label      = $this->da->quoteSmart($label);

        $sql = "INSERT INTO plugin_cardwall_on_top_column (tracker_id, label)
                VALUES ($tracker_id, $label)";

        return $this->updateAndGetLastId($sql);
    }

    public function save($tracker_id, $id, $label, $red, $green, $blue)
    {
        $id         = $this->da->escapeInt($id);
        $tracker_id = $this->da->escapeInt($tracker_id);
        $label      = $this->da->quoteSmart($label);
        $red        = $this->da->escapeInt($red);
        $green      = $this->da->escapeInt($green);
        $blue       = $this->da->escapeInt($blue);
        $sql        = "UPDATE plugin_cardwall_on_top_column
                SET label = $label,
                    bg_red = $red,
                    bg_green = $green,
                    bg_blue = $blue,
                    tlp_color_name = null
                WHERE tracker_id = $tracker_id
                  AND id = $id";
        if ($this->update($sql)) {
            return $this->da->affectedRows();
        }
    }

    public function delete($tracker_id, $id)
    {
        $id         = $this->da->escapeInt($id);
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "DELETE FROM plugin_cardwall_on_top_column
                WHERE tracker_id = $tracker_id
                  AND id = $id";
        return $this->update($sql);
    }

    public function duplicate($from_tracker_id, $to_tracker_id, TrackerEventTrackersDuplicated $event): void
    {
        $from_tracker_id = $this->da->escapeInt($from_tracker_id);
        $to_tracker_id   = $this->da->escapeInt($to_tracker_id);
        $sql             = "SELECT id
                FROM plugin_cardwall_on_top_column
                WHERE tracker_id = $from_tracker_id
                ORDER BY id ASC";
        $mapping         = [];
        foreach ($this->retrieve($sql) as $row) {
            $from_column_id = $row['id'];
            $sql            = "INSERT INTO plugin_cardwall_on_top_column (tracker_id, label, bg_red, bg_green, bg_blue, tlp_color_name)
                    SELECT $to_tracker_id, label, bg_red, bg_green, bg_blue, tlp_color_name
                    FROM plugin_cardwall_on_top_column
                    WHERE id = $from_column_id";

            if ($to_column_id = $this->updateAndGetLastId($sql)) {
                $mapping[$from_column_id] = $to_column_id;
            }
        }
        $event->mapping_registry->setCustomMapping('plugin_cardwall_column_mapping', $mapping);
    }

    public function saveTlpColor($tracker_id, $id, $label, $tlp_color_name)
    {
        $id             = $this->da->escapeInt($id);
        $tracker_id     = $this->da->escapeInt($tracker_id);
        $label          = $this->da->quoteSmart($label);
        $tlp_color_name = $this->da->quoteSmart($tlp_color_name);

        $sql = "UPDATE plugin_cardwall_on_top_column
                SET label = $label,
                    bg_red = null,
                    bg_green = null,
                    bg_blue = null,
                    tlp_color_name = $tlp_color_name
                WHERE tracker_id = $tracker_id
                  AND id = $id";
        if ($this->update($sql)) {
            return $this->da->affectedRows();
        }
    }
}
