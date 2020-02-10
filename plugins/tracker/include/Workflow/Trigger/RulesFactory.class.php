<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * Build a PHP representation of a Rule out of json
 */
class Tracker_Workflow_Trigger_RulesFactory
{
    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Tracker_Workflow_Trigger_TriggerValidator */
    private $validator;

    public function __construct(Tracker_FormElementFactory $formelement_factory, Tracker_Workflow_Trigger_TriggerValidator $validator)
    {
        $this->formelement_factory = $formelement_factory;
        $this->validator = $validator;
    }

    /**
     * Transfrom a Json decoded input into a useable php object
     *
     * It ensures data and format correctness
     *
     *
     * @throws Tracker_FormElement_InvalidFieldException
     * @throws Tracker_FormElement_InvalidFieldValueException
     * @throws Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException
     * @throws Tracker_Workflow_Trigger_Exception_TriggerInvalidConditionException
     *
     * @return Tracker_Workflow_Trigger_TriggerRule
     */
    public function getRuleFromJson(Tracker $tracker, stdClass $json)
    {
        $this->validator->validateJsonFormat($json, $tracker);

        $target    = $this->getTarget($tracker->getId(), $json->target->field_id, $json->target->field_value_id);
        $condition = $json->condition;
        $triggers  = $this->getTriggeringFields($tracker, $json->triggering_fields);
        return new Tracker_Workflow_Trigger_TriggerRule(
            null,
            $target,
            $condition,
            $triggers
        );
    }

    private function getTarget($tracker_id, $target_field_id, $target_value_id)
    {
        $field       = $this->getTargetField($target_field_id, $tracker_id);
        $field_value = $this->getTargetFieldValue($target_value_id, $field);
        return new Tracker_Workflow_Trigger_FieldValue($field, $field_value);
    }

    private function getTargetField($target_field_id, $tracker_id)
    {
        $field = $this->formelement_factory->getUsedFormElementFieldById($target_field_id);
        if ($field) {
            if ($field->getTracker()->getId() == $tracker_id) {
                return $field;
            }
            throw new Tracker_FormElement_InvalidFieldException("Field doesn't belong to target tracker");
        }
        throw new Tracker_FormElement_InvalidFieldException('Unknown field');
    }

    private function getTargetFieldValue($target_value_id, Tracker_FormElement_Field_List $target_field)
    {
        return $this->getMatchingValueById(
            $target_field,
            $target_value_id
        );
    }

    private function getTriggeringFields(Tracker $target_tracker, array $triggering_fields)
    {
        $fields = array();
        foreach ($triggering_fields as $triggering_field) {
            $fields[] = $this->getOneTriggeringField($target_tracker, $triggering_field->field_id, $triggering_field->field_value_id);
        }
        return $fields;
    }

    private function getOneTriggeringField(Tracker $target_tracker, $trigger_field_id, $trigger_value_id)
    {
        $field = $this->formelement_factory->getUsedFormElementFieldById($trigger_field_id);
        if ($field) {
            if ($field->getTracker()->getParent() == $target_tracker) {
                return new Tracker_Workflow_Trigger_FieldValue(
                    $field,
                    $this->getMatchingValueById($field, $trigger_value_id)
                );
            }
            throw new Tracker_FormElement_InvalidFieldException("Trigger field doesn't belong to target tracker");
        }
    }

    private function getMatchingValueById(Tracker_FormElement_Field_List $field, $value_id)
    {
        foreach ($field->getAllValues() as $value) {
            if ($value->getId() == $value_id) {
                return $value;
            }
        }
        throw new Tracker_FormElement_InvalidFieldValueException("Value doesn't belong to field");
    }
}
