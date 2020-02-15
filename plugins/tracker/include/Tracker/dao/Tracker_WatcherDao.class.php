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

class Tracker_WatcherDao extends DataAccessObject
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_watcher';
    }

    public function searchWatchees($tracker_id, $user_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $user_id    = $this->da->escapeInt($user_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id 
                  AND user_id = $user_id";
        return $this->retrieve($sql);
    }

    public function searchWatchers($tracker_id, $watchee_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $watchee_id = $this->da->escapeInt($watchee_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id 
                  AND watchee_id = $watchee_id";
        return $this->retrieve($sql);
    }
}
