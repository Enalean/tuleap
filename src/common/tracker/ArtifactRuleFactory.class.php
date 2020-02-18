<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */


/**
* Factory of rules
*
* Base class to create, retrieve, update or delete rules
*/
class ArtifactRuleFactory
{

    public $rules_dao;
    public $rules;

    public $RULETYPE_HIDDEN;
    public $RULETYPE_DISABLED;
    public $RULETYPE_MANDATORY;
    public $RULETYPE_VALUE;

    public function __construct(ArtifactRuleDao $rules_dao)
    {
        $this->rules_dao = $rules_dao;
        $this->rules = array();

        $this->RULETYPE_HIDDEN    = 1;
        $this->RULETYPE_DISABLED  = 2;
        $this->RULETYPE_MANDATORY = 3;
        $this->RULETYPE_VALUE     = 4;
    }

    /**
    * ArtifactRuleFactory is a singleton
    */
    public static function instance()
    {
        static $_artifactrulefactory_instance;
        if (!$_artifactrulefactory_instance) {
            $rules_dao         = new ArtifactRuleDao(CodendiDataAccess::instance());
            $_artifactrulefactory_instance = new ArtifactRuleFactory($rules_dao);
        }
        return $_artifactrulefactory_instance;
    }

    public function & getRuleById($id)
    {
        if (!isset($this->rules[$id])) {
            $this->rules[$id] = null;
            //We retrieve rule
            $dar = $this->rules_dao->searchById($id);
            if ($dar && ($rule_row = $dar->getRow())) {
                $rule_row['id'] = $id;
                $this->rules[$id] = $this->_buildRuleInstance($rule_row);
            }
        }
        return $this->rules[$id];
    }

    public function getAllRulesByArtifactTypeWithOrder($artifact_type)
    {
        $dar = $this->rules_dao->searchByGroupArtifactIdWithOrder($artifact_type);
        $rules = array();
        while ($rule_row = $dar->getRow()) {
            if (!isset($this->rules[$rule_row['id']])) {
                $rule_row['group_artifact_id'] = $artifact_type;
                $this->rules[$rule_row['id']] = $this->_buildRuleInstance($rule_row);
            }
            $rules[] = $this->rules[$rule_row['id']];
        }
        return $rules;
    }

    /**
    * @return ArtifactRule
    */
    public function _buildRuleInstance($data)
    {
        //We create Rule
        switch ($data['rule_type']) {
            case $this->RULETYPE_HIDDEN:
                $rule = new ArtifactRuleHidden($data['id'], $data['group_artifact_id'], $data['source_field_id'], $data['source_value_id'], $data['target_field_id']);
                break;
            case $this->RULETYPE_DISABLED:
                $rule = new ArtifactRuleDisabled($data['id'], $data['group_artifact_id'], $data['source_field_id'], $data['source_value_id'], $data['target_field_id']);
                break;
            case $this->RULETYPE_MANDATORY:
                $rule = new ArtifactRuleMandatory($data['id'], $data['group_artifact_id'], $data['source_field_id'], $data['source_value_id'], $data['target_field_id']);
                break;
            default: //RULETYPE_VALUE
                $rule = new ArtifactRuleValue($data['id'], $data['group_artifact_id'], $data['source_field_id'], $data['source_value_id'], $data['target_field_id'], $data['target_value_id']);
                break;
        }
        return $rule;
    }

    public function saveRuleValue($group_artifact_id, $source, $source_value, $target, $target_value)
    {
        $this->rules_dao->create($group_artifact_id, $source, $source_value, $target, $this->RULETYPE_VALUE, $target_value);
    }

    public function _saveRuleState($group_artifact_id, $source, $source_value, $target, $rule_type)
    {
        $this->rules_dao->deleteRuleState($group_artifact_id, $source, $source_value, $target, array($this->RULETYPE_HIDDEN, $this->RULETYPE_DISABLED, $this->RULETYPE_MANDATORY));
        $this->rules_dao->create($group_artifact_id, $source, $source_value, $target, $rule_type);
    }
    public function saveRuleHidden($group_artifact_id, $source, $source_value, $target)
    {
        $this->_saveRuleState($group_artifact_id, $source, $source_value, $target, $this->RULETYPE_HIDDEN);
    }

    public function saveRuleDisabled($group_artifact_id, $source, $source_value, $target)
    {
        $this->_saveRuleState($group_artifact_id, $source, $source_value, $target, $this->RULETYPE_DISABLED);
    }

    public function saveRuleMandatory($group_artifact_id, $source, $source_value, $target)
    {
        $this->_saveRuleState($group_artifact_id, $source, $source_value, $target, $this->RULETYPE_MANDATORY);
    }

    public function deleteRule($rule_id)
    {
        $deleted = $this->rules_dao->deleteByRuleId($rule_id);
        return $deleted;
    }

    public function deleteRuleValueBySource($artifact_type, $source, $source_value, $target)
    {
        $deleted = $this->rules_dao->deleteByGroupArtifactIdAndSourceAndSourceValueAndTargetAndRuleType($artifact_type, $source, $source_value, $target, $this->RULETYPE_VALUE);
        return $deleted;
    }
    public function deleteRuleValueByTarget($artifact_type, $source, $target, $target_value)
    {
        $deleted = $this->rules_dao->deleteByGroupArtifactIdAndSourceAndTargetAndTargetValueAndRuleType($artifact_type, $source, $target, $target_value, $this->RULETYPE_VALUE);
        return $deleted;
    }

    /**
    * Delete all rules for a tracker
    */
    public function deleteRulesByArtifactType($artifact_type)
    {
        $deleted = $this->rules_dao->deleteRulesByGroupArtifactId($artifact_type);
        return $deleted;
    }
    /**
    * Delete all rules related to a field
    */
    public function deleteRulesByFieldId($artifact_type, $field_id)
    {
        $deleted = $this->rules_dao->deleteByField($artifact_type, $field_id);
        return $deleted;
    }
    /**
    * Delete all rules related to a field value
    */
    public function deleteRulesByValueId($artifact_type, $field_id, $value_id)
    {
        $deleted = $this->rules_dao->deleteByFieldValue($artifact_type, $field_id, $value_id);
        return $deleted;
    }
    /**
    * copy rules from a tracker to another
    */
    public function copyRules($from_artifact_type, $to_artifact_type)
    {
        $copied = $this->rules_dao->copyRules($from_artifact_type, $to_artifact_type);
        return $copied;
    }
}
