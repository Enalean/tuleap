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
 *  Data Access Object for Tracker_Rule 
 */
class Tracker_RuleDao extends DataAccessObject {
    
    public function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_rule';
    }
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function searchAll() {
        $sql = "SELECT * FROM tracker_rule";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches Tracker_Rule by Id 
    * @return DataAccessResult
    */
    function searchById($id) {
        $sql = sprintf("SELECT group_artifact_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id FROM tracker_rule WHERE id = %s",
				$this->da->quoteSmart($id));
        return $this->retrieve($sql);
    }

    /**
    * Searches Tracker_Rule by TrackerId
    * @return DataAccessResult
    */
    function searchByTrackerId($tracker_id) {
        $sql = sprintf("SELECT id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id FROM tracker_rule WHERE tracker_id = %s",
				$this->da->quoteSmart($tracker_id));
        return $this->retrieve($sql);
    }

    /**
    * Searches Tracker_Rule by SourceFieldId 
    * @return DataAccessResult
    */
    function searchBySourceFieldId($sourceFieldId) {
        $sql = sprintf("SELECT id, group_artifact_id, source_value_id, target_field_id, rule_type, target_value_id FROM tracker_rule WHERE source_field_id = %s",
				$this->da->quoteSmart($sourceFieldId));
        return $this->retrieve($sql);
    }

    /**
    * Searches Tracker_Rule by SourceValueId 
    * @return DataAccessResult
    */
    function searchBySourceValueId($sourceValueId) {
        $sql = sprintf("SELECT id, group_artifact_id, source_field_id, target_field_id, rule_type, target_value_id FROM tracker_rule WHERE source_value_id = %s",
				$this->da->quoteSmart($sourceValueId));
        return $this->retrieve($sql);
    }

    /**
    * Searches Tracker_Rule by TargetFieldId 
    * @return DataAccessResult
    */
    function searchByTargetFieldId($targetFieldId) {
        $sql = sprintf("SELECT id, group_artifact_id, source_field_id, source_value_id, rule_type, target_value_id FROM tracker_rule WHERE target_field_id = %s",
				$this->da->quoteSmart($targetFieldId));
        return $this->retrieve($sql);
    }

    /**
    * Searches Tracker_Rule by RuleType 
    * @return DataAccessResult
    */
    function searchByRuleType($ruleType) {
        $sql = sprintf("SELECT id, group_artifact_id, source_field_id, source_value_id, target_field_id, target_value_id FROM tracker_rule WHERE rule_type = %s",
				$this->da->quoteSmart($ruleType));
        return $this->retrieve($sql);
    }

    /**
    * Searches Tracker_Rule by TargetValueId 
    * @return DataAccessResult
    */
    function searchByTargetValueId($targetValueId) {
        $sql = sprintf("SELECT id, group_artifact_id, source_field_id, source_value_id, target_field_id, rule_type FROM tracker_rule WHERE target_value_id = %s",
				$this->da->quoteSmart($targetValueId));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table tracker_rule 
    * @return true or id(auto_increment) if there is no error
    */
    function create($tracker_id, $source_field_id, $source_value_id, $target_field_id, $rule_type, $target_value_id) {
		$sql = sprintf("INSERT INTO tracker_rule (tracker_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id) VALUES (%s, %s, %s, %s, %s, %s)",
				$this->da->quoteSmart($tracker_id),
				$this->da->quoteSmart($source_field_id),
				$this->da->quoteSmart($source_value_id),
				$this->da->quoteSmart($target_field_id),
				$this->da->quoteSmart($rule_type),
				$this->da->quoteSmart($target_value_id));        
        //return $this->updateAndGetLastId($sql);
        $this->retrieve($sql);
    }

    
    /**
    * Searches Tracker_Rule by tracker_id 
    * @return DataAccessResult
    */
    function searchByTrackerIdWithOrder($tracker_id) {
       //$sql = sprintf("SELECT ar.id, ar.source_field_id, ar.source_value_id, ar.target_field_id, ar.rule_type, ar.target_value_id ".
       //               " FROM tracker_rule AS ar ".
       //               "   INNER JOIN tracker_field_usage AS afu1 ON (ar.source_field_id = afu1.field_id AND ar.group_artifact_id = afu1.group_artifact_id) ".
       //               "   INNER JOIN tracker_field_usage AS afu2 ON (ar.target_field_id = afu2.field_id AND ar.group_artifact_id = afu2.group_artifact_id) ".
       //               "   LEFT JOIN tracker_field_value_list AS afvls ".
       //               "      ON (ar.source_field_id = afvls.field_id AND ar.group_artifact_id = afvls.group_artifact_id AND ar.source_value_id = afvls.value_id) ".
       //               "   LEFT JOIN tracker_field_value_list AS afvlt ".
       //               "      ON (ar.target_field_id = afvlt.field_id AND ar.group_artifact_id = afvlt.group_artifact_id AND ar.target_value_id = afvlt.value_id) ".
       //               " WHERE ar.group_artifact_id = %s ".
       //               " ORDER BY afu1.place, afu2.place, afvls.order_id, afvlt.order_id, ar.id",
	   //		$this->da->quoteSmart($tracker_id));
               $sql = sprintf("SELECT ar.id, ar.source_field_id, ar.source_value_id, ar.target_field_id, ar.rule_type, ar.target_value_id ".
                      " FROM tracker_rule AS ar ".
                      " WHERE ar.tracker_id = %s ".
                      " ORDER BY ar.id",
				$this->da->quoteSmart($tracker_id));
        return $this->retrieve($sql);
    }

    function deleteById($id) {
        $sql = sprintf("DELETE FROM tracker_rule WHERE id = %s",
				$this->da->quoteSmart($id));
        return $this->update($sql);
    }
    
    function deleteRuleState($group_artifact_id, $source, $source_value, $target, $rule_types) {
        $quoted_types = array();
        foreach($rule_types as $type) {
            $quoted_types[] = $this->da->quoteSmart($type);
        }
        $sql = sprintf('DELETE FROM tracker_rule '.
                       ' WHERE group_artifact_id = %s '.
                       '   AND source_field_id   = %s '.
                       '   AND source_value_id   = %s '.
                       '   AND target_field_id   = %s '.
                       '   AND rule_type IN (%s) ',
				$this->da->quoteSmart($group_artifact_id),
				$this->da->quoteSmart($source),
				$this->da->quoteSmart($source_value),
				$this->da->quoteSmart($target),
				implode(', ', $quoted_types));
        return $this->retrieve($sql);
    }

    function deleteByGroupArtifactIdAndSourceAndSourceValueAndTargetAndRuleType($artifact_type, $source, $source_value, $target, $rule_type) {
        $sql = sprintf('DELETE FROM tracker_rule '.
                       ' WHERE group_artifact_id = %s '.
                       '   AND source_field_id   = %s '.
                       '   AND source_value_id   = %s '.
                       '   AND target_field_id   = %s '.
                       '   AND rule_type         = %s ',
				$this->da->quoteSmart($artifact_type),
				$this->da->quoteSmart($source),
				$this->da->quoteSmart($source_value),
				$this->da->quoteSmart($target),
				$this->da->quoteSmart($rule_type));
        return $this->update($sql);
    }

    function deleteByGroupArtifactIdAndSourceAndTargetAndTargetValueAndRuleType($artifact_type, $source, $target, $target_value, $rule_type) {
        $sql = sprintf('DELETE FROM tracker_rule '.
                       ' WHERE group_artifact_id = %s '.
                       '   AND source_field_id   = %s '.
                       '   AND target_field_id   = %s '.
                       '   AND target_value_id   = %s '.
                       '   AND rule_type         = %s ',
				$this->da->quoteSmart($artifact_type),
				$this->da->quoteSmart($source),
				$this->da->quoteSmart($target),
				$this->da->quoteSmart($target_value),
				$this->da->quoteSmart($rule_type));
        return $this->update($sql);
    }
    
    function deleteRulesByGroupArtifactId($artifact_type) {
        $sql = sprintf('DELETE FROM tracker_rule '.
                       ' WHERE group_artifact_id = %s ',
				$this->da->quoteSmart($artifact_type));
        return $this->update($sql);
    }
    function deleteByField($artifact_type, $field_id) {
        $sql = sprintf('DELETE FROM tracker_rule '.
                       ' WHERE group_artifact_id = %s '.
                       '   AND (source_field_id  = %s '.
                       '   OR target_field_id    = %s) ',
				$this->da->quoteSmart($artifact_type),
				$this->da->quoteSmart($field_id),
				$this->da->quoteSmart($field_id));
        return $this->update($sql);
    }
    function deleteByFieldValue($artifact_type, $field_id, $value_id) {
        $sql = sprintf('DELETE FROM tracker_rule '.
                       ' WHERE group_artifact_id   = %s '.
                       '   AND ( '.
                       '     ( source_field_id     = %s '.
                       '       AND source_value_id = %s '.
                       '     )  '.
                       '     OR '.
                       '     ( target_field_id     = %s '.
                       '       AND target_value_id = %s '.
                       '     ) '.
                       '   ) ',
				$this->da->quoteSmart($artifact_type),
				$this->da->quoteSmart($field_id),
				$this->da->quoteSmart($value_id),
				$this->da->quoteSmart($field_id),
				$this->da->quoteSmart($value_id));
        return $this->update($sql);
    }
    
    function copyRules($from_artifact_type, $to_artifact_type) {
        $sql = sprintf('INSERT INTO tracker_rule (group_artifact_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id) '.
                        ' SELECT %s, source_field_id, source_value_id, target_field_id, rule_type, target_value_id '.
                        ' FROM tracker_rule '.
                        ' WHERE group_artifact_id = %s ',
                               $this->da->quoteSmart($to_artifact_type),
                               $this->da->quoteSmart($from_artifact_type));
        return $this->updateAndGetLastId($sql);
    }
    
    
    function deleteRulesBySourceTarget($tracker_id, $field_source_id, $field_target_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_source_id = $this->da->escapeInt($field_source_id);
        $field_target_id = $this->da->escapeInt($field_target_id); 
        $sql = "DELETE
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                  AND source_field_id   = $field_source_id
                  AND target_field_id   = $field_target_id";        
        return $this->update($sql);
    }
    
    function searchBySourceTarget($tracker_id, $field_source_id, $field_target_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_source_id = $this->da->escapeInt($field_source_id);
        $field_target_id = $this->da->escapeInt($field_target_id);
        $sql = "SELECT * FROM $this->table_name
                WHERE tracker_id = $tracker_id
                  AND source_field_id   = $field_source_id
                  AND target_field_id   = $field_target_id";
        return $this->retrieve($sql);
    }
    
    function searchInvolvedFieldsByTrackerId($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT distinct source_field_id, target_field_id 
                FROM tracker_rule 
                WHERE tracker_id = '$tracker_id'";
        return $this->retrieve($sql);
    }
}


?>