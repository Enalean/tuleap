<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Workflow_Trigger_TriggerValidatorTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $validator;
    private $tracker;

    protected function setUp(): void
    {
        $rules_manager = \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class);
        $collection = new Tracker_Workflow_Trigger_TriggerRuleCollection();
        $this->tracker = \Mockery::spy(\Tracker::class);
        $rules_manager->shouldReceive('getForTargetTracker')->with($this->tracker)->andReturns($collection);

        $this->validator = new Tracker_Workflow_Trigger_TriggerValidator($rules_manager);
    }

    public function testItRaisesAnExceptionIfNoTarget(): void
    {
        $json = new stdClass();
        $json->target = null;

        $this->expectException(\Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException::class);
        $this->validator->validateJsonFormat($json, $this->tracker);
    }

    public function testItRaisesAnExceptionIfTargetHasNoFieldId(): void
    {
        $json = new stdClass();
        $json->target = new stdClass();

        $this->expectException(\Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException::class);
        $this->validator->validateJsonFormat($json, $this->tracker);
    }

    public function testItRaisesAnExceptionIfTargetHasNoFieldValueId(): void
    {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;

        $this->expectException(\Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException::class);
        $this->validator->validateJsonFormat($json, $this->tracker);
    }

    public function testItRaisesAnExceptionIfTargetHasNoCondition(): void
    {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;

        $this->expectException(\Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException::class);
        $this->validator->validateJsonFormat($json, $this->tracker);
    }

    public function testItRaisesAnExceptionIfTargetHasInvalidCondition(): void
    {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;
        $json->condition = 'bla';

        $this->expectException(\Tracker_Workflow_Trigger_Exception_TriggerInvalidConditionException::class);
        $this->validator->validateJsonFormat($json, $this->tracker);
    }

    public function testItRaisesAnExceptionIfNoTriggeringField(): void
    {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;
        $json->condition = Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF;

        $this->expectException(\Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException::class);
        $this->validator->validateJsonFormat($json, $this->tracker);
    }

    public function testItRaisesAnExceptionIfTriggeringFieldIsNotAnArray(): void
    {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;
        $json->condition = Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF;
        $json->triggering_fields = 'bla';

        $this->expectException(\Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException::class);
        $this->validator->validateJsonFormat($json, $this->tracker);
    }

    public function testItRaisesAnExceptionIfTriggeringFieldIsNotAnArrayOfFields(): void
    {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;
        $json->condition = Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF;
        $json->triggering_fields = array('bla');

        $this->expectException(\Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException::class);
        $this->validator->validateJsonFormat($json, $this->tracker);
    }

    public function testItRaisesAnExceptionIfTriggeringFieldsHaveIdenticalData(): void
    {
        $json = new stdClass();
        $json->target = new stdClass();
        $json->target->field_id = 34;
        $json->target->field_value_id = 75;
        $json->condition = Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF;

        $triggering_field = new stdClass();
        $triggering_field->field_id = 46;
        $triggering_field->field_value_id = 156;

        $json->triggering_fields = array($triggering_field, $triggering_field);

        $this->expectException(\Tracker_Workflow_Trigger_Exception_AddRuleJsonFormatException::class);
        $this->validator->validateJsonFormat($json, $this->tracker);
    }

    public function testItRaisesNoExceptionIfDataIsGood(): void
    {
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

        $this->validator->validateJsonFormat($json, $this->tracker);
    }

    public function testItRaisesAnExceptionIfTargetFieldAlreadyHasRuleForSameValue(): void
    {
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

        $field_list = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $bind_value = \Mockery::spy(\Tracker_FormElement_Field_List_BindValue::class);
        $bind_value->shouldReceive('getId')->andReturns(75);
        $target = new Tracker_Workflow_Trigger_FieldValue($field_list, $bind_value);
        $condition = 'some_condition';
        $triggers = array();
        $rule = new Tracker_Workflow_Trigger_TriggerRule(7, $target, $condition, $triggers);

        $collection = new Tracker_Workflow_Trigger_TriggerRuleCollection();
        $collection->push($rule);

        $tracker = \Mockery::spy(\Tracker::class);

        $this->expectException(\Tracker_Workflow_Trigger_Exception_TriggerInvalidTargetException::class);

        $rules_manager = \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class);
        $rules_manager->shouldReceive('getForTargetTracker')->with($tracker)->andReturns($collection);
        $validator = new Tracker_Workflow_Trigger_TriggerValidator($rules_manager);

        $validator->validateJsonFormat($json, $tracker);
    }
}
