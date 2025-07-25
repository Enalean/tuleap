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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_Workflow_Trigger_RulesFactoryTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    private int $tracker_id = 274;
    private Tracker $tracker;
    private stdClass $json_input;
    private Tracker_Workflow_Trigger_TriggerValidator&MockObject $validator;
    private int $target_value_id = 250;
    private Tracker_FormElement_Field_List_Bind_StaticValue $target_field_value;
    private Tracker_FormElement_Field_Selectbox&MockObject $target_field;
    private int $target_field_id  = 30;
    private int $trigger_field_id = 369;
    private int $trigger_value_id = 852;

    protected function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build();

        $this->validator = $this->createMock(Tracker_Workflow_Trigger_TriggerValidator::class);
        $this->validator->method('validateJsonFormat');

        $this->json_input = json_decode(file_get_contents(__DIR__ . '/_fixtures/add_rule.json'));

        $this->target_field_value = ListStaticValueBuilder::aStaticValue('label')->withId($this->target_value_id)->build();
        $this->target_field       = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $this->target_field->method('getId')->willReturn($this->target_field_id);
        $this->target_field->method('getTracker')->willReturn($this->tracker);
        $this->target_field->method('getAllValues')->willReturn([
            ListStaticValueBuilder::aStaticValue('label')->withId(9998)->build(),
            $this->target_field_value,
            ListStaticValueBuilder::aStaticValue('label')->withId(9999)->build(),
        ]);
    }

    public function testItFetchesFieldFromFormElementFactory(): void
    {
        $factory = new Tracker_Workflow_Trigger_RulesFactory(
            RetrieveUsedFieldsStub::withFields($this->target_field),
            $this->validator,
        );

        self::assertSame(
            $this->target_field,
            $factory->getRuleFromJson($this->tracker, $this->json_input)->getTarget()->getField()
        );
    }

    public function testItRaisesAnExceptionIfFieldIsInvalid(): void
    {
        $factory                            = new Tracker_Workflow_Trigger_RulesFactory(
            RetrieveUsedFieldsStub::withFields($this->target_field),
            $this->validator,
        );
        $this->json_input->target->field_id = '40';

        $this->expectException(Tracker_FormElement_InvalidFieldException::class);

        $factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    public function testItRaisesAnExceptionWhenFieldDoesntBelongToTracker(): void
    {
        $field   = IntegerFieldBuilder::anIntField(30)
            ->inTracker(TrackerTestBuilder::aTracker()->withId(37)->build())
            ->build();
        $factory = new Tracker_Workflow_Trigger_RulesFactory(
            RetrieveUsedFieldsStub::withFields($field),
            $this->validator,
        );

        $this->expectException(Tracker_FormElement_InvalidFieldException::class);

        $factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    public function testItBuildsTheRuleWithTargetFieldAndValue(): void
    {
        $factory = new Tracker_Workflow_Trigger_RulesFactory(
            RetrieveUsedFieldsStub::withFields($this->target_field),
            $this->validator,
        );

        $rule = $factory->getRuleFromJson($this->tracker, $this->json_input);
        self::assertEquals($this->target_field, $rule->getTarget()->getField());
        self::assertEquals($this->target_field_value, $rule->getTarget()->getValue());
    }

    public function testItRaisesAnExceptionWhenTargetValueDoesntBelongToField(): void
    {
        $target_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $target_field->method('getId')->willReturn(30);
        $target_field->method('getTracker')->willReturn($this->tracker);
        $target_field->method('getAllValues')->willReturn([]);
        $factory = new Tracker_Workflow_Trigger_RulesFactory(
            RetrieveUsedFieldsStub::withFields($target_field),
            $this->validator,
        );

        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);

        $factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    public function testItBuildsTheRuleWithCondition(): void
    {
        $this->target_field->method('getAllValues')->willReturn([$this->target_field_value]);
        $factory = new Tracker_Workflow_Trigger_RulesFactory(
            RetrieveUsedFieldsStub::withFields($this->target_field),
            $this->validator,
        );
        $rule    = $factory->getRuleFromJson($this->tracker, $this->json_input);
        self::assertEquals(Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF, $rule->getCondition());
    }

    private function setupRuleFromRequestTriggerTests(): void
    {
        $this->target_field_value = ListStaticValueBuilder::aStaticValue('label')->withId($this->target_value_id)->build();

        $this->target_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $this->target_field->method('getId')->willReturn($this->target_field_id);
        $this->target_field->method('getTracker')->willReturn($this->tracker);
        $this->target_field->method('getAllValues')->willReturn([
            $this->target_field_value,
        ]);
    }

    public function testItHasATrigger(): void
    {
        $this->setupRuleFromRequestTriggerTests();
        $child_tracker = TrackerTestBuilder::aTracker()->withParent($this->tracker)->build();

        $trigger_field_value = ListStaticValueBuilder::aStaticValue('label')->withId($this->trigger_value_id)->build();

        $trigger_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $trigger_field->method('getId')->willReturn($this->trigger_field_id);
        $trigger_field->method('getTracker')->willReturn($child_tracker);
        $trigger_field->method('getAllValues')->willReturn([
            $trigger_field_value,
        ]);

        $factory  = new Tracker_Workflow_Trigger_RulesFactory(
            RetrieveUsedFieldsStub::withFields($this->target_field, $trigger_field),
            $this->validator,
        );
        $rule     = $factory->getRuleFromJson($this->tracker, $this->json_input);
        $triggers = $rule->getTriggers();
        $this->assertCount(1, $triggers);
        $rule1 = array_pop($triggers);
        self::assertEquals($trigger_field, $rule1->getField());
        self::assertEquals($trigger_field_value, $rule1->getValue());
    }

    public function testItRaisesAnErrorIfTriggerTrackerDoesntBelongToChildren(): void
    {
        $this->setupRuleFromRequestTriggerTests();
        $not_child_tracker = TrackerTestBuilder::aTracker()->withParent(null)->build();

        $trigger_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $trigger_field->method('getId')->willReturn($this->trigger_field_id);
        $trigger_field->method('getTracker')->willReturn($not_child_tracker);

        $factory = new Tracker_Workflow_Trigger_RulesFactory(
            RetrieveUsedFieldsStub::withFields($this->target_field, $trigger_field),
            $this->validator,
        );

        $this->expectException(Tracker_FormElement_InvalidFieldException::class);

        $factory->getRuleFromJson($this->tracker, $this->json_input);
    }

    public function testItHasTwoTriggers(): void
    {
        $this->setupRuleFromRequestTriggerTests();
        // field 1
        $child_tracker_1 = TrackerTestBuilder::aTracker()->withId(101)->withParent($this->tracker)->build();

        $trigger_field_id_1 = 369;
        $trigger_value_id_1 = 852;

        $trigger_field_value_1 = ListStaticValueBuilder::aStaticValue('label')->withId($trigger_value_id_1)->build();

        $trigger_field_1 = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $trigger_field_1->method('getId')->willReturn($trigger_field_id_1);
        $trigger_field_1->method('getTracker')->willReturn($child_tracker_1);
        $trigger_field_1->method('getAllValues')->willReturn([
            $trigger_field_value_1,
        ]);

        // field 2
        $child_tracker_2 = TrackerTestBuilder::aTracker()->withId(102)->withParent($this->tracker)->build();

        $trigger_field_id_2 = 874;
        $trigger_value_id_2 = 147;

        $trigger_field_value_2 = ListStaticValueBuilder::aStaticValue('label')->withId($trigger_value_id_2)->build();

        $trigger_field_2 = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $trigger_field_2->method('getId')->willReturn($trigger_field_id_2);
        $trigger_field_2->method('getTracker')->willReturn($child_tracker_2);
        $trigger_field_2->method('getAllValues')->willReturn([
            $trigger_field_value_2,
        ]);

        // Update input
        $json_triggering_field2                 = new stdClass();
        $json_triggering_field2->field_id       = "$trigger_field_id_2";
        $json_triggering_field2->field_value_id = "$trigger_value_id_2";
        $this->json_input->triggering_fields[]  = $json_triggering_field2;

        $factory = new Tracker_Workflow_Trigger_RulesFactory(
            RetrieveUsedFieldsStub::withFields($this->target_field, $trigger_field_1, $trigger_field_2),
            $this->validator,
        );
        $rule    = $factory->getRuleFromJson($this->tracker, $this->json_input);

        $this->assertCount(2, $rule->getTriggers());

        $triggering_fields = $rule->getTriggers();
        $rule1             = array_shift($triggering_fields);
        self::assertEquals($trigger_field_1, $rule1->getField());
        self::assertEquals($trigger_field_value_1, $rule1->getValue());

        $rule2 = array_shift($triggering_fields);
        self::assertEquals($trigger_field_2, $rule2->getField());
        self::assertEquals($trigger_field_value_2, $rule2->getValue());
    }

    public function testItDoesATwoWayTransform(): void
    {
        $target_field_value = ListStaticValueBuilder::aStaticValue('Target Value Label')->withId($this->target_value_id)->build();

        $target_bind_static = new Tracker_FormElement_Field_List_Bind_Static(
            new DatabaseUUIDV7Factory(),
            null,
            null,
            [$target_field_value],
            null,
            null
        );
        $tracker_target     = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->withName('Target Tracker Name')->build();
        $target_field       = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $target_field->method('getId')->willReturn($this->target_field_id);
        $target_field->method('getLabel')->willReturn('Target Field Label');
        $target_field->method('getTracker')->willReturn($tracker_target);
        $target_field->method('getBind')->willReturn($target_bind_static);
        $target_field->method('getAllValues')->willReturn([$target_field_value]);

        $trigger_field_value_1 = ListStaticValueBuilder::aStaticValue('Triggering Value Label')->withId(852)->build();
        $trigger_bind_static   = new Tracker_FormElement_Field_List_Bind_Static(
            new DatabaseUUIDV7Factory(),
            null,
            null,
            [$trigger_field_value_1],
            null,
            null
        );
        $trigger_tracker       = TrackerTestBuilder::aTracker()->withId(69)->withName('Triggering Tracker')->withParent($this->tracker)->build();

        $trigger_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $trigger_field->method('getId')->willReturn($this->trigger_field_id);
        $trigger_field->method('getLabel')->willReturn('Triggering Field Label');
        $trigger_field->method('getTracker')->willReturn($trigger_tracker);
        $trigger_field->method('getBind')->willReturn($trigger_bind_static);
        $trigger_field->method('getAllValues')->willReturn([$trigger_field_value_1]);

        // Add to input what should be added by get
        $json_input                                          = clone $this->json_input;
        $json_input->id                                      = null;
        $json_input->target->field_label                     = 'Target Field Label';
        $json_input->target->field_value_label               = 'Target Value Label';
        $json_input->target->tracker_name                    = 'Target Tracker Name';
        $json_input->triggering_fields[0]->field_label       = 'Triggering Field Label';
        $json_input->triggering_fields[0]->field_value_label = 'Triggering Value Label';
        $json_input->triggering_fields[0]->tracker_name      = 'Triggering Tracker';

        $factory     = new Tracker_Workflow_Trigger_RulesFactory(
            RetrieveUsedFieldsStub::withFields($target_field, $trigger_field),
            $this->validator,
        );
        $rule        = $factory->getRuleFromJson($this->tracker, $this->json_input);
        $json_output = json_decode(json_encode($rule->fetchFormattedForJson()));
        self::assertEquals($json_output, $json_input);
    }
}
