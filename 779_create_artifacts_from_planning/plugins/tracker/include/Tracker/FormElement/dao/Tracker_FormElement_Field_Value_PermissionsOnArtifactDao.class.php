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

require_once('Tracker_FormElement_Field_ValueDao.class.php');
class Tracker_FormElement_Field_Value_PermissionsOnArtifactDao extends Tracker_FormElement_Field_ValueDao {
    
    public function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_changeset_value_permissionsonartifact';
    }
    
    public function create($changeset_value_id, $use_perm, $value_ids) {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $use_perm = $this->da->escapeInt($use_perm);
        $values = array();
        if (!is_array($value_ids)) {
            $value_ids = array($value_ids);
        }
        foreach($value_ids as $v) {
            $values[] = "($changeset_value_id, $use_perm, $v)";
        }
        if ($values) {
            $values = implode(',', $values);
            $sql = "INSERT INTO $this->table_name(changeset_value_id, use_perm, ugroup_id)
                    VALUES $values";
            return $this->updateAndGetLastId($sql);
        }
        return false;
    }
    public function  createNoneValue($tracker_id, $field_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $changeset_value_ids = $this->createNoneChangesetValue($tracker_id, $field_id);
        if ( $changeset_value_ids === false ) {
            return false;
        }                   
        $sql = " INSERT INTO $this->table_name(changeset_value_id, use_perm, ugroup_id) VALUES (".implode(', 1, 1), ( ', $changeset_value_ids).", 1, 1) ";
        $this->update($sql);        
    }

    public function keep($from, $to) {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql = "INSERT INTO $this->table_name(changeset_value_id, use_perm, ugroup_id)
                SELECT $to, use_perm, ugroup_id
                FROM $this->table_name
                WHERE changeset_value_id = $from";
        return $this->update($sql);
    }
    
    public function searchByChangesetValueId($changeset_value_id) {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE changeset_value_id = $changeset_value_id ";
        return $this->retrieve($sql);
    }
}
?>
