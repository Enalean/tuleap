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

class Tracker_CannedResponseDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_canned_response';
    }

    public function searchById($tracker_id, $id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $id = $this->da->escapeInt($id);
        $sql = "SELECT * 
                FROM $this->table_name
                WHERE id = $id 
                  AND tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }

    public function searchByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT * 
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                ORDER BY title ASC";
        return $this->retrieve($sql);
    }

    public function create($tracker_id, $title, $body)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $title      = $this->da->quoteSmart($title);
        $body       = $this->da->quoteSmart($body);
        $sql = "INSERT INTO $this->table_name (tracker_id, title, body)
                VALUES ($tracker_id, $title, $body)";
        return $this->updateAndGetLastId($sql);
    }

    public function save($canned)
    {
        $id         = $this->da->escapeInt($canned->id);
        $title      = $this->da->quoteSmart($canned->title);
        $body       = $this->da->quoteSmart($canned->body);
        $sql = "UPDATE $this->table_name 
                SET title      = $title, 
                    body       = $body
                WHERE id = $id";
        return $this->update($sql);
    }

    public function delete($id)
    {
        $id = $this->da->escapeInt($id);
        $sql = "DELETE FROM $this->table_name 
                WHERE id = $id";
        return $this->update($sql);
    }
}
