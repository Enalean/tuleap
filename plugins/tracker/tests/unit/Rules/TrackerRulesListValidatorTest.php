<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Rule;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElementFactory;
use Tracker_Rule_Date_Factory;
use Tracker_Rule_List;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerRulesListValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private TrackerRulesListValidator $tracker_rules_list_validator;

    private Tracker_FormElementFactory&MockObject $formelement_factory;

    private Tracker $tracker;

    private Tracker_FormElement_Field_List&MockObject $source_field;

    private Tracker_FormElement_Field_List&MockObject $target_field;

    private Tracker_FormElement_Field_List_Bind_Static&MockObject $bind;

    private Tracker_FormElement_Field_List_Bind_Static&MockObject $bind_2;

    private Tracker_FormElement_Field_List_Bind_Static&MockObject $bind_3;

    private Tracker_FormElement_Field_List_Bind_Static $bind_4;

    private array $list_rules;

    public function setUp(): void
    {
        $this->formelement_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->tracker             = TrackerTestBuilder::aTracker()->withId(110)->withName('MyTracker')->build();

        $this->bind   = $this->createMock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $this->bind_2 = $this->createMock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $this->bind_3 = $this->createMock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $this->bind_4 = $this->createMock(\Tracker_FormElement_Field_List_Bind_Static::class);

        $this->source_field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $this->source_field->method('getID')->willReturn(123);
        $this->source_field->method('getLabel')->willReturn('aaaaa');
        $this->source_field->method('getBind')->willReturn($this->bind);
        $this->source_field->method('getTracker')->willReturn($this->tracker);

        $this->target_field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $this->target_field->method('getID')->willReturn(789);
        $this->target_field->method('getLabel')->willReturn('bbbbb');
        $this->target_field->method('getBind')->willReturn($this->bind_2);

        $this->bind->method('formatArtifactValue')->willReturn('Champ1');
        $this->bind_2->method('formatArtifactValue')->willReturn('Champ2');
        $this->bind_3->method('formatArtifactValue')->willReturn('Champ3');
        $this->bind_4->method('formatArtifactValue')->willReturn('Champ4');

        $this->source_field->method('setHasErrors')->with(true);
        $this->target_field->method('setHasErrors')->with(true);

        $this->tracker_rules_list_validator = new TrackerRulesListValidator($this->formelement_factory, new \Psr\Log\NullLogger());

        // Fields:
        // 101(A1, A2)
        // 102(B1, B2, B3)
        // 103(C1, C2)
        // 104(D1, D2)
        //
        // Rules:
        // 101(A1) => 102(B1, B3) The resource A1 can be used in rooms B1 and B3
        // 101(A2) => 102(B2, B3) The resource A2 can be used in rooms B2 and B3
        // 102(B1) => 103(C1) The person C1 can access to rooms B1 and B3
        // 102(B2) => 103(C2)     The person C2 can access to room B2 only
        // 102(B3) => 103(C1)     The person C1 can access to rooms B1 and B3
        //
        // Scenarios:
        // S1 => A2, B3, C1, D1 should be valid (C2 can use A2 in B3)
        // S2 => A2, B3, C2, D1 should *not* be valid (C2 cannot access to B3)
        // S3 => (A1, A2), B3, C1, D1 should be valid
        // S4 => (A1, A2), B2, C2, D1 should be valid (even if A1 cannot access to B2)
        // S5 => A1, (B1, B3), C1, D1 should be valid
        // S6 => A1, (B1, B2), C1, D1 should *not* be valid (A1 or C1 cannot access to B2)

        $rule_1 = new Tracker_Rule_List(1, 110, '101', 'A1', '102', 'B1');
        $rule_2 = new Tracker_Rule_List(2, 110, '101', 'A1', '102', 'B3');
        $rule_3 = new Tracker_Rule_List(3, 110, '101', 'A2', '102', 'B2');
        $rule_4 = new Tracker_Rule_List(4, 110, '101', 'A2', '102', 'B3');
        $rule_5 = new Tracker_Rule_List(5, 110, '102', 'B1', '103', 'C1');
        $rule_6 = new Tracker_Rule_List(6, 110, '102', 'B2', '103', 'C2');
        $rule_7 = new Tracker_Rule_List(7, 110, '102', 'B3', '103', 'C1');

        $this->list_rules = [$rule_1, $rule_2, $rule_3, $rule_4, $rule_5, $rule_6, $rule_7];

        $field_1 = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $field_1->method('getBind')->willReturn($this->bind);
        $field_1->method('getID')->willReturn('101');
        $field_1->method('getLabel')->willReturn('f_1');
        $field_1->method('getAllValues')->willReturn(null);
        $field_1->method('setHasErrors')->with(true);
        $field_1->method('getTracker')->willReturn($this->tracker);

        $field_2 = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $field_2->method('getBind')->willReturn($this->bind_2);
        $field_2->method('getID')->willReturn('102');
        $field_2->method('getLabel')->willReturn('f_2');
        $field_2->method('getAllValues')->willReturn(null);
        $field_2->method('setHasErrors')->with(true);
        $field_2->method('getTracker')->willReturn($this->tracker);

        $field_3 = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $field_3->method('getBind')->willReturn($this->bind_3);
        $field_3->method('getID')->willReturn('103');
        $field_3->method('getLabel')->willReturn('f_3');
        $field_3->method('getAllValues')->willReturn(null);
        $field_3->method('setHasErrors')->with(true);

        $field_4 = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $field_4->method('getBind')->willReturn($this->bind_4);
        $field_4->method('getID')->willReturn('104');
        $field_4->method('getLabel')->willReturn('f_4');
        $field_4->method('getAllValues')->willReturn(null);
        $field_4->method('setHasErrors')->with(true);

        $this->formelement_factory
            ->method('getFormElementListById')
            ->willReturnCallback(
                fn (int $id) => match ($id) {
                    101 => $field_1,
                    102 => $field_2,
                    103 => $field_3,
                    104 => $field_4,
                    123 => $this->source_field,
                    789 => $this->target_field,
                    666 => null,
                }
            );

        $rule_date_factory = $this->createMock(Tracker_Rule_Date_Factory::class);
        $rule_date_factory->method('searchByTrackerId')->willReturn([]);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['Response']);
    }

    public function testValidateListRulesReturnTrueIfValuesAreValid(): void
    {
        $value_field_list  = [
            123 => 456,
            789 => 101,
        ];
        $tracker_rule_list = new \Tracker_Rule_List();
        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetValue(101)
            ->setTargetFieldId(789)
            ->setTrackerId(110)
            ->setId(5);

        $this->assertTrue($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    public function testValidateListRulesReturnErrorIfTargetValuesAreDifferent(): void
    {
        $GLOBALS['Response']->method('addFeedback')->with('error', 'Global rules are not respected in tracker MyTracker (#110) - aaaaa(Champ1) -> bbbbb(Champ2)');

        $value_field_list  = [
            123 => 456,
            789 => 101,
        ];
        $tracker_rule_list = new \Tracker_Rule_List();

        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetFieldId(789)
            ->setTargetValue(102)
            ->setTrackerId(110)
            ->setId(5);

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    /**
     * This tests a case where a source value does not have any target value in the dependencies.
     * This leads to a rule object that does not exist for this source
     *
     * In this test, we are in the artifact update case, which means that field 789 is added in the submitted data with an empty value
     */
    public function testValidateListRulesReturnErrorIfTargetValueIsNotProvided(): void
    {
        $GLOBALS['Response']->method('addFeedback')->with('error', 'Global rules are not respected in tracker MyTracker (#110) - aaaaa(Champ1) -> bbbbb()');

        $value_field_list = [
            123 => 457,
            789 => [],
        ];

        $tracker_rule_list = new \Tracker_Rule_List();
        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetFieldId(789)
            ->setTargetValue(101)
            ->setTrackerId(110)
            ->setId(5);

        self::assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    /**
     * This tests a case where a source value does not have any target value in the dependencies.
     * This leads to a rule object that does not exist for this source
     *
     * In this test, we are in the artifact creation case, which means that field 789 is not added at all in the submitted data
     */
    public function testValidateListRulesReturnErrorIfTargetValueIsNotProvidedAtArtifactCreation(): void
    {
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', 'Global rules are not respected in tracker MyTracker (#110) - aaaaa(Champ1) -> bbbbb()');

        $value_field_list = [
            123 => 457,
        ];

        $tracker_rule_list = new \Tracker_Rule_List();
        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetFieldId(789)
            ->setTargetValue(101)
            ->setTrackerId(110)
            ->setId(5);

        self::assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    public function testValidateListRulesReturnErrorIfSourceValuesAreDifferent(): void
    {
        $GLOBALS['Response']->method('addFeedback')->with('error', 'Global rules are not respected in tracker MyTracker (#110) - aaaaa(Champ1) -> bbbbb(Champ2)');

        $value_field_list = [
            123 => 456,
            789 => 101,
        ];

        $tracker_rule_list = new \Tracker_Rule_List();
        $tracker_rule_list->setSourceValue(4560)
            ->setSourceFieldId(123)
            ->setTargetValue(101)
            ->setTargetFieldId(789)
            ->setTrackerId(110)
            ->setId(5);

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    public function testValidateListRulesReturnErrorIfTrackersIdsAreDifferent(): void
    {
        $GLOBALS['Response']->method('addFeedback')->with('error', 'Global rules are not respected in tracker MyTracker (#110) - aaaaa(Champ1) -> bbbbb(Champ2)');

        $value_field_list  = [
            123 => 456,
            789 => 101,
        ];
        $tracker_rule_list = new \Tracker_Rule_List();
        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetValue(101)
            ->setTargetFieldId(789)
            ->setTrackerId(11)
            ->setId(5);

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    public function testValidateListRulesReturnErrorIfAFieldIsEmpty(): void
    {
        $this->bind->method('formatArtifactValue')->willReturn('Champ1');
        $this->bind_2->method('formatArtifactValue')->willReturn('');

        $value_field_list  = [
            123 => 456,
            789 => '',
        ];
        $tracker_rule_list = new \Tracker_Rule_List();

        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetValue(101)
            ->setTargetFieldId(789)
            ->setTrackerId(110)
            ->setId(5);

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    public function testValidateListRulesValidWithEmptyTargetsValue(): void
    {
        $this->bind->method('formatArtifactValue')->willReturn('Champ1');
        $this->bind_2->method('formatArtifactValue')->willReturn('');

        $value_field_list  = [
            123 => 456,
            789 => 100,
        ];
        $tracker_rule_list = new \Tracker_Rule_List();

        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetValue(100)
            ->setTargetFieldId(789)
            ->setTrackerId(110)
            ->setId(5);

        $this->assertTrue($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    public function testValidateListRulesReturnErrorIfAFieldIsNoneValue(): void
    {
        $this->bind->method('formatArtifactValue')->willReturn('Champ1');
        $this->bind_2->method('formatArtifactValue')->willReturn('');

        $value_field_list = [
            123 => 456,
            789 => 100,
        ];

        $tracker_rule_list = new \Tracker_Rule_List();

        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetValue(101)
            ->setTargetFieldId(789)
            ->setTrackerId(110)
            ->setId(5);

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    public function testS1ValidateListRulesReturnTrueIfRulesAreRespected()
    {
        $GLOBALS['Response']->expects($this->never())->method('addFeedback');
        $value_field_list = [
            '101' => 'A2',
            '102' => 'B3',
            '103' => 'C1',
            '104' => 'D1',
        ];
        $this->assertTrue($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, $this->list_rules));
    }

    public function testS2ValidateListRulesReturnFalseAndErrorIfC3TryToAccessToB3(): void
    {
        $GLOBALS['Response']->method('addFeedback')->with('error', 'Global rules are not respected in tracker MyTracker (#110) - f_2(Champ2) -> f_3(Champ3)');

        $value_field_list = [
            '101' => 'A2',
            '102' => 'B3',
            '103' => 'C2', //C2 cannot access to B3 !
            '104' => 'D1',
        ];

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, $this->list_rules));
    }

    public function testS3ValidateListRulesReturnTrueIfRulesAreRespected()
    {
        $GLOBALS['Response']->expects($this->never())->method('addFeedback');
        $value_field_list = [
            '101' => ['A1', 'A2'],
            '102' => 'B3',
            '103' => 'C1',
            '104' => 'D1',
        ];
        $this->assertTrue($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, $this->list_rules));
    }

    public function testS4ValidateListRulesReturnTrueIfRulesAreRespected()
    {
        $GLOBALS['Response']->expects($this->never())->method('addFeedback');
        $value_field_list = [
            '101' => ['A1', 'A2'],
            '102' => 'B2',
            '103' => 'C2',
            '104' => 'D1',
        ];
        $this->assertTrue($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, $this->list_rules));
    }

    public function testS5ValidateListRulesReturnTrueIfRulesAreRespected()
    {
        $GLOBALS['Response']->expects($this->never())->method('addFeedback');

        $value_field_list = [
            '101' => 'A1',
            '102' => ['B1', 'B3'],
            '103' => 'C1',
            '104' => 'D1',
        ];

        $this->assertTrue($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, $this->list_rules));
    }

    public function testS6ValidateListRulesReturnFalseAndErrorIfA1TryToAccessToB2(): void
    {
        $GLOBALS['Response']->method('addFeedback')->with('error', 'Global rules are not respected in tracker MyTracker (#110) - f_1(Champ1) -> f_2(Champ2)');
        $value_field_list = [
            '101' => 'A1',
            '102' => ['B1', 'B2'], //A1 cannot access to B2 !
            '103' => 'C1',
            '104' => 'D1',
        ];
        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, $this->list_rules));
    }
}
