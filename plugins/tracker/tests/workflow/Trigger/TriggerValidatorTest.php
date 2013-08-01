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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Workflow_Trigger_TriggerValidator_Test extends TuleapTestCase {

    protected $json_input;
    protected $validator;

    public function setUp() {
        parent::setUp();
      
        $this->validator = new Tracker_Workflow_Trigger_TriggerValidator();
        $this->json_input = json_decode(file_get_contents(dirname(__FILE__).'/_fixtures/add_rule.json'));
    }
}

class Tracker_Workflow_Trigger_TriggerValidator_validateJsonFormat_Test extends Tracker_Workflow_Trigger_TriggerValidator_Test {

    public function itRaisesAnExceptionIfNoTarget() {
        $json = new stdClass();
        $json->target = null;

        $this->expectException('Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException');
        $this->validator->validateJsonFormat($json);
    }

    public function itRaisesAnExceptionIfTargetHasNoFieldId() {
        $json = new stdClass();
        $json->target = new stdClass();

        $this->expectException('Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException');
        $this->validator->validateJsonFormat($json);
    }

    public function itRaisesAnExceptionIfTargetHasNoFieldValueId() {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;

        $this->expectException('Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException');
        $this->validator->validateJsonFormat($json);
    }

    public function itRaisesAnExceptionIfTargetHasNoCondition() {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;

        $this->expectException('Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException');
        $this->validator->validateJsonFormat($json);
    }

    public function itRaisesAnExceptionIfTargetHasInvalidCondition() {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;
        $json->condition = 'bla';

        $this->expectException('Tracker_Workflow_Trigger_Exception_TriggerInvalidConditionException');
        $this->validator->validateJsonFormat($json);
    }

    public function itRaisesAnExceptionIfNoTriggeringField() {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;
        $json->condition = Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF;

        $this->expectException('Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException');
        $this->validator->validateJsonFormat($json);
    }

    public function itRaisesAnExceptionIfTriggeringFieldIsNotAnArray() {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;
        $json->condition = Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF;
        $json->triggering_fields = 'bla';

        $this->expectException('Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException');
        $this->validator->validateJsonFormat($json);
    }

    public function itRaisesAnExceptionIfTriggeringFieldIsNotAnArrayOfFields() {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;
        $json->condition = Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF;
        $json->triggering_fields = array('bla');

        $this->expectException('Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException');
        $this->validator->validateJsonFormat($json);
    }

    public function itRaisesAnExceptionIfTriggeringFieldsHaveIdenticalData() {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;
        $json->condition = Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF;

        $triggering_field = new stdClass();
        $triggering_field->field_id = 46;
        $triggering_field->field_value_id = 156;

        $json->triggering_fields = array($triggering_field, $triggering_field);

        $this->expectException('Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException');
        $this->validator->validateJsonFormat($json);
    }

    public function itRaisesNoExceptionIfDataIsGood() {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;
        $json->condition = Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF;

        $triggering_field = new stdClass();
        $triggering_field->field_id = 46;
        $triggering_field->field_value_id = 156;
        $triggering_field2 = new stdClass();
        $triggering_field2->field_id = 67;
        $triggering_field2->field_value_id = 62;

        $json->triggering_fields = array($triggering_field, $triggering_field2);

        $this->validator->validateJsonFormat($json);
    }
}
?>
