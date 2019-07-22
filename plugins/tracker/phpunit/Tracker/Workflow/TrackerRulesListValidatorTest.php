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

namespace Tuleap\Tracker\Workflow;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Tracker\Rule\TrackerRulesListValidator;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;

class TrackerRulesListValidatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var TrackerRulesListValidator
     */
    private $tracker_rules_list_validator;
    private $formelement_factory;
    private $tracker;

    public function setUp(): void
    {
        $GLOBALS['Response'] = Mockery::mock(BaseLayout::class);

        $this->formelement_factory  = \Mockery::mock(\Tracker_FormElementFactory::class);
        $this->tracker              = \Mockery::mock(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(110);

        $this->tracker_rules_list_validator = new TrackerRulesListValidator($this->formelement_factory);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['Response']);
    }

    public function testValidateListRulesReturnTrueIfValuesAreValid(): void
    {
        $bind  = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind2 = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->shouldReceive("formatArtifactValue")->andReturns('Champ1');
        $bind2->shouldReceive("formatArtifactValue")->andReturns('Champ2');

        $source_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $source_field->shouldReceive('getID')->andReturns(123);
        $source_field->shouldReceive('getLabel')->andReturns('aaaaa');
        $source_field->shouldReceive('getBind')->andReturns($bind);

        $target_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $target_field->shouldReceive('getID')->andReturns(789);
        $target_field->shouldReceive('getLabel')->andReturns('bbbbb');
        $target_field->shouldReceive('getBind')->andReturns($bind2);

        $source_field->shouldReceive('setHasErrors')->withArgs([true]);
        $target_field->shouldReceive('setHasErrors')->withArgs([true]);

        $value_field_list  = array(
            123 => 456,
            789 => 101
        );
        $tracker_rule_list = new \Tracker_Rule_List();
        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetValue(101)
            ->setTargetFieldId(789)
            ->setTrackerId(110)
            ->setId(5);

        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([123])->andReturns($source_field);
        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([789])->andReturns($target_field);

        $this->assertTrue($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    public function testValidateListRulesReturnErrorIfTargetValuesAreDifferent(): void
    {
        $bind  = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind2 = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->shouldReceive("formatArtifactValue")->andReturns('Champ1');
        $bind2->shouldReceive("formatArtifactValue")->andReturns('Champ2');

        $source_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $source_field->shouldReceive('getID')->andReturns(123);
        $source_field->shouldReceive('getLabel')->andReturns('aaaaa');
        $source_field->shouldReceive('getBind')->andReturns($bind);

        $target_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $target_field->shouldReceive('getID')->andReturns(789);
        $target_field->shouldReceive('getLabel')->andReturns('bbbbb');
        $target_field->shouldReceive('getBind')->andReturns($bind2);

        $source_field->shouldReceive('setHasErrors')->withArgs([true]);
        $target_field->shouldReceive('setHasErrors')->withArgs([true]);

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', 'aaaaa(Champ1) -> bbbbb(Champ2)']);

        $value_field_list  = array(
            123 => 456,
            789 => 101
        );
        $tracker_rule_list = new \Tracker_Rule_List();
        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetFieldId(789)
            ->setTrackerId(110)
            ->setId(5);

        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([123])->andReturns($source_field);
        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([789])->andReturns($target_field);

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    public function testValidateListRulesReturnErrorIfSourceValuesAreDifferent(): void
    {
        $bind  = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind2 = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->shouldReceive("formatArtifactValue")->andReturns('Champ1');
        $bind2->shouldReceive("formatArtifactValue")->andReturns('Champ2');

        $source_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $source_field->shouldReceive('getID')->andReturns(123);
        $source_field->shouldReceive('getLabel')->andReturns('aaaaa');
        $source_field->shouldReceive('getBind')->andReturns($bind);

        $target_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $target_field->shouldReceive('getID')->andReturns(789);
        $target_field->shouldReceive('getLabel')->andReturns('bbbbb');
        $target_field->shouldReceive('getBind')->andReturns($bind2);

        $source_field->shouldReceive('setHasErrors')->withArgs([true]);
        $target_field->shouldReceive('setHasErrors')->withArgs([true]);

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', 'aaaaa(Champ1) -> bbbbb(Champ2)']);

        $value_field_list  = array(
            123 => 456,
            789 => 101
        );
        $tracker_rule_list = new \Tracker_Rule_List();
        $tracker_rule_list->setSourceValue(4560)
            ->setSourceFieldId(123)
            ->setTargetValue(101)
            ->setTargetFieldId(789)
            ->setTrackerId(110)
            ->setId(5);

        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([123])->andReturns($source_field);
        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([789])->andReturns($target_field);

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    public function testValidateListRulesReturnErrorIfTrackersIdsAreDifferent(): void
    {
        $bind  = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind2 = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->shouldReceive("formatArtifactValue")->andReturns('Champ1');
        $bind2->shouldReceive("formatArtifactValue")->andReturns('Champ2');

        $source_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $source_field->shouldReceive('getID')->andReturns(123);
        $source_field->shouldReceive('getLabel')->andReturns('aaaaa');
        $source_field->shouldReceive('getBind')->andReturns($bind);

        $target_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $target_field->shouldReceive('getID')->andReturns(789);
        $target_field->shouldReceive('getLabel')->andReturns('bbbbb');
        $target_field->shouldReceive('getBind')->andReturns($bind2);

        $source_field->shouldReceive('setHasErrors')->withArgs([true]);
        $target_field->shouldReceive('setHasErrors')->withArgs([true]);

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', 'aaaaa(Champ1) -> bbbbb(Champ2)']);

        $value_field_list  = array(
            123 => 456,
            789 => 101
        );
        $tracker_rule_list = new \Tracker_Rule_List();
        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetValue(101)
            ->setTargetFieldId(789)
            ->setTrackerId(11)
            ->setId(5);

        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([123])->andReturns($source_field);
        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([789])->andReturns($target_field);

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    public function testValidateListRulesReturnErrorIfAFieldIsEmpty(): void
    {
        $bind  = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind2 = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->shouldReceive("formatArtifactValue")->andReturns('Champ1');
        $bind2->shouldReceive("formatArtifactValue")->andReturns('');

        $source_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $source_field->shouldReceive('getID')->andReturns(123);
        $source_field->shouldReceive('getLabel')->andReturns('aaaaa');
        $source_field->shouldReceive('getBind')->andReturns($bind);

        $target_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $target_field->shouldReceive('getID')->andReturns(789);
        $target_field->shouldReceive('getLabel')->andReturns('bbbbb');
        $target_field->shouldReceive('getBind')->andReturns($bind2);

        $source_field->shouldReceive('setHasErrors')->withArgs([true]);
        $target_field->shouldReceive('setHasErrors')->withArgs([true]);

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', 'aaaaa(Champ1) -> bbbbb()']);

        $value_field_list  = array(
            123 => 456,
            789 => ''
        );
        $tracker_rule_list = new \Tracker_Rule_List();
        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetValue(101)
            ->setTargetFieldId(789)
            ->setTrackerId(110)
            ->setId(5);

        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([123])->andReturns($source_field);
        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([789])->andReturns($target_field);

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }

    public function testValidateListRulesReturnErrorIfAFieldIsNoneValue(): void
    {
        $bind  = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind2 = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->shouldReceive("formatArtifactValue")->andReturns('Champ1');
        $bind2->shouldReceive("formatArtifactValue")->andReturns('');

        $source_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $source_field->shouldReceive('getID')->andReturns(123);
        $source_field->shouldReceive('getLabel')->andReturns('aaaaa');
        $source_field->shouldReceive('getBind')->andReturns($bind);

        $target_field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $target_field->shouldReceive('getID')->andReturns(789);
        $target_field->shouldReceive('getLabel')->andReturns('bbbbb');
        $target_field->shouldReceive('getBind')->andReturns($bind2);

        $source_field->shouldReceive('setHasErrors')->withArgs([true]);
        $target_field->shouldReceive('setHasErrors')->withArgs([true]);

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', 'aaaaa(Champ1) -> bbbbb()']);

        $value_field_list  = array(
            123 => 456,
            789 => 100
        );
        $tracker_rule_list = new \Tracker_Rule_List();
        $tracker_rule_list->setSourceValue(456)
            ->setSourceFieldId(123)
            ->setTargetValue(101)
            ->setTargetFieldId(789)
            ->setTrackerId(110)
            ->setId(5);

        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([123])->andReturns($source_field);
        $this->formelement_factory->shouldReceive('getFormElementListById')->withArgs([789])->andReturns($target_field);

        $this->assertFalse($this->tracker_rules_list_validator->validateListRules($this->tracker, $value_field_list, [$tracker_rule_list]));
    }
}
