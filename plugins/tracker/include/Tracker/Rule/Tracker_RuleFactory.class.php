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
require_once('List/List.class.php');
require_once('Tracker_Rule.class.php');

/**
* Factory of rules
*
* Base class to create, retrieve, update or delete rules
*/
class Tracker_RuleFactory {

    var $rules_dao;
    var $rules;


    function Tracker_RuleFactory($rules_dao) {
        $this->rules_dao = $rules_dao;
        $this->rules = array();
    }

    /**
    * @return Tracker_RuleFactory is a singleton
    */
    function instance() {
        static $_artifactrulefactory_instance;
        if (!$_artifactrulefactory_instance) {
            $rules_dao         = new Tracker_RuleDao();
            $_artifactrulefactory_instance = new Tracker_RuleFactory($rules_dao);
        }
        return $_artifactrulefactory_instance;
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
            default: //RULETYPE_VALUE
                $rule_list = new Tracker_Rule_List();
                $rule_list->setSourceValue($data['source_value_id'])
                        ->setTargetValue($data['target_value_id'])
                        ->setId($data['id'])
                        ->setTrackerId($data['tracker_id'])
                        ->setSourceFieldId($data['source_field_id'])
                        ->setTargetFieldId($data['target_field_id']);
                $rule =& $rule_list;
                break;
        }
        return $rule;
    }

    public function saveObject(array $rules, Tracker $tracker) {
        foreach ($rules as $rule) {
            $this->saveRuleValue($tracker->id, $rule->source_field->getId(), $rule->source_value->getId(), $rule->target_field->getId(), $rule->target_value->getId());
        }
    }

    function saveRuleValue($tracker_id, $source, $source_value, $target, $target_value) {
        $this->rules_dao->create($tracker_id, $source, $source_value, $target, Tracker_Rule::RULETYPE_VALUE, $target_value);
    }

    function deleteRule($rule_id) {
        $deleted = $this->rules_dao->deleteByRuleId($rule_id);
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
    * Duplicate the rules from tracker source to tracker target
    *
    * @param int   $from_tracker_id The Id of the tracker source
    * @param int   $to_tracker_id   The Id of the tracker target
    * @param array $field_mapping   The mapping of the fields of the tracker
    *
    * @return void
    */
    public function duplicate($from_tracker_id, $to_tracker_id, $field_mapping) {
        $dar = $this->rules_dao->searchByTrackerId($from_tracker_id);

        // Retrieve rules of tracker from
        while ($row = $dar->getRow()) {
            // if we already have the status field, just jump to open values
            $source_field_id = $row['source_field_id'];
            $target_field_id = $row['target_field_id'];
            $source_value_id = $row['source_value_id'];
            $target_value_id = $row['target_value_id'];
            $rule_type = $row['rule_type'];
            // walk the mapping array to get the corresponding field values for tracker TARGET
            foreach ($field_mapping as $mapping) {
                if ($mapping['from'] == $source_field_id) {
                    $duplicate_source_field_id = $mapping['to'];

                    $mapping_values = $mapping['values'];
                    $duplicate_source_value_id = $mapping_values[$source_value_id];
                }
                if ($mapping['from'] == $target_field_id) {
                    $duplicate_target_field_id = $mapping['to'];

                    $mapping_values = $mapping['values'];
                    $duplicate_target_value_id = $mapping_values[$target_value_id];
                }
            }
            $this->rules_dao->create($to_tracker_id, $duplicate_source_field_id, $duplicate_source_value_id, $duplicate_target_field_id, $rule_type, $duplicate_target_value_id);
        }
    }

    /**
     * Creates a Tracker_Semantic Object
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported semantic
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Tracker          $tracker     to which the rule is attached
     *
     * @return Tracker_Rule_List The rule object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping, $tracker) {
        $rules = array();
        foreach ($xml->rule as $xml_rule) {
            $xml_source_field_attributes = $xml_rule->source_field->attributes();
            $source_field = $xmlMapping[(string)$xml_source_field_attributes['REF']];

            $xml_target_field_attributes = $xml_rule->target_field->attributes();
            $target_field = $xmlMapping[(string)$xml_target_field_attributes['REF']];

            $xml_source_value_attributes = $xml_rule->source_value->attributes();
            $source_value = $xmlMapping[(string)$xml_source_value_attributes['REF']];

            $xml_target_value_attributes = $xml_rule->target_value->attributes();
            $target_value = $xmlMapping[(string)$xml_target_value_attributes['REF']];
            
            $rule_list = new Tracker_Rule_List();
            $rule_list->setSourceValue($source_value)
                    ->setTargetValue($target_value)
                    ->setId(0)
                    ->setTrackerId($tracker->getId())
                    ->setSourceFieldId($source_field)
                    ->setTargetFieldId($target_field);

            $rules[] = $rule_list;
        }
        return $rules;
    }

    /**
     * Get dependency rules of a Source and Target
     *
     * @param $tracker_id, the id of the tracker
     * @param $field_source_id, the id of the source field
     * @param $field_target_id, the id of the target field
     *
     * @return array of Tracker_Rule_List
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
        $rule_list = new Tracker_Rule_List();
        $rule_list->setSourceValue($row['source_value_id'])
                ->setTargetValue($row['target_value_id'])
                ->setId($row['id'])
                ->setTrackerId($row['tracker_id'])
                ->setSourceFieldId($row['source_field_id'])
                ->setTargetFieldId($row['target_field_id']);

        return $rule_list;
    }
}
?>