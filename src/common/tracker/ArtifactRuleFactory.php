<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
class ArtifactRuleFactory //phpcs:ignore
{
    public ArtifactRuleDao $rules_dao;
    public array $rules;

    public int $RULETYPE_HIDDEN;
    public int $RULETYPE_DISABLED;
    public int $RULETYPE_MANDATORY;
    public int $RULETYPE_VALUE;

    public function __construct(ArtifactRuleDao $rules_dao)
    {
        $this->rules_dao = $rules_dao;
        $this->rules     = [];

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
        if (! $_artifactrulefactory_instance) {
            $rules_dao                     = new ArtifactRuleDao(CodendiDataAccess::instance());
            $_artifactrulefactory_instance = new ArtifactRuleFactory($rules_dao);
        }
        return $_artifactrulefactory_instance;
    }

    public function & getRuleById($id)
    {
        if (! isset($this->rules[$id])) {
            $this->rules[$id] = null;
            //We retrieve rule
            $dar = $this->rules_dao->searchById($id);
            if ($dar && ($rule_row = $dar->getRow())) {
                $rule_row['id']   = $id;
                $this->rules[$id] = $this->_buildRuleInstance($rule_row);
            }
        }
        return $this->rules[$id];
    }

    public function getAllRulesByArtifactTypeWithOrder($artifact_type)
    {
        $dar   = $this->rules_dao->searchByGroupArtifactIdWithOrder($artifact_type);
        $rules = [];
        while ($rule_row = $dar->getRow()) {
            if (! isset($this->rules[$rule_row['id']])) {
                $rule_row['group_artifact_id'] = $artifact_type;
                $this->rules[$rule_row['id']]  = $this->_buildRuleInstance($rule_row);
            }
            $rules[] = $this->rules[$rule_row['id']];
        }
        return $rules;
    }

    /**
    * @return ArtifactRule
    */
    public function _buildRuleInstance($data) //phpcs:ignore
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

    /**
    * copy rules from a tracker to another
    */
    public function copyRules($from_artifact_type, $to_artifact_type)
    {
        $copied = $this->rules_dao->copyRules($from_artifact_type, $to_artifact_type);
        return $copied;
    }
}
