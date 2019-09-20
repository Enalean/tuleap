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


class Cardwall_OnTop_Dao extends DataAccessObject
{

    /**
     * @return bool
     */
    public function isEnabled($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT NULL
                FROM plugin_cardwall_on_top
                WHERE tracker_id = $tracker_id
                LIMIT 1";
        $result = $this->retrieve($sql);
        return $result && count($result) == 1;
    }

    public function enable($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "INSERT INTO plugin_cardwall_on_top(tracker_id)
                VALUES ($tracker_id)";
        return $this->update($sql);
    }

    public function disable($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "DELETE FROM plugin_cardwall_on_top
                WHERE tracker_id = $tracker_id";
        return $this->update($sql);
    }

    /**
     * @return bool
     */
    public function isFreestyleEnabled($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT NULL
                FROM plugin_cardwall_on_top
                WHERE tracker_id = $tracker_id
                AND use_freestyle_columns = 1
                LIMIT 1";
        $result = $this->retrieve($sql);
        return $result && count($result) == 1;
    }

    public function enableFreestyleColumns($tracker_id)
    {
        return $this->updateFreestyleColumns($tracker_id, 1);
    }

    public function disableFreestyleColumns($tracker_id)
    {
        return $this->updateFreestyleColumns($tracker_id, 0);
    }

    private function updateFreestyleColumns($tracker_id, $value)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $value      = $this->da->escapeInt($value);
        $sql = "UPDATE plugin_cardwall_on_top
                SET use_freestyle_columns = $value
                WHERE tracker_id = $tracker_id";
        return $this->update($sql);
    }

    public function duplicate($from_tracker_id, $to_tracker_id)
    {
        $from_tracker_id = $this->da->escapeInt($from_tracker_id);
        $to_tracker_id   = $this->da->escapeInt($to_tracker_id);
        $sql = "INSERT INTO plugin_cardwall_on_top(tracker_id, use_freestyle_columns)
                SELECT $to_tracker_id, use_freestyle_columns
                FROM plugin_cardwall_on_top
                WHERE tracker_id = $from_tracker_id";
        if ($this->update($sql)) {
            return $this->da->affectedRows();
        }
        return false;
    }
}
