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

require_once('dao/Tracker_RuleDao.class.php');

require_once('Tracker_Rule_Value.class.php');


/**
* Factory of rules
*
* Base class to create, retrieve, update or delete rules
*/
class Tracker_RuleFactory {
    
    var $rules_dao;
    var $rules;
    
    var $RULETYPE_HIDDEN;
    var $RULETYPE_DISABLED;
    var $RULETYPE_MANDATORY;
    var $RULETYPE_VALUE;
    
    function Tracker_RuleFactory(&$rules_dao) {
        $this->rules_dao         =& $rules_dao;
        $this->rules = array();
        
        $this->RULETYPE_HIDDEN    = 1;
        $this->RULETYPE_DISABLED  = 2;
        $this->RULETYPE_MANDATORY = 3;
        $this->RULETYPE_VALUE     = 4;
    }
    
    /**
    * Tracker_RuleFactory is a singleton
    */
    function instance() {
        static $_artifactrulefactory_instance;
        if (!$_artifactrulefactory_instance) {
            $rules_dao         = new Tracker_RuleDao();
            $_artifactrulefactory_instance = new Tracker_RuleFactory($rules_dao);
        }
        return $_artifactrulefactory_instance;
    }

    function getRuleById($id) {
        if (!isset($this->rules[$id])) {
            $this->rules[$id] = null;
            //We retrieve rule
            $dar =& $this->rules_dao->searchById($id);
            if ($dar && ($rule_row = $dar->getRow())) {
                $rule_row['id'] = $id;
                $this->rules[$id] =& $this->_buildRuleInstance($rule_row);
            }
        }
        return $this->rules[$id];
    }
    
    function getAllRulesByTrackerWithOrder($tracker_id) {
        $dar = $this->rules_dao->searchByTrackerIdWithOrder($tracker_id);
        $rules = array();
        while($rule_row = $dar->getRow()) {
            if (!isset($this->rules[$rule_row['id']])) {
                $rule_row['tracker_id'] = $tracker_id;
                $this->rules[$rule_row['id']] =& $this->_buildRuleInstance($rule_row);
            }
            $rules[] =& $this->rules[$rule_row['id']];
        }
        return $rules;
    }
    
    /**
    * @return Tracker_Rule
    */
    function &_buildRuleInstance($data) {
        //We create Rule
        switch ($data['rule_type']) {
            case $this->RULETYPE_HIDDEN:
                $rule =& new Tracker_RuleHidden($data['id'], $data['tracker_id'], $data['source_field_id'], $data['source_value_id'], $data['target_field_id']);
                break;
            case $this->RULETYPE_DISABLED:
                $rule =& new Tracker_RuleDisabled($data['id'], $data['tracker_id'], $data['source_field_id'], $data['source_value_id'], $data['target_field_id']);
                break;
            case $this->RULETYPE_MANDATORY:
                $rule =& new Tracker_RuleMandatory($data['id'], $data['tracker_id'], $data['source_field_id'], $data['source_value_id'], $data['target_field_id']);
                break;
            default: //RULETYPE_VALUE
                $rule =& new Tracker_Rule_Value($data['id'], $data['tracker_id'], $data['source_field_id'], $data['source_value_id'], $data['target_field_id'], $data['target_value_id']);
                break;
        }
        return $rule;
    }
    
    function saveRuleValue($tracker_id, $source, $source_value, $target, $target_value) {
        $this->rules_dao->create($tracker_id, $source, $source_value, $target, $this->RULETYPE_VALUE, $target_value);
    }
    
    function _saveRuleState($tracker_id, $source, $source_value, $target, $rule_type) {
        $this->rules_dao->deleteRuleState($tracker_id, $source, $source_value, $target, array($this->RULETYPE_HIDDEN, $this->RULETYPE_DISABLED, $this->RULETYPE_MANDATORY));
        $this->rules_dao->create($tracker_id, $source, $source_value, $target, $rule_type);
    }
    function saveRuleHidden($tracker_id, $source, $source_value, $target) {
        $this->_saveRuleState($tracker_id, $source, $source_value, $target, $this->RULETYPE_HIDDEN);
    }
    
    function saveRuleDisabled($tracker_id, $source, $source_value, $target) {
        $this->_saveRuleState($tracker_id, $source, $source_value, $target, $this->RULETYPE_DISABLED);
    }
    
    function saveRuleMandatory($tracker_id, $source, $source_value, $target) {
        $this->_saveRuleState($tracker_id, $source, $source_value, $target, $this->RULETYPE_MANDATORY);
    }
    
    function deleteRule($rule_id) {
        $deleted = $this->rules_dao->deleteByRuleId($rule_id);
        return $deleted;
    }

    function deleteRuleValueBySource($tracker_id, $source, $source_value, $target) {
        $deleted = $this->rules_dao->deleteByGroupArtifactIdAndSourceAndSourceValueAndTargetAndRuleType($tracker_id, $source, $source_value, $target, $this->RULETYPE_VALUE);
        return $deleted;
    }
    function deleteRuleValueByTarget($tracker_id, $source, $target, $target_value) {
        $deleted = $this->rules_dao->deleteByGroupArtifactIdAndSourceAndTargetAndTargetValueAndRuleType($tracker_id, $source, $target, $target_value, $this->RULETYPE_VALUE);
        return $deleted;
    }
    
    /**
    * Delete all rules for a tracker
    */
    function deleteRulesByArtifactTracker($tracker_id) {
        $deleted = $this->rules_dao->deleteRulesByGroupArtifactId($tracker_id);
        return $deleted;
    }
    /**
    * Delete all rules related to a field
    */
    function deleteRulesByFieldId($tracker_id, $field_id) {
        $deleted = $this->rules_dao->deleteByField($tracker_id, $field_id);
        return $deleted;
    }
    /**
    * Delete all rules related to a field value
    */
    function deleteRulesByValueId($tracker_id, $field_id, $value_id) {
        $deleted = $this->rules_dao->deleteByFieldValue($tracker_id, $field_id, $value_id);
        return $deleted;
    }
    
    /**
    * Delete all rules by source field id and target field id
    *
    * @param $tracker_id, the id of the tracker
    * @param $field_source_id, the id of the source field
    * @param $field_target_id, the id of the target field
    *
    * @return bool
    */
    function deleteRulesBySourceTarget($tracker_id, $field_source_id, $field_target_id) {
        $deleted = $this->rules_dao->deleteRulesBySourceTarget($tracker_id, $field_source_id, $field_target_id);
        return $deleted;
    }
    
    /**
    * copy rules from a tracker to another
    */
    function copyRules($from_artifact_type, $to_artifact_type) {
        $copied = $this->rules_dao->copyRules($from_artifact_type, $to_artifact_type);
        return $copied;
    }
    
    /**
     * Get dependency rules of a Source and Target
     *
     * @param $tracker_id, the id of the tracker
     * @param $field_source_id, the id of the source field
     * @param $field_target_id, the id of the target field
     *
     * @return array of Tracker_Rule_Value
     */
    function getDependenciesBySourceTarget($tracker_id, $field_source_id, $field_target_id) {
        $dependencies = array();
        foreach($this->rules_dao->searchBySourceTarget($tracker_id, $field_source_id, $field_target_id) as $row) {
            $dependencies[$row['id']] = $this->getInstanceFromRow($row);
        }
        return $dependencies;
    }
    
    function getInvolvedFieldsByTrackerId($tracker_id) {
        return $this->rules_dao->searchInvolvedFieldsByTrackerId($tracker_id);
    }
    
    function getInstanceFromRow($row) {
        $instance = new Tracker_Rule_Value(
                        $row['id'],
                        $row['tracker_id'],
                        $row['source_field_id'],
                        $row['source_value_id'],
                        $row['target_field_id'],
                        $row['target_value_id']
                    );
        return $instance;
    }
}
?>