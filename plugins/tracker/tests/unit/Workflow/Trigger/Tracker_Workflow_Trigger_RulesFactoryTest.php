<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
final class Tracker_Workflow_Trigger_RulesFactoryTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $tracker_id;
    private $tracker;
    private $formelement_factory;
    private $factory;
    private $json_input;
    private $validator;
    /**
     * @var int
     */
    private $target_value_id;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $target_field_value;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private $target_field;
    /**
     * @var int
     */
    private $target_field_id;
    /**
     * @var int
     */
    private $trigger_field_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $not_child_tracker;
    /**
     * @var int
     */
    private $trigger_value_id;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $trigger_field_value;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private $trigger_field;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $child_tracker_1;
    /**
     * @var int
     */
    private $trigger_field_id_1;
    /**
     * @var int
     */
    private $trigger_value_id_1;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $trigger_field_value_1;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private $trigger_field_1;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $trigger_field_value_2;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private $trigger_field_2;

    protected function setUp(): void
    {
        $this->tracker_id = 274;
        $this->tracker = Mockery::spy(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn($this->tracker_id);
        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->validator = \Mockery::spy(\Tracker_Workflow_Trigger_TriggerValidator::class);
        $this->factory = new Tracker_Workflow_Trigger_RulesFactory($this->formelement_factory, $this->validator);
        $this->json_input = json_decode(file_get_contents(__DIR__ . '/_fixtures/add_rule.json'));

        $this->target_value_id    = 250;
        $this->target_field_value = new Tracker_FormElement_Field_List_Bind_StaticValue($this->target_value_id, 'label', 'desc', 0, false);
        $this->target_field       = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $this->target_field->shouldReceive('getTracker')->andReturn($this->tracker);
        $this->target_field->shouldReceive('getAllValues')->andReturns([
           new Tracker_FormElement_Field_List_Bind_StaticValue(9998, 'label', 'desc', 0, false),
           $this->target_field_value,
           new Tracker_FormElement_Field_List_Bind_StaticValue(9999, 'label', 'desc', 0, false),
        ]);
    }

    public function testItFetchesFieldFromFormElementFactory(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->with('30')->once()->andReturns($this->target_field);

        $this->factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    public function testItRaisesAnExceptionIfFieldIsInvalid(): void
    {
        $this->json_input->target->field_id = '40';

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    public function testItRaisesAnExceptionWhenFieldDoesntBelongToTracker(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(37);
        $field = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $field->shouldReceive('getTracker')->andReturn($tracker);
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->andReturns($field);

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    public function testItBuildsTheRuleWithTargetFieldAndValue(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->with('30')->andReturns($this->target_field);

        $rule = $this->factory->getRuleFromJson($this->tracker, $this->json_input);
        $this->assertEquals($this->target_field, $rule->getTarget()->getField());
        $this->assertEquals($this->target_field_value, $rule->getTarget()->getValue());
    }

    public function testItRaisesAnExceptionWhenTargetValueDoesntBelongToField(): void
    {
        $target_field = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $target_field->shouldReceive('getTracker')->andReturn($this->tracker);
        $target_field->shouldReceive('getAllValues')->andReturns([]);
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->andReturns($target_field);

        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);

        $this->factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    public function testItBuildsTheRuleWithCondition(): void
    {
        $this->target_field->shouldReceive('getAllValues')->andReturns([$this->target_field_value]);
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->with('30')->andReturns(
            $this->target_field
        );
        $rule = $this->factory->getRuleFromJson($this->tracker, $this->json_input);
        $this->assertEquals(Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF, $rule->getCondition());
    }

    private function setupRuleFromRequestTriggerTests(): void
    {
        $this->target_field_id = 30;
        $this->tracker_id = 274;
        $this->tracker = Mockery::spy(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn($this->tracker_id);
        $this->target_value_id = 250;
        $this->target_field_value = new Tracker_FormElement_Field_List_Bind_StaticValue($this->target_value_id, 'label', 'desc', 0, false);

        $this->target_field = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $this->target_field->shouldReceive('getId')->andReturn($this->target_field_id);
        $this->target_field->shouldReceive('getTracker')->andReturn($this->tracker);
        $this->target_field->shouldReceive('getAllValues')->andReturns([
                                                                           $this->target_field_value,
                                                                       ]);
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->with("$this->target_field_id")->andReturns($this->target_field);
    }

    public function testItHasATrigger(): void
    {
        $this->setupRuleFromRequestTriggerTests();
        $child_tracker = Mockery::spy(Tracker::class);
        $child_tracker->shouldReceive('getParent')->andReturn($this->tracker);

        $this->trigger_field_id   = 369;
        $this->trigger_value_id   = 852;
        $this->trigger_field_value = new Tracker_FormElement_Field_List_Bind_StaticValue($this->trigger_value_id, 'label', 'desc', 0, false);

        $this->trigger_field = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $this->trigger_field->shouldReceive('getTracker')->andReturn($child_tracker);
        $this->trigger_field->shouldReceive('getAllValues')->andReturns([
                                                                            $this->trigger_field_value,
                                                                        ]);

        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->with("$this->trigger_field_id")->andReturns($this->trigger_field);

        $rule     = $this->factory->getRuleFromJson($this->tracker, $this->json_input);
        $triggers = $rule->getTriggers();
        $this->assertCount(1, $triggers);
        $rule1 = array_pop($triggers);
        $this->assertEquals($this->trigger_field, $rule1->getField());
        $this->assertEquals($this->trigger_field_value, $rule1->getValue());
    }

    public function testItRaisesAnErrorIfTriggerTrackerDoesntBelongToChildren(): void
    {
        $this->setupRuleFromRequestTriggerTests();
        $this->not_child_tracker = Mockery::spy(Tracker::class);
        $this->not_child_tracker->shouldReceive('getParent')->andReturn(null);

        $this->trigger_field_id = 369;
        $this->trigger_value_id = 852;

        $this->trigger_field = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $this->trigger_field->shouldReceive('getTracker')->andReturn($this->not_child_tracker);

        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->with("$this->trigger_field_id")->andReturns($this->trigger_field);

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);

        $this->factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    private function setUpTwoTriggers()
    {
        // field 1
        $this->child_tracker_1 = Mockery::mock(Tracker::class);
        $this->child_tracker_1->shouldReceive('getParent')->andReturn($this->tracker);

        $this->trigger_field_id_1 = 369;
        $this->trigger_value_id_1 = 852;

        $this->trigger_field_value_1 = new Tracker_FormElement_Field_List_Bind_StaticValue($this->trigger_value_id_1, 'label', 'desc', 0, false);

        $this->trigger_field_1 = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $this->trigger_field_1->shouldReceive('getId')->andReturn($this->trigger_field_id_1);
        $this->trigger_field_1->shouldReceive('getTracker')->andReturn($this->child_tracker_1);
        $this->trigger_field_1->shouldReceive('getAllValues')->andReturns([
                                                                              $this->trigger_field_value_1,
                                                                          ]);

        // field 2
        $child_tracker_2 = Mockery::mock(Tracker::class);
        $child_tracker_2->shouldReceive('getParent')->andReturn($this->tracker);

        $trigger_field_id_2 = 874;
        $trigger_value_id_2 = 147;

        $this->trigger_field_value_2 = new Tracker_FormElement_Field_List_Bind_StaticValue($trigger_value_id_2, 'label', 'desc', 0, false);

        $this->trigger_field_2 = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $this->trigger_field_2->shouldReceive('getId')->andReturn($trigger_field_id_2);
        $this->trigger_field_2->shouldReceive('getTracker')->andReturn($child_tracker_2);
        $this->trigger_field_2->shouldReceive('getAllValues')->andReturns([
                                                                              $this->trigger_field_value_2,
                                                                          ]);

        // Returns the 2 fields
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->with("$this->trigger_field_id_1")->andReturns($this->trigger_field_1);
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->with("$trigger_field_id_2")->andReturns($this->trigger_field_2);

        // Update input
        $json_triggering_field2 = new stdClass();
        $json_triggering_field2->field_id = "$trigger_field_id_2";
        $json_triggering_field2->field_value_id = "$trigger_value_id_2";
        $this->json_input->triggering_fields[] = $json_triggering_field2;
    }

    public function testItHasTwoTriggers(): void
    {
        $this->setupRuleFromRequestTriggerTests();
        $this->setUpTwoTriggers();

        $rule = $this->factory->getRuleFromJson($this->tracker, $this->json_input);

        $this->assertCount(2, $rule->getTriggers());

        $triggering_fields = $rule->getTriggers();
        $rule1 = array_shift($triggering_fields);
        $this->assertEquals($this->trigger_field_1, $rule1->getField());
        $this->assertEquals($this->trigger_field_value_1, $rule1->getValue());

        $rule2 = array_shift($triggering_fields);
        $this->assertEquals($this->trigger_field_2, $rule2->getField());
        $this->assertEquals($this->trigger_field_value_2, $rule2->getValue());
    }

    public function testItDoesATwoWayTransform(): void
    {
        $this->tracker_id = 274;
        $tracker_name     = 'Target Tracker Name';

        $this->target_field_id = 30;
        $this->target_value_id = 250;
        $target_field_value    = new Tracker_FormElement_Field_List_Bind_StaticValue($this->target_value_id, 'Target Value Label', 'desc', 0, false);

        $target_bind_static = new Tracker_FormElement_Field_List_Bind_Static(
            null,
            null,
            [$target_field_value],
            null,
            null
        );
        $tracker_target_field = Mockery::spy(Tracker::class);
        $tracker_target_field->shouldReceive('getId')->andReturn($this->tracker_id);
        $tracker_target_field->shouldReceive('getName')->andReturn($tracker_name);
        $target_field = Mockery::spy(Tracker_FormElement_Field_Selectbox::class);
        $target_field->shouldReceive('getId')->andReturn($this->target_field_id);
        $target_field->shouldReceive('getLabel')->andReturn('Target Field Label');
        $target_field->shouldReceive('getTracker')->andReturn($tracker_target_field);
        $target_field->shouldReceive('getBind')->andReturn($target_bind_static);
        $target_field->shouldReceive('getAllValues')->andReturn([$target_field_value]);
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->with("$this->target_field_id")->andReturns($target_field);

        // field 1
        $this->trigger_field_id_1    = 369;
        $this->trigger_field_value_1 = new Tracker_FormElement_Field_List_Bind_StaticValue(852, 'Triggering Value Label 1', 'desc', 0, false);
        $trigger_bind_static = new Tracker_FormElement_Field_List_Bind_Static(
            null,
            null,
            [$this->trigger_field_value_1],
            null,
            null
        );
        $tracker_field_1 = Mockery::spy(Tracker::class);
        $tracker_field_1->shouldReceive('getId')->andReturn(69);
        $tracker_field_1->shouldReceive('getName')->andReturn('Triggering Tracker 1');
        $tracker_field_1->shouldReceive('getParent')->andReturn($this->tracker);

        $this->trigger_field_1 = Mockery::spy(Tracker_FormElement_Field_Selectbox::class);
        $this->trigger_field_1->shouldReceive('getId')->andReturn($this->trigger_field_id_1);
        $this->trigger_field_1->shouldReceive('getLabel')->andReturn('Triggering Field Label 1');
        $this->trigger_field_1->shouldReceive('getTracker')->andReturn($tracker_field_1);
        $this->trigger_field_1->shouldReceive('getBind')->andReturn($trigger_bind_static);
        $this->trigger_field_1->shouldReceive('getAllValues')->andReturn([$this->trigger_field_value_1]);
        $this->formelement_factory->shouldReceive('getUsedFormElementFieldById')->with("$this->trigger_field_id_1")->andReturns($this->trigger_field_1);

        // Add to input what should be added by get
        $json_input = clone $this->json_input;
        $json_input->id = null;
        $json_input->target->field_label = 'Target Field Label';
        $json_input->target->field_value_label = 'Target Value Label';
        $json_input->target->tracker_name = 'Target Tracker Name';
        $json_input->triggering_fields[0]->field_label = 'Triggering Field Label 1';
        $json_input->triggering_fields[0]->field_value_label = 'Triggering Value Label 1';
        $json_input->triggering_fields[0]->tracker_name = 'Triggering Tracker 1';

        $rule = $this->factory->getRuleFromJson($this->tracker, $this->json_input);
        $json_output = json_decode(json_encode($rule->fetchFormattedForJson()));
        $this->assertEquals($json_output, $json_input);
    }
}
