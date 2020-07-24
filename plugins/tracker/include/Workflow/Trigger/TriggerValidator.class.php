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
 * validates a PHP representation of a Rule out of json
 */
class Tracker_Workflow_Trigger_TriggerValidator
{
    private $triggering_fields = [];
    private $rules_manager;

    public function __construct(Tracker_Workflow_Trigger_RulesManager $rules_manager)
    {
        $this->rules_manager = $rules_manager;
    }

    public function validateJsonFormat(stdClass $json, Tracker $tracker)
    {
        $this->validateJsonTargetFormat($json);
        $this->validateJsonTargetUniqueness($json, $tracker);
        $this->validateJsonConditionFormat($json);
        $this->validateJsonTriggeringFieldsFormat($json);
    }

    private function validateJsonTargetFormat(stdClass $json)
    {
        if (! isset($json->target)) {
            throw new Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException('target is missing');
        }
        $this->validateJsonFieldValueFormat($json->target);
    }

    private function validateJsonTargetUniqueness($json, $tracker)
    {
        $existing_rules = $this->rules_manager->getForTargetTracker($tracker);

        foreach ($existing_rules as $rule) {
            if ($rule->getTarget()->getValue()->getId() == $json->target->field_value_id) {
                throw new Tracker_Workflow_Trigger_Exception_TriggerInvalidTargetException('trigger already exists for field value');
            }
        }
    }

    private function validateJsonConditionFormat(stdClass $json)
    {
        if (! isset($json->condition)) {
            throw new Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException('condition is missing');
        }
        $valid_condition = new Valid_WhiteList('condition', [
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
        ]);
        $valid_condition->required();
        $valid_condition->disableFeedback();
        if (! $valid_condition->validate($json->condition)) {
            throw new Tracker_Workflow_Trigger_Exception_TriggerInvalidConditionException();
        }
    }

    private function validateJsonTriggeringFieldsFormat(stdClass $json)
    {
        if (! isset($json->triggering_fields)) {
            throw new Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException('no triggering_fields');
        }
        if (! is_array($json->triggering_fields)) {
            throw new Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException('triggering_fields must be an array');
        }
        foreach ($json->triggering_fields as $triggering_field) {
            $this->validateJsonFieldValueFormat($triggering_field);
            $this->validateTriggeringFieldUniqueness($triggering_field);
        }
    }

    private function validateJsonFieldValueFormat($json)
    {
        if (! isset($json->field_id)) {
            throw new Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException('field_id is missing');
        }
        if (! isset($json->field_value_id)) {
            throw new Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException('field_value_id is missing');
        }
    }

    private function validateTriggeringFieldUniqueness($json)
    {
        $hash = $json->field_id . '###' . $json->field_value_id;

        if (in_array($hash, $this->triggering_fields)) {
            throw new Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException('triggering fields must be unique');
        }

        $this->triggering_fields[] = $hash;
    }
}
