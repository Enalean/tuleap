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

/**
 *  Data Access Object for Tracker_FormElement_Field 
 */
class Tracker_FormElement_FieldDao extends DataAccessObject {
    
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_field';
    }
    
    public function searchByTrackerIdAndName($tracker_id, $name) {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $name  = $this->da->quoteSmart($name);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id 
                  AND name = $name";
        return $this->retrieve($sql);
    }
    
    public function searchUsedByTrackerIdAndName($tracker_id, $name) {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $name  = $this->da->quoteSmart($name);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id 
                  AND name = $name
                  AND use_it = 1";
        return $this->retrieve($sql);
    }
    
    public function searchUnusedByTrackerId($tracker_id) {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id 
                  AND use_it = 0
                ORDER BY parent_id, rank";
        return $this->retrieve($sql);
    }
    
    public function searchUsedByTrackerId($tracker_id) {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id 
                  AND parent_id = 0
                  AND use_it = 1
                ORDER BY rank";
        return $this->retrieve($sql);
    }
    
    public function searchUsedByIdAndType($tracker_id, $field_id, $type) {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $field_id    = $this->da->escapeInt($field_id);
        if (is_array($type)) {
            $type_stm = ' IN ('. implode(',', array_map(array($this->da, 'quoteSmart'), $type)) .') ';
        } else {
            $type = $this->da->quoteSmart($type);
            $type_stm = " = $type";
        }
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id 
                  AND id = $field_id
                  AND use_it = 1
                  AND formElement_type $type_stm
                ORDER BY rank";
        return $this->retrieve($sql);
    }
    
    public function searchUsedUserListFieldByTrackerId($tracker_id) {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name f, tracker_field_list_bind_users lbu
                WHERE f.tracker_id = $tracker_id 
                  AND use_it = 1 
                  AND f.id = lbu.field_id
                ORDER BY rank";
        return $this->retrieve($sql);
    }
    
    public function getUsedUserListFieldById($tracker_id, $field_id) {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $field_id    = $this->da->escapeInt($field_id);
        $sql = "SELECT *
                FROM $this->table_name f, tracker_field_list_bind_users lbu
                WHERE f.tracker_id = $tracker_id 
                  AND f.id = $field_id
                  AND use_it = 1
                  AND f.id = lbu.field_id
                ORDER BY rank";
        return $this->retrieve($sql);
    }
    
    public function searchUsedUserSbFieldByTrackerId($tracker_id) {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name f, tracker_field_list_bind_users lbu
                WHERE f.tracker_id = $tracker_id 
                  AND use_it = 1 
                  AND f.id = lbu.field_id 
                  AND formElement_type IN ('sb', 'msb')
                ORDER BY rank";
        return $this->retrieve($sql);
    }
    
    public function getUsedUserSbFieldById($tracker_id, $field_id) {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $field_id    = $this->da->escapeInt($field_id);
        $sql = "SELECT *
                FROM $this->table_name f, tracker_field_list_bind_users lbu
                WHERE f.tracker_id = $tracker_id 
                  AND f.id = $field_id
                  AND use_it = 1
                  AND f.id = lbu.field_id 
                  AND formElement_type IN ('sb', 'msb')
                ORDER BY rank";
        return $this->retrieve($sql);
    }
    
    public function searchByParentId($parent_id) {
        $parent_id  = $this->da->escapeInt($parent_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE parent_id = $parent_id 
                ORDER BY rank";
        return $this->retrieve($sql);
    }
    
    function searchUsedByParentId($parent_id) {
        $parent_id  = $this->da->escapeInt($parent_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE parent_id = $parent_id 
                  AND use_it = 1
                ORDER BY rank";
        return $this->retrieve($sql);
    }
    
    function searchUsedByTrackerIdAndType($tracker_id, $type, $used = null) {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        if (is_array($type)) {
            $type_stm = ' IN ('. implode(',', array_map(array($this->da, 'quoteSmart'), $type)) .') ';
        } else {
            $type = $this->da->quoteSmart($type);
            $type_stm = " = $type";
        }
        
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id 
                  AND formElement_type $type_stm";
        if ($used) {
            $sql.= " AND use_it = 1";
        }
        $sql.= " ORDER BY rank";
        return $this->retrieve($sql);
    }
    
    function searchByTrackerIdAndType($tracker_id, $type) {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        if (is_array($type)) {
            $type_stm = ' IN ('. implode(',', array_map(array($this->da, 'quoteSmart'), $type)) .') ';
        } else {
            $type = $this->da->quoteSmart($type);
            $type_stm = " = $type";
        }
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id 
                  AND formElement_type $type_stm
                ORDER BY rank";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches field_id for (multi_)assigned_to By TrackerId
    * @return DataAccessResult
    */
    function searchAssignedToFieldIdByArtifactTrackerId($TrackerId) {
        $sql = sprintf(" SELECT field_id ".
                       " FROM tracker_field ".
                       " WHERE group_artifact_id = %s ".
                       "   AND (field_name = 'assigned_to' OR field_name = 'multi_assigned_to') ",
               $TrackerId);
        return $this->retrieve($sql);
    }
    
    function searchById($id) {
        $id = $this->da->escapeInt($id);
        $sql = "SELECT * FROM tracker_field WHERE id = $id";
        return $this->retrieve($sql);
    }
    
    function searchNextUsedSibling($tracker_id, $id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $id         = $this->da->escapeInt($id);
        $sql = "SELECT R2.*
                FROM tracker_field AS R1 INNER JOIN
                     tracker_field AS R2 ON (R1.tracker_id = R2.tracker_id AND R1.parent_id = R2.parent_id AND R2. rank > R1.rank)
                WHERE R1.id = $id
                  AND R2.use_it = 1
                ORDER BY R2.rank
                LIMIT 1";
        return $this->retrieve($sql);
    }
    
    function searchPreviousUsedSibling($tracker_id, $id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $id         = $this->da->escapeInt($id);
        $sql = "SELECT R2.*
                FROM tracker_field AS R1 INNER JOIN
                     tracker_field AS R2 ON (R1.tracker_id = R2.tracker_id AND R1.parent_id = R2.parent_id AND R2. rank < R1.rank)
                WHERE R1.id = $id
                  AND R2.use_it = 1
                ORDER BY R2.rank DESC
                LIMIT 1";
        return $this->retrieve($sql);
    }
    
    function searchByTrackerId($tracker_id) {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                ORDER BY parent_id, rank";
        return $this->retrieve($sql);
    }
    
    public function duplicate($from_field_id, $to_tracker_id) {
        //TODO: duplicate tracker_id
        $from_field_id  = $this->da->escapeInt($from_field_id);
        $to_tracker_id = $this->da->escapeInt($to_tracker_id);
        $sql = "INSERT INTO $this->table_name (tracker_id, parent_id, name, formElement_type, label, description, scope, required, use_it, rank, notifications, original_field_id)
                SELECT $to_tracker_id, parent_id, name, formElement_type, label, description, scope, required, use_it, rank, notifications, original_field_id
                FROM $this->table_name
                WHERE id = $from_field_id";
        return $this->updateAndGetLastId($sql);
    }
    
    public function mapNewParentsAfterDuplication($tracker_id, $mapping) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $cases = '';
        foreach($mapping as $map) {
            $cases .= ' WHEN '. $map['from'] .' THEN '. $map['to'] . PHP_EOL;
        }
        if ($cases) {
            $sql = "UPDATE $this->table_name
                    SET parent_id = CASE parent_id 
                                    $cases
                                    END
                    WHERE tracker_id = $tracker_id;";
            return $this->update($sql);
        }
        return true;
    }
    
    public function save($field) {
        $rank = (int)$this->prepareRanking($field->id, $field->parent_id, $field->rank, 'id', 'parent_id');
        
        $sql = "UPDATE $this->table_name
                SET parent_id         = ". $this->da->escapeInt($field->parent_id) .", 
                    label             = ". $this->da->quoteSmart($field->label) .", 
                    name              = ". $this->da->quoteSmart($field->name) .", 
                    description       = ". $this->da->quoteSmart($field->description) .", 
                    scope             = ". $this->da->quoteSmart($field->scope) .", 
                    required          = ". $this->da->escapeInt($field->required ? 1 : 0) .", 
                    notifications     = ". ($field->notifications ? 1 : "NULL") .", 
                    use_it            = ". $this->da->escapeInt($field->use_it ? 1 : 0) .", 
                    rank              = ". $this->da->escapeInt($rank) .",
                    original_field_id = ". $this->da->escapeInt($field->getOriginalFieldId()) ."
                WHERE id = ". $this->da->escapeInt($field->id);
        if ($this->update($sql)) {
            $field->rank = $rank;
            return true;
        }
        return false;
    }
    
    public function propagateUpdatedProperties($original_field) {
        $sql = "UPDATE $this->table_name AS original 
                INNER JOIN $this->table_name AS target ON (target.original_field_id = original.id)
                SET target.label = original.label,
                    target.description = original.description
                WHERE original.id = $original_field->id";
        return $this->update($sql);
    }
    
    public function setType($field,$type) {
        $sql = "UPDATE $this->table_name
                SET formElement_type = ". $this->da->quoteSmart($type) ."
                WHERE id = ". $this->da->escapeInt($field->id);
        if ($this->update($sql)) {
            return true;
        }
        return false;
    }
 
    public function delete($field) {
        $sql = "DELETE FROM $this->table_name
                WHERE id = ". $this->da->escapeInt($field->id);
        return $this->update($sql);
    }
    
    public function searchSharedTargets($id) {
        $id  = $this->da->escapeInt($id);
        $sql = "SELECT * FROM tracker_field WHERE original_field_id = $id";
        return $this->retrieve($sql);
    }
    
    private function getSqlForAllSharedTargetsOfProject($project_id) {
        $project_id  = $this->da->escapeInt($project_id);
        $sql = "SELECT f_target.*
                FROM tracker_field f_target 
                  JOIN tracker_field f_src ON (f_target.original_field_id = f_src.id) 
                  JOIN tracker ON (f_src.tracker_id = tracker.id)
                WHERE tracker.group_id = $project_id
                  AND f_target.use_it = 1";
        return $sql;
    }
    
    private function getSqlForAllSharedSourcesOfProject($project_id) {
        $project_id  = $this->da->escapeInt($project_id);
        $sql = "SELECT tracker_field.*
                FROM tracker_field 
                INNER JOIN tracker ON tracker.id = tracker_field.tracker_id 
                WHERE tracker.group_id = $project_id 
                  AND tracker_field.original_field_id != 0
                  AND tracker_field.use_it = 1";
        return $sql;
    }
    
    public function searchProjectSharedFieldsOriginals($project_id) {
        $source_sql = $this->getSqlForAllSharedSourcesOfProject($project_id);
        $target_sql = $this->getSqlForAllSharedTargetsOfProject($project_id);
        
        $sql = "SELECT * FROM (($source_sql) UNION ($target_sql)) AS combined
                GROUP BY original_field_id";
        return $this->retrieve($sql);
    }

    /**
     * Returns:
     * - all the fields that are a copy of fields defined in the project
     * - and the original shared field description
     *
     * Warning: the 2 parts of the union are deeply integrated, the second union
     * query is an extension of the first one
     * 
     * @return DataAccessResult
     */
    public function searchAllSharedFieldsOfProject($project_id) {
        $project_id = $this->da->escapeInt($project_id);
        $sql = "SELECT * FROM
                ((SELECT f_target.*
                  FROM tracker_field   AS f_target 
                    JOIN tracker_field AS f_src    ON (f_target.original_field_id = f_src.id) 
                    JOIN tracker                   ON (f_src.tracker_id           = tracker.id)
                  WHERE tracker.group_id = $project_id
                  AND f_target.use_it = 1)
        
                 UNION
        
                (SELECT f_original.*
                  FROM tracker_field   AS f_original
                    JOIN tracker_field AS f_target   ON (f_original.id              = f_target.original_field_id)
                    JOIN tracker_field AS f_src      ON (f_target.original_field_id = f_src.id) 
                    JOIN tracker                     ON (f_src.tracker_id           = tracker.id)
                  WHERE tracker.group_id = $project_id
                  AND f_target.use_it = 1)

                ) as combined";
        return $this->retrieve($sql);
    }
    
    public function searchFieldFromTrackerIdAndSharedFieldId($tracker_id, $shared_field_id) {
        $tracker_id      = $this->da->escapeInt($tracker_id);
        $shared_field_id = $this->da->escapeInt($shared_field_id);
        
        $sql = "SELECT f1.id
                FROM tracker_field AS f1
                INNER JOIN tracker_field AS f2 ON (
                        f2.id = $shared_field_id
                    AND f1.use_it = 1
                    AND (
                            f2.id = f1.id
                        OR
                            f2.original_field_id = f1.id
                        OR
                            f2.id = f1.original_field_id
                        OR
                            (f2.original_field_id = f1.original_field_id AND f1.original_field_id <> 0)
                        )
                )
                WHERE f1.tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }
    
    public function create($type, $tracker_id, $parent_id, $name, $prefix_name, $label, $description, $use_it, $scope, $required, $notifications, $rank, $original_field_id) {
        $type              = $this->da->quoteSmart($type);
        $tracker_id        = $this->da->escapeInt($tracker_id);
        $parent_id         = $this->da->escapeInt($parent_id);
        $name_like         = $this->da->quoteSmart(str_replace('_', '\_', $prefix_name) . '%');
        $prefix_name       = $this->da->quoteSmart($prefix_name);
        $label             = $this->da->quoteSmart($label);
        $description       = $this->da->quoteSmart($description);
        $use_it            = $this->da->escapeInt($use_it);
        $scope             = $this->da->quoteSmart($scope);
        $required          = $this->da->escapeInt($required);
        $notifications     = ($notifications ? 1 : "NULL");
        $rank              = $this->da->escapeInt($this->prepareRanking(0, $parent_id, $rank, 'id', 'parent_id'));
        $original_field_id = $this->da->escapeInt($original_field_id);
        
        $sql = "INSERT INTO $this->table_name (tracker_id, parent_id, name, formElement_type, label, description, scope, required, use_it, rank, notifications, original_field_id) ";
        if ($name) {
            $name = $this->da->quoteSmart($name);
            $sql .= "
                VALUES ($tracker_id, $parent_id, $name, $type, $label, $description, $scope, $required, $use_it, $rank, $notifications, $original_field_id)";
        } else {
            $sql .= "
                SELECT $tracker_id, $parent_id, CONCAT($prefix_name, IFNULL(MAX(REPLACE(name, $prefix_name, '')), 0) + 1), $type, $label, $description, $scope, $required, $use_it, $rank, $notifications, $original_field_id
                FROM tracker_field
                WHERE tracker_id = $tracker_id
                    AND name LIKE $name_like";
        }
        return $this->updateAndGetLastId($sql);
    }
}


?>
