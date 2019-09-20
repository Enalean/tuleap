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

/**
 *  Data Access Object for Tracker_FormElement_Field
 */
class Tracker_FormElement_FieldSetDao extends DataAccessObject
{
    function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_fieldset';
    }

    function searchById($id)
    {
        $id  = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE id = $id ";
        return $this->retrieve($sql);
    }

    function searchByTrackerId($tracker_id)
    {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id 
                ORDER BY rank";
        return $this->retrieve($sql);
    }

    public function duplicate($from_fieldset_id, $to_tracker_id)
    {
        $from_fieldset_id = $this->da->escapeInt($from_fieldset_id);
        $to_tracker_id    = $this->da->escapeInt($to_tracker_id);
        $sql = "INSERT INTO $this->table_name (tracker_id, name, description, rank)
                SELECT $to_tracker_id, name, description, rank
                FROM $this->table_name
                WHERE id = $from_fieldset_id";
        return $this->updateAndGetLastId($sql);
    }

    public function create($tracker_id, $name, $description, $rank)
    {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $name        = $this->da->quoteSmart($name);
        $description = $this->da->quoteSmart($description);
        $rank        = (int)$this->prepareRanking(0, $tracker_id, $rank, 'id', 'tracker_id');

        $sql = "INSERT INTO $this->table_name (tracker_id, name, description, rank)
                VALUES ($tracker_id, $name, $description, $rank)";
        return $this->updateAndGetLastId($sql);
    }

    public function save($fieldset_id, $tracker_id, $name, $description, $rank)
    {
        $fieldset_id = $this->da->escapeInt($fieldset_id);
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $name        = $this->da->quoteSmart($name);
        $description = $this->da->quoteSmart($description);
        $rank        = (int)$this->prepareRanking($fieldset_id, $tracker_id, $rank, 'id', 'tracker_id');

        $sql = "UPDATE $this->table_name 
                SET name = $name, description = $description, rank = $rank, tracker_id = $tracker_id
                WHERE id = $fieldset_id";
        return $this->update($sql);
    }

    public function delete($fieldset_id)
    {
        $fieldset_id = $this->da->escapeInt($fieldset_id);
        $sql = "DELETE FROM $this->table_name 
                WHERE id = $fieldset_id";
        return $this->update($sql);
    }
}
