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

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_Selectbox;
use Tracker_Rule_Date_Factory;
use Tracker_Rule_List;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;

class TrackerRulesListValidatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    /**
     * @var TrackerRulesListValidator
     */
    private $tracker_rules_list_validator;

    /**
     * @var Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var Mockery\MockInterface|\Tracker
     */
    private $tracker;

    /**
     * @var Mockery\MockInterface|\Tracker_FormElement_Field_List
     */
    private $source_field;

    /**
     * @var Mockery\MockInterface|\Tracker_FormElement_Field_List
     */
    private $target_field;

    /**
     * @var Mockery\MockInterface|\Tracker_FormElement_Field_List_Bind_Static
     */
    private $bind;

    /**
     * @var Mockery\MockInterface|\Tracker_FormElement_Field_List_Bind_Static
     */
    private $bind_2;

    /**
     * @var Mockery\MockInterface|\Tracker_FormElement_Field_List_Bind_Static
     */
    private $bind_3;

    /**
     * @var Mockery\MockInterface|\Tracker_FormElement_Field_List_Bind_Static
     */
    private $bind_4;

    /**
     * @var array
     */
    private $list_rules;

    public function setUp(): void
    {
        $this->formelement_factory = \Mockery::mock(\Tracker_FormElementFactory::class);
        $this->tracker             = \Mockery::mock(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(110);

        $this->bind   = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $this->bind_2 = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $this->bind_3 = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $this->bind_4 = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);

        $this->source_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $this->source_field->shouldReceive('getID')->andReturns(123);
        $this->source_field->shouldReceive('getLabel')->andReturns('aaaaa');
        $this->source_field->shouldReceive('getBind')->andReturns($this->bind);

        $this->target_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $this->target_field->shouldReceive('getID')->andReturns(789);
        $this->target_field->shouldReceive('getLabel')->andReturns('bbbbb');
        $this->target_field->shouldReceive('getBind')->andReturns($this->bind_2);

        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([123])->andReturns($this->source_field);
        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([789])->andReturns($this->target_field);
        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([666])->andReturns(null);

        $this->bind->shouldReceive("formatArtifactValue")->andReturns('Champ1');
        $this->bind_2->shouldReceive("formatArtifactValue")->andReturns('Champ2');
        $this->bind_3->shouldReceive("formatArtifactValue")->andReturns('Champ3');
        $this->bind_4->shouldReceive("formatArtifactValue")->andReturns('Champ4');

        $this->source_field->shouldReceive('setHasErrors')->withArgs([true]);
        $this->target_field->shouldReceive('setHasErrors')->withArgs([true]);

        $this->tracker_rules_list_validator = new TrackerRulesListValidator($this->formelement_factory);

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

        $field_1 = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $field_1->shouldReceive('getBind')->andReturn($this->bind);
        $field_1->shouldReceive('getID')->andReturn('101');
        $field_1->shouldReceive('getLabel')->andReturn('f_1');
        $field_1->shouldReceive('getAllValues')->andReturn(null);
        $field_1->shouldReceive('setHasErrors')->withArgs([true]);

        $field_2 = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $field_2->shouldReceive('getBind')->andReturn($this->bind_2);
        $field_2->shouldReceive('getID')->andReturn('102');
        $field_2->shouldReceive('getLabel')->andReturn('f_2');
        $field_2->shouldReceive('getAllValues')->andReturn(null);
        $field_2->shouldReceive('setHasErrors')->withArgs([true]);

        $field_3 = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $field_3->shouldReceive('getBind')->andReturn($this->bind_3);
        $field_3->shouldReceive('getID')->andReturn('103');
        $field_3->shouldReceive('getLabel')->andReturn('f_3');
        $field_3->shouldReceive('getAllValues')->andReturn(null);
        $field_3->shouldReceive('setHasErrors')->withArgs([true]);

        $field_4 = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $field_4->shouldReceive('getBind')->andReturn($this->bind_4);
        $field_4->shouldReceive('getID')->andReturn('104');
        $field_4->shouldReceive('getLabel')->andReturn('f_4');
        $field_4->shouldReceive('getAllValues')->andReturn(null);
        $field_4->shouldReceive('setHasErrors')->withArgs([true]);

        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs(['101'])->andReturn($field_1);
        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs(['102'])->andReturn($field_2);
        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs(['103'])->andReturn($field_3);
        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs(['104'])->andReturn($field_4);

        $rule_date_factory = Mockery::mock(Tracker_Rule_Date_Factory::class);
        $rule_date_factory->shouldReceive('searchByTrackerId')->andReturn([]);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['Response']);
    }

    public function testValidateListRulesReturnTrueIfValuesAreValid(): void
    {
        $value_field_list  = [
            123 => 456,
            789 => 101
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
        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', 'aaaaa(Champ1) -> bbbbb(Champ2)']);

        $value_field_list  = [
            123 => 456,
            789 => 101
        ];
        $tracker_rule_list = new \Tracker_Rule_List();

        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetFieldId(789)
            ->setTrackerId(110)
            ->setId(5);

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }


    public function testValidateListRulesReturnErrorIfSourceValuesAreDifferent(): void
    {
        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', 'aaaaa(Champ1) -> bbbbb(Champ2)']);

        $value_field_list = [
            123 => 456,
            789 => 101
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
        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', 'aaaaa(Champ1) -> bbbbb(Champ2)']);

        $value_field_list  = [
            123 => 456,
            789 => 101
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
        $this->bind->shouldReceive("formatArtifactValue")->andReturns('Champ1');
        $this->bind_2->shouldReceive("formatArtifactValue")->andReturns('');

        $value_field_list  = [
            123 => 456,
            789 => ''
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
        $this->bind->shouldReceive("formatArtifactValue")->andReturns('Champ1');
        $this->bind_2->shouldReceive("formatArtifactValue")->andReturns('');

        $value_field_list  = [
            123 => 456,
            789 => 100
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
        $this->bind->shouldReceive("formatArtifactValue")->andReturns('Champ1');
        $this->bind_2->shouldReceive("formatArtifactValue")->andReturns('');

        $value_field_list = [
            123 => 456,
            789 => 100
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
        $GLOBALS['Response']->shouldNotReceive('addFeedback');
        $value_field_list = [
            '101' => 'A2',
            '102' => 'B3',
            '103' => 'C1',
            '104' => 'D1'
        ];
        $this->assertTrue($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, $this->list_rules));
    }

    public function testS2ValidateListRulesReturnFalseAndErrorIfC3TryToAccessToB3()
    {
        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', 'f_2(Champ2) -> f_3(Champ3)']);

        $value_field_list = [
            '101' => 'A2',
            '102' => 'B3',
            '103' => 'C2', //C2 cannot access to B3 !
            '104' => 'D1'
        ];

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, $this->list_rules));
    }

    public function testS3ValidateListRulesReturnTrueIfRulesAreRespected()
    {
        $GLOBALS['Response']->shouldNotReceive('addFeedback');
        $value_field_list = [
            '101' => ['A1', 'A2'],
            '102' => 'B3',
            '103' => 'C1',
            '104' => 'D1'
        ];
        $this->assertTrue($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, $this->list_rules));
    }

    public function testS4ValidateListRulesReturnTrueIfRulesAreRespected()
    {
        $GLOBALS['Response']->shouldNotReceive('addFeedback');
        $value_field_list = [
            '101' => ['A1', 'A2'],
            '102' => 'B2',
            '103' => 'C2',
            '104' => 'D1'
        ];
        $this->assertTrue($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, $this->list_rules));
    }

    public function testS5ValidateListRulesReturnTrueIfRulesAreRespected()
    {
        $GLOBALS['Response']->shouldNotReceive('addFeedback');

        $value_field_list = [
            '101' => 'A1',
            '102' => ['B1', 'B3'],
            '103' => 'C1',
            '104' => 'D1'
        ];

        $this->assertTrue($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, $this->list_rules));
    }

    public function testS6ValidateListRulesReturnFalseAndErrorIfA1TryToAccessToB2()
    {
        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', 'f_1(Champ1) -> f_2(Champ2)']);
        $value_field_list = [
            '101' => 'A1',
            '102' => ['B1', 'B2'], //A1 cannot access to B2 !
            '103' => 'C1',
            '104' => 'D1'
        ];
        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, $this->list_rules));
    }
}
