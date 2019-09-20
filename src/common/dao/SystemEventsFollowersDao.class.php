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

require_once('include/DataAccessObject.class.php');

class SystemEventsFollowersDao extends DataAccessObject
{
    public function __construct($da)
    {
        parent::__construct($da);
        $this->table_name = 'system_events_followers';
    }

    public function searchAll()
    {
        $sql = "SELECT *
                FROM $this->table_name
                ORDER BY id ASC";
        return $this->retrieve($sql);
    }

    public function searchByType($type)
    {
        $type = $this->da->quoteLikeValueSurround($type);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE types LIKE $type
                ORDER BY id ASC";
        return $this->retrieve($sql);
    }

    public function create($emails, $types)
    {
        $emails = $this->da->quoteSmart($emails);
        $types  = $this->da->quoteSmart($types);
        $sql = "INSERT INTO $this->table_name(emails, types)
                VALUES ($emails, $types)";

        return $this->updateAndGetLastId($sql);
    }

    public function delete($id)
    {
        $id = $this->da->escapeInt($id);
        $sql = "DELETE FROM $this->table_name
                WHERE id = $id";
        return $this->update($sql);
    }

    public function save($id, $emails, $types)
    {
        $id = $this->da->escapeInt($id);
        $emails = $this->da->quoteSmart($emails);
        $types  = $this->da->quoteSmart($types);
        $sql = "UPDATE $this->table_name
                SET emails = $emails,
                    types = $types
                WHERE id = $id";
        return $this->update($sql);
    }
}
