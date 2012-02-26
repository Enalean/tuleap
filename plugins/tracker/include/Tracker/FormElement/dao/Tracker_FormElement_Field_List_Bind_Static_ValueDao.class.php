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

require_once('common/dao/include/DataAccessObject.class.php');

class Tracker_FormElement_Field_List_Bind_Static_ValueDao extends DataAccessObject {
    const COPY_BY_REFERENCE = true;
    const COPY_BY_VALUE = false;
    
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_field_list_bind_static_value';
    }
    
    public function searchById($id) {
        $id  = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE id = $id";
        return $this->retrieve($sql);
    }
    
    public function searchByFieldId($field_id, $is_rank_alpha) {
        $field_id  = $this->da->escapeInt($field_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE field_id = $field_id 
                ORDER BY ". ($is_rank_alpha ? 'label' : 'rank');
        return $this->retrieve($sql);
    }
    public function duplicate($from_value_id, $to_field_id, $by_reference) {
        $from_value_id  = $this->da->escapeInt($from_value_id);
        $to_field_id    = $this->da->escapeInt($to_field_id);
        if ($by_reference) {
            $insert = "INSERT INTO $this->table_name (field_id, label, description, rank, is_hidden, original_value_id)
                    SELECT $to_field_id, label, description, rank, is_hidden, $from_value_id";
            
        } else {
            $insert = "INSERT INTO $this->table_name (field_id, label, description, rank, is_hidden)
                    SELECT $to_field_id, label, description, rank, is_hidden";
        }
        $sql = $insert . "
                FROM $this->table_name
                WHERE id = $from_value_id";
                
        return $this->updateAndGetLastId($sql);
    }

    public function create($field_id, $label, $description, $rank, $is_hidden) {
        $field_id     = $this->da->escapeInt($field_id);
        $label        = $this->da->quoteSmart($label);
        $description  = $this->da->quoteSmart($description);
        $rank         = $this->da->escapeInt($this->prepareRanking(0, $field_id, $rank, 'id', 'field_id'));
        $is_hidden    = $this->da->escapeInt($is_hidden);
        
        $sql = "INSERT INTO $this->table_name (field_id, label, description, rank, is_hidden)
                VALUES ($field_id, $label, $description, $rank, $is_hidden)";
        return $this->updateAndGetLastId($sql);
    }
    
    public function propagateCreation($field, $original_value_id) {
        $field_id     = $this->da->escapeInt($field->id);
        $original_value_id     = $this->da->escapeInt($original_value_id);
        
        $sql = "INSERT INTO $this->table_name (field_id, label, description, rank, is_hidden, original_value_id)
                SELECT target.id, original_value.label, original_value.description, original_value.rank, original_value.is_hidden, $original_value_id
                    FROM tracker_field_list_bind_static_value AS original_value
                    INNER JOIN tracker_field AS target ON (target.original_field_id = original_value.field_id)
                    WHERE original_value.field_id = $field_id 
                        AND original_value.id = $original_value_id 
                        AND original_value.field_id != target.id";
        return $this->retrieve($sql);
    }
    
    public function save($id, $field_id, $label, $description, $rank, $is_hidden) {
        $id           = $this->da->escapeInt($id);
        $field_id     = $this->da->escapeInt($field_id);
        $label        = $this->da->quoteSmart($label);
        $description  = $this->da->quoteSmart($description);
        $rank         = $this->da->escapeInt($this->prepareRanking($id, $field_id, $rank, 'id', 'field_id'));
        $is_hidden    = $this->da->escapeInt($is_hidden);
        
        $sql = "UPDATE $this->table_name 
                SET label = $label, 
                    description = $description,
                    rank = $rank, 
                    is_hidden = $is_hidden
                WHERE id = $id
                  OR original_value_id = $id";
        return $this->update($sql);
    }
    
    public function delete($id) {
        $id       = $this->da->escapeInt($id);
        $sql = "DELETE FROM $this->table_name 
                WHERE id = $id 
                   OR original_value_id = $id";
        
        return $this->update($sql);
    }
    
    public function searchChangesetValues($changeset_id, $field_id, $is_rank_alpha) {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $field_id     = $this->da->escapeInt($field_id);
        $sql = "SELECT f.id
                FROM tracker_field_list_bind_static_value AS f 
                     INNER JOIN tracker_changeset_value_list AS l ON (l.bindvalue_id = f.id)
                     INNER JOIN tracker_changeset_value AS c
                     ON ( l.changeset_value_id = c.id
                      AND c.changeset_id = $changeset_id
                      AND c.field_id = $field_id
                     )
                ORDER BY f.". ($is_rank_alpha ? 'label' : 'rank');
        return $this->retrieve($sql);
    }
    
    public function canValueBeHidden($field_id, $value_id) {
        $field_id = $this->da->escapeInt($field_id);
        $value_id = $this->da->escapeInt($value_id);
        $sql = "SELECT null
                FROM $this->table_name AS v
                    INNER JOIN tracker_workflow AS w ON (
                        v.field_id = w.field_id
                        AND 
                        (v.id = $value_id OR v.original_value_id = $value_id)
                    )
                    INNER JOIN tracker_workflow_transition AS wt ON (
                        w.workflow_id = wt.workflow_id 
                        AND
                        (wt.from_id = v.id AND (v.original_value_id <> 0 OR wt.from_id = v.original_value_id)) 
                    )
                UNION
                SELECT null
                FROM $this->table_name AS v
                    INNER JOIN tracker_workflow_transition AS wt ON ((wt.to_id = v.id OR wt.to_id = v.original_value_id) AND (v.id = $value_id OR v.original_value_id = $value_id))
                    INNER JOIN tracker_workflow AS w ON (w.workflow_id = wt.workflow_id AND v.field_id = w.field_id)
                UNION 
                SELECT null
                FROM $this->table_name AS v
                    INNER JOIN tracker_semantic_status AS s 
                    ON ((s.open_value_id = v.id OR s.open_value_id = v.original_value_id)
                        AND (v.id = $value_id OR v.original_value_id = $value_id)
                        AND s.field_id = v.field_id)
                UNION 
                SELECT null
                FROM $this->table_name AS v
                    INNER JOIN tracker_rule AS tr
                    ON ( v.id = $value_id 
                        AND (tr.source_field_id = v.field_id OR tr.target_field_id = v.field_id)
                        AND ((tr.source_field_id = $field_id AND tr.source_value_id = $value_id) OR (tr.target_field_id = $field_id AND tr.target_value_id = $value_id)))
                UNION
                SELECT null
                FROM tracker_field AS original
                    INNER JOIN tracker_field AS copied_field ON(original.id = copied_field.original_field_id AND original.id = $field_id)
                    INNER JOIN tracker_field_list_bind_static_value AS copied_value ON (copied_value.field_id = copied_field.id AND copied_value.original_value_id = $value_id)
                    INNER JOIN tracker_rule AS tr ON (
                        (tr.source_field_id = copied_field.id AND tr.source_value_id = copied_value.id)
                        OR
                        (tr.target_field_id = copied_field.id AND tr.target_value_id = copied_value.id)
                    )
                ";
        return count($this->retrieve($sql)) == 0;
    }
    
    public function canValueBeDeleted($field_id, $value_id) {
        $field_id = $this->da->escapeInt($field_id);
        $value_id = $this->da->escapeInt($value_id);
        
        $sql = "SELECT null
                FROM $this->table_name AS v
                    INNER JOIN tracker_changeset_value_list AS cvl ON (v.id = cvl.bindvalue_id)
                    INNER JOIN tracker_changeset_value AS cv ON (cv.id = cvl.changeset_value_id AND cv.field_id = v.field_id)
                WHERE v.original_value_id = $value_id OR v.id = $value_id
                UNION
                SELECT null
                FROM $this->table_name AS v
                    INNER JOIN tracker_changeset_value_openlist AS cvl ON (v.id = cvl.bindvalue_id AND v.id = $value_id)
                    INNER JOIN tracker_changeset_value AS cv ON (cv.id = cvl.changeset_value_id AND cv.field_id = v.field_id AND cv.field_id = $field_id)
                ";
        return $this->canValueBeHidden($field_id, $value_id) && count($this->retrieve($sql)) == 0;
    }
}
?>
