<?php
/**
 * Copyright (c) STMicroelectronics, 2016. All Rights Reserved.
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


class TrackerPublicKeyDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'plugin_tracker_encryption_key';
    }

    public function retrieveKey($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT key_content
                FROM $this->table_name
                WHERE tracker_id=" . $tracker_id;
        return $this->retrieve($sql);
    }

    public function insertKey($tracker_id, $key_content)
    {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $key_content = $this->da->quoteSmart($key_content);
        $sql = "REPLACE INTO $this->table_name
                (key_content, tracker_id)
                VALUES ($key_content, $tracker_id)";
        return $this->update($sql);
    }

    public function deleteKey($tracker_id)
    {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $sql = "DELETE FROM $this->table_name
                WHERE tracker_id = $tracker_id";
        return $this->update($sql);
    }
}
