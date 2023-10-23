<?php
/**
  * Copyright (c) Enalean, 2012 - Present. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

/**
* Factory of rules
*
* Base class to create, retrieve, update or delete rules
*/
class Tracker_Rule_List_Factory
{
    /**
     *
     * @var Tracker_Rule_List_Dao
     */
    protected $dao;

    public function __construct(Tracker_Rule_List_Dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     *
     * @param int $source_field_id
     * @param int $target_field_id
     * @param int $tracker_id
     * @param int $source_value
     * @param int $target_value
     * @return Tracker_Rule_List
     */
    public function create($source_field_id, $target_field_id, $tracker_id, $source_value, $target_value)
    {
        $list_rule = $this->populate(new Tracker_Rule_List(), $tracker_id, $source_field_id, $target_field_id, $source_value, $target_value);
        $rule_id   = $this->insert($list_rule);

        $list_rule->setId($rule_id);

        return $list_rule;
    }

    /**
     *
     * @return bool
     */
    public function delete(Tracker_Rule_List $list_rule)
    {
        return $this->dao->delete($list_rule);
    }

    /**
     *
     * @param int $rule_id
     * @return Tracker_Rule_List
     */
    public function searchById($rule_id)
    {
        $rule = $this->dao->searchById($rule_id);

        if (! $rule) {
            return null;
        }

        return $this->populate(new Tracker_Rule_List(), $rule['source_field_id'], $rule['target_field_id'], $rule['tracker_id'], $rule['source_value_id'], $rule['target_value_id']);
    }

    /**
     *
     * @param int $tracker_id
     * @return array An array of Tracker_Rule_List objects
     */
    public function searchByTrackerId($tracker_id)
    {
        $rules = $this->dao->searchByTrackerId($tracker_id);

        if (! $rules) {
            return [];
        }

        $rules_array = [];

        foreach ($rules as $rule) {
            $list_rule     = $this->populate(new Tracker_Rule_List(), $rule['tracker_id'], $rule['source_field_id'], $rule['target_field_id'], $rule['source_value_id'], $rule['target_value_id']);
            $rules_array[] = $list_rule;
        }

        return $rules_array;
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
        $rows = $this->dao->searchByTrackerId($from_tracker_id);

        // Retrieve rules of tracker from
        foreach ($rows as $row) {
            // if we already have the status field, just jump to open values
            $source_field_id = $row['source_field_id'];
            $target_field_id = $row['target_field_id'];
            $source_value_id = $row['source_value_id'];
            $target_value_id = $row['target_value_id'];
            // walk the mapping array to get the corresponding field values for tracker TARGET
            foreach ($field_mapping as $mapping) {
                if ($mapping['from'] == $source_field_id) {
                    $duplicate_source_field_id = $mapping['to'];

                    $mapping_values = $mapping['values'];
                    if ((int) $source_value_id === Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
                        $duplicate_source_value_id = $source_value_id;
                    } else {
                        $duplicate_source_value_id = $mapping_values[$source_value_id];
                    }
                }
                if ($mapping['from'] == $target_field_id) {
                    $duplicate_target_field_id = $mapping['to'];

                    $mapping_values = $mapping['values'];
                    if ((int) $target_value_id === Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
                        $duplicate_target_value_id = $target_value_id;
                    } else {
                        $duplicate_target_value_id = $mapping_values[$target_value_id];
                    }
                }
            }
            $this->dao->create($to_tracker_id, $duplicate_source_field_id, $duplicate_source_value_id, $duplicate_target_field_id, $duplicate_target_value_id);
        }
    }

    /**
     *
     * @param array $xmlMapping
     * @param Tracker_FormElementFactory $form_element_factory
     * @param int $tracker_id
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping, $form_element_factory, $tracker_id)
    {
        $rules      = $this->searchByTrackerId($tracker_id);
        $list_rules = $root->addChild('list_rules');

        foreach ($rules as $rule) {
            $source_field = $form_element_factory->getFormElementById($rule->getSourceFieldId());
            $target_field = $form_element_factory->getFormElementById($rule->getTargetFieldId());
            $bf           = new Tracker_FormElement_Field_List_BindFactory();
            //TODO: handle sb/msb bind to users and remove condition
            if ($bf->getType($source_field->getBind()) == 'static' && $bf->getType($target_field->getBind()) == 'static') {
                $child = $list_rules->addChild('rule');
                $child->addChild('source_field')->addAttribute('REF', array_search($rule->source_field, $xmlMapping));
                $child->addChild('target_field')->addAttribute('REF', array_search($rule->target_field, $xmlMapping));
                if ($rule->source_value == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
                    $child->addChild('source_value')->addAttribute('is_none', '1');
                } else {
                    $child->addChild('source_value')->addAttribute('REF', array_search($rule->source_value, $xmlMapping['values']));
                }

                if ($rule->target_value == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
                    $child->addChild('target_value')->addAttribute('is_none', '1');
                } else {
                    $child->addChild('target_value')->addAttribute('REF', array_search($rule->target_value, $xmlMapping['values']));
                }
            }
        }
    }

    /**
     *
     * @return int The ID of the tracker_Rule created
     */
    public function insert(Tracker_Rule_List $list_rule)
    {
        return $this->dao->insert($list_rule);
    }

    /**
     *
     * @param int $tracker_id
     * @param int $source_field_id
     * @param int $target_field_id
     * @param int $source_value
     * @param int $target_value
     * @return \Tracker_Rule_List
     */
    private function populate(Tracker_Rule_List $list_rule, $tracker_id, $source_field_id, $target_field_id, $source_value, $target_value)
    {
        $list_rule->setTrackerId($tracker_id)
            ->setSourceFieldId($source_field_id)
            ->setTargetFieldId($target_field_id)
            ->setSourceValue($source_value)
            ->setTargetValue($target_value);

        return $list_rule;
    }
}
