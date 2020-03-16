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

class Tracker_NotificationDao extends DataAccessObject
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_notification';
    }

    public function searchNotification($tracker_id, $user_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $user_id    = $this->da->escapeInt($user_id);
        $sql = "SELECT role_label,event_label,notify 
                FROM $this->table_name" . "_role AS r, $this->table_name" . "_event AS e, $this->table_name AS n 
                WHERE n.tracker_id=" . db_ei($tracker_id) . " 
                  AND n.user_id=" . db_ei($user_id) . " 
                  AND n.role_id=r.role_id 
                  AND r.tracker_id=" . db_ei($tracker_id) . " 
                  AND n.event_id=e.event_id 
                  AND e.tracker_id=" . db_ei($tracker_id);
        return $this->retrieve($sql);
    }

    public function searchRoles($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name" . "_role
                WHERE tracker_id = $tracker_id 
                ORDER BY rank ASC";
        return $this->retrieve($sql);
    }

    public function searchEvents($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name" . "_event
                WHERE tracker_id = $tracker_id 
                ORDER BY rank ASC";
        return $this->retrieve($sql);
    }
}
