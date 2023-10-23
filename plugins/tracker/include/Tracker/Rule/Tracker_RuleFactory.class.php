<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
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

use Tuleap\Tracker\Rule\InvolvedFieldsInRule;

/**
* Factory of rules
*
* Base class to create, retrieve, update or delete rules
*/
class Tracker_RuleFactory
{
    /**
     *
     * @var Tracker_RuleDao
     */
    private $rules_dao;


    private $rules;

    /**
     *
     * @var Tracker_Rule_List_Factory
     */
    private $list_factory;

    /**
     *
     * @var Tracker_Rule_Date_Factory
     */
    private $date_factory;

    /**
     *
     * @var Tracker_Rule_Date_Dao
     */
    private $date_dao;

    /**
     *
     * @var Tracker_Rule_List_Dao
     */
    private $list_dao;


    public function __construct(Tracker_RuleDao $rules_dao)
    {
        $this->rules_dao = $rules_dao;
        $this->rules     = [];
    }

    /**
    * @return Tracker_RuleFactory is a singleton
    */
    public static function instance()
    {
        static $_artifactrulefactory_instance;
        if (! $_artifactrulefactory_instance) {
            $rules_dao                     = new Tracker_RuleDao();
            $_artifactrulefactory_instance = new Tracker_RuleFactory($rules_dao);
        }
        return $_artifactrulefactory_instance;
    }

    /**
     * @return Tracker_Rule_List[]
     */
    public function getAllListRulesByTrackerWithOrder($tracker_id)
    {
        $dar   = $this->rules_dao->searchByTrackerIdWithOrder($tracker_id);
        $rules = [];
        while ($rule_row = $dar->getRow()) {
            if (! isset($this->rules[$rule_row['id']])) {
                $rule_row['tracker_id']       = $tracker_id;
                $this->rules[$rule_row['id']] =& $this->_buildRuleInstance($rule_row);
            }
            $rules[] =& $this->rules[$rule_row['id']];
        }
        return $rules;
    }

    /**
    * @return Tracker_Rule
    */
    public function &_buildRuleInstance($data)
    {
        //We create Rule
        switch ($data['rule_type']) {
            default: //RULETYPE_VALUE
                $rule_list = new Tracker_Rule_List();
                $rule_list->setSourceValue($data['source_value_id'])
                    ->setTargetValue($data['target_value_id'])
                    ->setId($data['id'])
                    ->setTrackerId($data['tracker_id'])
                    ->setSourceFieldId((int) $data['source_field_id'])
                    ->setTargetFieldId((int) $data['target_field_id']);
                $rule =& $rule_list;
                break;
        }
        return $rule;
    }

    /**
     * called by TrackerFactory::saveObject();
     * @param array $rules
     */
    public function saveObject(array $rules, Tracker $trackerDB)
    {
        if (isset($rules['list_rules'])) {
            foreach ($rules['list_rules'] as $list_rule) {
                assert($list_rule instanceof Tracker_Rule_List);
                $list_rule->setTrackerId($trackerDB->getId());
                $this->getListFactory()->insert($list_rule);
            }
        }

        if (isset($rules['date_rules'])) {
            foreach ($rules['date_rules'] as $date_rule) {
                $date_rule->setTrackerId($trackerDB->getId());
                $this->getDateFactory()->insert($date_rule);
            }
        }
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
    public function deleteRulesBySourceTarget($tracker_id, $field_source_id, $field_target_id)
    {
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
    public function duplicate($from_tracker_id, $to_tracker_id, $field_mapping)
    {
        $this->getListFactory()->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
        $this->getDateFactory()->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }

    /**
     * Creates a Tracker_Semantic Object
     *
     * Called by TrackerFactory::getInstanceFromXML()
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported semantic
     * @param array            $xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Tracker          $tracker     to which the rule is attached
     *
     * @return Tracker_Rule_List The rule object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping, $tracker)
    {
        $rules = [];
        //test this better
        if (property_exists($xml, 'list_rules')) {
            $list_rules          = $xml->list_rules;
            $rules['list_rules'] = $this->generateListRulesArrayFromXml($list_rules, $xmlMapping, $tracker);
        }

        if (property_exists($xml, 'date_rules')) {
            $date_rules          = $xml->date_rules;
            $rules['date_rules'] = $this->generateDateRulesArrayFromXml($date_rules, $xmlMapping, $tracker);
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
    public function getDependenciesBySourceTarget($tracker_id, $field_source_id, $field_target_id)
    {
        $dependencies = [];
        foreach ($this->rules_dao->searchBySourceTarget($tracker_id, $field_source_id, $field_target_id) as $row) {
            $dependencies[$row['id']] = $this->getInstanceFromRow($row);
        }
        return $dependencies;
    }

    public function getInvolvedFieldsByTrackerId($tracker_id)
    {
        return $this->rules_dao->searchInvolvedFieldsByTrackerId($tracker_id);
    }

    public function getInstanceFromRow($row)
    {
        $rule_list = new Tracker_Rule_List();
        $rule_list->setSourceValue($row['source_value_id'])
            ->setTargetValue($row['target_value_id'])
            ->setId($row['id'])
            ->setTrackerId($row['tracker_id'])
            ->setSourceFieldId($row['source_field_id'])
            ->setTargetFieldId($row['target_field_id']);

        return $rule_list;
    }

    /**
     *
     * @return Tracker_Rule_List_Factory
     */
    public function getListFactory()
    {
        if (! $this->list_factory) {
            $listDao            = $this->getListDao();
            $this->list_factory =  new Tracker_Rule_List_Factory($listDao);
        }

        return $this->list_factory;
    }

    /**
     *
     * @return Tracker_RuleFactory
     */
    public function setListFactory(Tracker_Rule_List_Factory $factory)
    {
        $this->list_factory = $factory;
        return $this;
    }

    /**
     *
     * @return Tracker_Rule_Date_Factory
     */
    public function getDateFactory()
    {
        if (! $this->date_factory) {
            $dateDao              = $this->getDateDao();
            $form_element_factory = Tracker_FormElementFactory::instance();
            $this->date_factory   =  new Tracker_Rule_Date_Factory($dateDao, $form_element_factory);
        }

        return $this->date_factory;
    }

    /**
     *
     * @return Tracker_RuleFactory
     */
    public function setDateFactory(Tracker_Rule_Date_Factory $factory)
    {
        $this->date_factory = $factory;
        return $this;
    }

    /**
     *
     * @return Tracker_Rule_List_Dao
     */
    public function getListDao()
    {
        if (! $this->list_dao) {
            $this->list_dao =  new Tracker_Rule_List_Dao();
        }

        return $this->list_dao;
    }

    /**
     *
     * @param Tracker_Rule_List_Dao $dao
     * @return \Tracker_RuleFactory
     */
    public function setListDao($dao)
    {
        $this->list_dao = $dao;
        return $this;
    }

    /**
     *
     * @return Tracker_Rule_date_Dao
     */
    public function getDateDao()
    {
        if (! $this->date_dao) {
            $this->date_dao =  new Tracker_Rule_Date_Dao();
        }

        return $this->date_dao;
    }

    /**
     *
     * @return \Tracker_RuleFactory
     */
    public function setDateDao(Tracker_Rule_Date_Dao $dao)
    {
        $this->date_dao = $dao;
        return $this;
    }

    /**
     *
     * @param SimpleXMLElement $date_rules         containing the structure of the imported semantic
     * @param array            $xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Tracker          $tracker     to which the rule is attached
     * @return array of \Tracker_Rule_Date
     */
    private function generateDateRulesArrayFromXml($date_rules, &$xmlMapping, $tracker)
    {
        $rules = [];

        foreach ($date_rules->rule as $xml_rule) {
            $xml_source_field_attributes = $xml_rule->source_field->attributes();
            $source_field                = $xmlMapping[(string) $xml_source_field_attributes['REF']];

            $xml_target_field_attributes = $xml_rule->target_field->attributes();
            $target_field                = $xmlMapping[(string) $xml_target_field_attributes['REF']];

            $xml_comparator_attributes = $xml_rule->comparator->attributes();
            $comparator                = $xml_comparator_attributes['type'];

            $rule_list = new Tracker_Rule_Date();
            $rule_list->setComparator($comparator)
                ->setTrackerId($tracker->getId())
                ->setSourceField($source_field)
                ->setTargetField($target_field);

            $rules[] = $rule_list;
        }

        return $rules;
    }

    /**
     *
     * @param SimpleXMLElement $list_rules         containing the structure of the imported semantic
     * @param array            $xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Tracker          $tracker     to which the rule is attached
     * @return array of Tracker_Rule_List
     */
    private function generateListRulesArrayFromXml($list_rules, &$xmlMapping, $tracker)
    {
        $rules = [];

        foreach ($list_rules->rule as $xml_rule) {
            $xml_source_field_attributes = $xml_rule->source_field->attributes();
            if (! isset($xmlMapping[(string) $xml_source_field_attributes['REF']])) {
                continue;
            }
            $source_field = $xmlMapping[(string) $xml_source_field_attributes['REF']];

            $xml_target_field_attributes = $xml_rule->target_field->attributes();
            if (! isset($xmlMapping[(string) $xml_target_field_attributes['REF']])) {
                continue;
            }
            $target_field = $xmlMapping[(string) $xml_target_field_attributes['REF']];

            $xml_source_value_attributes = $xml_rule->source_value->attributes();
            if (isset($xml_source_value_attributes['is_none'])) {
                $source_value = new Tracker_FormElement_Field_List_Bind_StaticValue_None();
            } else {
                $source_value = $xmlMapping[(string) $xml_source_value_attributes['REF']];
            }

            $xml_target_value_attributes = $xml_rule->target_value->attributes();
            if (isset($xml_target_value_attributes['is_none'])) {
                $target_value = new Tracker_FormElement_Field_List_Bind_StaticValue_None();
            } else {
                $target_value = $xmlMapping[(string) $xml_target_value_attributes['REF']];
            }

            $rule_list = new Tracker_Rule_List();
            $rule_list->setSourceValue($source_value)
                ->setTargetValue($target_value)
                ->setTrackerId($tracker->getId())
                ->setSourceField($source_field)
                ->setTargetField($target_field);
            $rules[] = $rule_list;
        }

        return $rules;
    }

    /**
     * @return InvolvedFieldsInRule[]
     */
    public function getInvolvedFieldsByTrackerIdCollection(int $tracker_id): array
    {
        $involved_fields            = $this->getInvolvedFieldsByTrackerId($tracker_id);
        $involved_fields_collection = [];

        foreach ($involved_fields as $fields) {
            if (! isset($fields["source_field_id"], $fields["target_field_id"])) {
                continue;
            }
            $involved_fields_collection[] = new InvolvedFieldsInRule((int) $fields["source_field_id"], (int) $fields["target_field_id"]);
        }

        return $involved_fields_collection;
    }
}
