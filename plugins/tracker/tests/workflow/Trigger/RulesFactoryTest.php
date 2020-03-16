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

require_once __DIR__ . '/../../bootstrap.php';

class Tracker_Workflow_Trigger_RulesFactory_getRuleFromRequest_Test extends TuleapTestCase
{

    protected $tracker_id;
    protected $tracker;
    protected $formelement_factory;
    protected $factory;
    protected $json_input;
    protected $validator;

    public function setUp()
    {
        parent::setUp();
        $this->tracker_id = 274;
        $this->tracker = aTracker()->withId($this->tracker_id)->build();
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $rules_manager = mock('Tracker_Workflow_Trigger_RulesManager');
        $this->validator = mock('Tracker_Workflow_Trigger_TriggerValidator');
        $this->factory = new Tracker_Workflow_Trigger_RulesFactory($this->formelement_factory, $this->validator);
        $this->json_input = json_decode(file_get_contents(dirname(__FILE__) . '/_fixtures/add_rule.json'));
    }
}

class Tracker_Workflow_Trigger_RulesFactory_getRuleFromRequest_TargetTest extends Tracker_Workflow_Trigger_RulesFactory_getRuleFromRequest_Test
{

    public function setUp()
    {
        parent::setUp();
        $this->tracker_id = 274;
        $this->tracker = aTracker()->withId($this->tracker_id)->build();
        $this->target_value_id = 250;
        $this->target_field_value = aBindStaticValue()->withId($this->target_value_id)->build();
        $this->target_field = aMockField()->withTracker($this->tracker)->build();
        stub($this->target_field)->getAllValues()->returns(
            array(
                aBindStaticValue()->withId(9998)->build(),
                $this->target_field_value,
                aBindStaticValue()->withId(9999)->build(),
            )
        );
    }

    public function itFetchesFieldFromFormElementFactory()
    {
        expect($this->formelement_factory)->getUsedFormElementFieldById()->count(2);
        expect($this->formelement_factory)->getUsedFormElementFieldById('30')->at(0);
        stub($this->formelement_factory)->getUsedFormElementFieldById('30')->returns($this->target_field);

        $this->factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    public function itRaisesAnExceptionIfFieldIsInvalid()
    {
        $this->json_input->target->field_id = '40';

        $this->expectException('Tracker_FormElement_InvalidFieldException');
        $this->factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    public function itRaisesAnExceptionWhenFieldDoesntBelongToTracker()
    {
        $tracker = aTracker()->withId(37)->build();
        stub($this->formelement_factory)->getUsedFormElementFieldById()->returns(aMockField()->withTracker($tracker)->build());

        $this->expectException('Tracker_FormElement_InvalidFieldException');
        $this->factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    public function itBuildsTheRuleWithTargetFieldAndValue()
    {
        stub($this->formelement_factory)->getUsedFormElementFieldById('30')->returns($this->target_field);

        $rule = $this->factory->getRuleFromJson($this->tracker, $this->json_input);
        $this->assertEqual($rule->getTarget()->getField(), $this->target_field);
        $this->assertEqual($rule->getTarget()->getValue(), $this->target_field_value);
    }

    public function itRaisesAnExceptionWhenTargetValueDoesntBelongToField()
    {
        $target_field = aMockField()->withTracker($this->tracker)->build();
        stub($target_field)->getAllValues()->returns(array());
        stub($this->formelement_factory)->getUsedFormElementFieldById()->returns($target_field);

        $this->expectException('Tracker_FormElement_InvalidFieldValueException');

        $this->factory->getRuleFromJson($this->tracker, $this->json_input);
    }
}

class Tracker_Workflow_Trigger_RulesFactory_getRuleFromRequest_ConditionTest extends Tracker_Workflow_Trigger_RulesFactory_getRuleFromRequest_Test
{

    public function setUp()
    {
        parent::setUp();
        $this->tracker_id = 274;
        $this->tracker = aTracker()->withId($this->tracker_id)->build();
        $this->target_value_id = 250;
        $this->target_field_value = aBindStaticValue()->withId($this->target_value_id)->build();
        $this->target_field = aMockField()->withTracker($this->tracker)->build();
        stub($this->target_field)->getAllValues()->returns(
            array(
                $this->target_field_value,
            )
        );
        stub($this->formelement_factory)->getUsedFormElementFieldById('30')->returns($this->target_field);
    }

    public function itBuildsTheRuleWithCondition()
    {
        $rule = $this->factory->getRuleFromJson($this->tracker, $this->json_input);
        $this->assertEqual($rule->getCondition(), Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF);
    }
}

class Tracker_Workflow_Trigger_RulesFactory_getRuleFromRequest_TriggerTest extends Tracker_Workflow_Trigger_RulesFactory_getRuleFromRequest_Test
{

    public function setUp()
    {
        parent::setUp();
        $this->target_field_id = 30;
        $this->tracker_id = 274;
        $this->tracker = aTracker()->withId($this->tracker_id)->build();
        $this->target_value_id = 250;
        $this->target_field_value = aBindStaticValue()->withId($this->target_value_id)->build();
        $this->target_field = aMockField()->withId($this->target_field_id)->withTracker($this->tracker)->build();
        stub($this->target_field)->getAllValues()->returns(
            array(
                $this->target_field_value,
            )
        );
        stub($this->formelement_factory)->getUsedFormElementFieldById("$this->target_field_id")->returns($this->target_field);
    }

    public function itHasATrigger()
    {
        $this->child_tracker = aTracker()->withParent($this->tracker)->build();

        $this->trigger_field_id = 369;
        $this->trigger_value_id = 852;

        $this->trigger_field_value = aBindStaticValue()->withId($this->trigger_value_id)->build();

        $this->trigger_field = aMockField()->withTracker($this->child_tracker)->build();
        stub($this->trigger_field)->getAllValues()->returns(
            array(
                $this->trigger_field_value,
            )
        );

        stub($this->formelement_factory)->getUsedFormElementFieldById("$this->trigger_field_id")->returns($this->trigger_field);

        $rule     = $this->factory->getRuleFromJson($this->tracker, $this->json_input);
        $triggers = $rule->getTriggers();
        $this->assertCount($triggers, 1);
        $rule1 = array_pop($triggers);
        $this->assertEqual($rule1->getField(), $this->trigger_field);
        $this->assertEqual($rule1->getValue(), $this->trigger_field_value);
    }

    public function itRaisesAnErrorIfTriggerTrackerDoesntBelongToChildren()
    {
        $this->not_child_tracker = aTracker()->withParent(null)->build();

        $this->trigger_field_id = 369;
        $this->trigger_value_id = 852;

        $this->trigger_field = aMockField()->withTracker($this->not_child_tracker)->build();

        stub($this->formelement_factory)->getUsedFormElementFieldById("$this->trigger_field_id")->returns($this->trigger_field);

        $this->expectException('Tracker_FormElement_InvalidFieldException');

        $this->factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    private function setUpTwoTriggers()
    {
        // field 1
        $this->child_tracker_1 = aTracker()->withParent($this->tracker)->build();

        $this->trigger_field_id_1 = 369;
        $this->trigger_value_id_1 = 852;

        $this->trigger_field_value_1 = aBindStaticValue()->withId($this->trigger_value_id_1)->build();

        $this->trigger_field_1 = aMockField()->withId($this->trigger_field_id_1)->withTracker($this->child_tracker_1)->build();
        stub($this->trigger_field_1)->getAllValues()->returns(
            array(
                $this->trigger_field_value_1,
            )
        );

        // field 2
        $this->child_tracker_2 = aTracker()->withParent($this->tracker)->build();

        $this->trigger_field_id_2 = 874;
        $this->trigger_value_id_2 = 147;

        $this->trigger_field_value_2 = aBindStaticValue()->withId($this->trigger_value_id_2)->build();

        $this->trigger_field_2 = aMockField()->withId($this->trigger_field_id_2)->withTracker($this->child_tracker_2)->build();
        stub($this->trigger_field_2)->getAllValues()->returns(
            array(
                $this->trigger_field_value_2,
            )
        );

        // Returns the 2 fields
        stub($this->formelement_factory)->getUsedFormElementFieldById("$this->trigger_field_id_1")->returns($this->trigger_field_1);
        stub($this->formelement_factory)->getUsedFormElementFieldById("$this->trigger_field_id_2")->returns($this->trigger_field_2);

        // Update input
        $json_triggering_field2 = new stdClass();
        $json_triggering_field2->field_id = "$this->trigger_field_id_2";
        $json_triggering_field2->field_value_id = "$this->trigger_value_id_2";
        $this->json_input->triggering_fields[] = $json_triggering_field2;
    }

    public function itHasTwoTriggers()
    {
        $this->setUpTwoTriggers();

        $rule = $this->factory->getRuleFromJson($this->tracker, $this->json_input);

        $this->assertCount($rule->getTriggers(), 2);

        $triggering_fields = $rule->getTriggers();
        $rule1 = array_shift($triggering_fields);
        $this->assertEqual($rule1->getField(), $this->trigger_field_1);
        $this->assertEqual($rule1->getValue(), $this->trigger_field_value_1);

        $rule2 = array_shift($triggering_fields);
        $this->assertEqual($rule2->getField(), $this->trigger_field_2);
        $this->assertEqual($rule2->getValue(), $this->trigger_field_value_2);
    }
}

class Tracker_Workflow_Trigger_RulesFactory_JsonInputOutput_TriggerTest extends Tracker_Workflow_Trigger_RulesFactory_getRuleFromRequest_Test
{

    public function setUp()
    {
        parent::setUp();
        $this->tracker_id = 274;
        $this->tracker_name = 'Target Tracker Name';

        $this->target_field_id = 30;
        $this->target_value_id = 250;
        $target_field_value = aBindStaticValue()
            ->withId($this->target_value_id)
            ->withLabel('Target Value Label')
            ->build();

        $target_bind_static = new Tracker_FormElement_Field_List_Bind_Static(
            null,
            null,
            [$target_field_value],
            null,
            null
        );
        $target_field = aSelectBoxField()
            ->withId($this->target_field_id)
            ->withLabel('Target Field Label')
            ->withTracker(
                aTracker()
                    ->withId($this->tracker_id)
                    ->withName($this->tracker_name)
                    ->build()
            )
            ->withBind($target_bind_static)
            ->build();
        stub($this->formelement_factory)->getUsedFormElementFieldById("$this->target_field_id")->returns($target_field);

        // field 1
        $this->trigger_field_id_1 = 369;
        $this->trigger_field_value_1 = aBindStaticValue()
            ->withId(852)
            ->withLabel('Triggering Value Label 1')
            ->build();
        $trigger_bind_static = new Tracker_FormElement_Field_List_Bind_Static(
            null,
            null,
            [$this->trigger_field_value_1],
            null,
            null
        );
        $this->trigger_field_1 = aSelectBoxField()
            ->withId($this->trigger_field_id_1)
            ->withLabel('Triggering Field Label 1')
            ->withTracker(
                aTracker()
                    ->withId(69)
                    ->withName('Triggering Tracker 1')
                    ->withParent($this->tracker)
                    ->build()
            )
            ->withBind($trigger_bind_static)
            ->build();
        stub($this->formelement_factory)->getUsedFormElementFieldById("$this->trigger_field_id_1")->returns($this->trigger_field_1);
    }

    public function itDoesATwoWayTransform()
    {
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
        $this->assertEqual($json_output, $json_input);
    }
}
