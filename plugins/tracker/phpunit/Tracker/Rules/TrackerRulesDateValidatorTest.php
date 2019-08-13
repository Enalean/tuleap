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
use Tracker_Rule_Date;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Tracker\Rule\TrackerRulesDateValidator;

class TrackerRulesDateValidatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var TrackerRulesDateValidator
     */
    private $tracker_rules_date_validator;

    /**
     * @var Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $formelement_factory;

    public function setUp(): void
    {
        $GLOBALS['Response']       = Mockery::mock(BaseLayout::class);

        $this->formelement_factory = \Mockery::mock(\Tracker_FormElementFactory::class);
        $this->tracker_rules_date_validator = new TrackerRulesDateValidator($this->formelement_factory);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['Response']);
    }

    public function testValidateDateRulesReturnsTrueWhenThereAreValidDateRules()
    {
        $tracker_rule_date  = Mockery::mock(\Tracker_Rule_Date::class);
        $tracker_rule_date2 = Mockery::mock(\Tracker_Rule_Date::class);

        $tracker_rule_date->shouldReceive('validate')->andReturn(true);
        $tracker_rule_date->shouldReceive('getSourceFieldId')->andReturn(10);
        $tracker_rule_date->shouldReceive('getTargetFieldId')->andReturn(11);
        $tracker_rule_date2->shouldReceive('validate')->andReturn(true);
        $tracker_rule_date2->shouldReceive('getSourceFieldId')->andReturn(12);
        $tracker_rule_date2->shouldReceive('getTargetFieldId')->andReturn(13);

        $value_field_list = array(
            10 => '',
            11 => '',
            12 => '',
            13 => '',
        );
        $this->assertTrue($this->tracker_rules_date_validator->validateDateRules($value_field_list, [$tracker_rule_date, $tracker_rule_date2]));
    }

    public function testValidateDateRulesReturnsFalseAndFeedbackWhenADateIsnotValid()
    {
        $source_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $source_field->shouldReceive('getID')->andReturns(123);
        $source_field->shouldReceive('getLabel')->andReturns('aaaaa');
        $target_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $target_field->shouldReceive('getID')->andReturns(789);
        $target_field->shouldReceive('getLabel')->andReturns('bbbbb');

        $comparator =  Tracker_Rule_Date::COMPARATOR_GREATER_THAN;
        $tracker_rule_date  = Mockery::mock(\Tracker_Rule_Date::class);
        $tracker_rule_date2 = Mockery::mock(\Tracker_Rule_Date::class);

        $tracker_rule_date->shouldReceive('validate')->andReturn(true);
        $tracker_rule_date->shouldReceive('getSourceFieldId')->andReturn(10);
        $tracker_rule_date->shouldReceive('getTargetFieldId')->andReturn(11);
        $tracker_rule_date->shouldReceive('getComparator')->andReturn($comparator);

        $tracker_rule_date2->shouldReceive('validate')->andReturn(false);
        $tracker_rule_date2->shouldReceive('getSourceFieldId')->andReturn(12);
        $tracker_rule_date2->shouldReceive('getTargetFieldId')->andReturn(13);
        $tracker_rule_date2->shouldReceive('getComparator')->andReturn($comparator);
        $this->formelement_factory->shouldReceive('getFormElementById')->withArgs([12])->andReturns($source_field);
        $this->formelement_factory->shouldReceive('getFormElementById')->withArgs([13])->andReturns($target_field);

        $GLOBALS['Language']->shouldReceive('getText')->andReturn('this is a text');
        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', 'this is a text']);
        $source_field->shouldReceive('setHasErrors')->withArgs([true]);
        $target_field->shouldReceive('setHasErrors')->withArgs([true]);

        $value_field_list = array(
            10 => '',
            11 => '',
            12 => '',
            13 => '',
        );
        $this->assertFalse($this->tracker_rules_date_validator->validateDateRules($value_field_list, [$tracker_rule_date, $tracker_rule_date2]));
    }

    public function testValidateDateRulesReturnsFalseAndFeedbackDuringCSVImportWhenADateIsValidButNotInFieldList()
    {
        $source_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $source_field->shouldReceive('getID')->andReturns(123);
        $source_field->shouldReceive('getLabel')->andReturns('aaaaa');

        $target_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $target_field->shouldReceive('getID')->andReturns(789);
        $target_field->shouldReceive('getLabel')->andReturns('bbbbb');
        $comparator =  Tracker_Rule_Date::COMPARATOR_GREATER_THAN;

        $tracker_rule_date  = Mockery::mock(\Tracker_Rule_Date::class);
        $tracker_rule_date2 = Mockery::mock(\Tracker_Rule_Date::class);
        $tracker_rule_date->shouldReceive('validate')->andReturn(true);
        $tracker_rule_date->shouldReceive('getSourceFieldId')->andReturn(10);
        $tracker_rule_date->shouldReceive('getTargetFieldId')->andReturn(11);
        $tracker_rule_date->shouldReceive('getComparator')->andReturn($comparator);

        $tracker_rule_date2->shouldReceive('validate')->andReturn(true);
        $tracker_rule_date2->shouldReceive('getSourceFieldId')->andReturn(12);
        $tracker_rule_date2->shouldReceive('getTargetFieldId')->andReturn(13);
        $tracker_rule_date2->shouldReceive('getComparator')->andReturn($comparator);
        $this->formelement_factory->shouldReceive('getFormElementById')->withArgs([12])->andReturns($source_field);
        $this->formelement_factory->shouldReceive('getFormElementById')->withArgs([13])->andReturns($target_field);

        $GLOBALS['Language']->shouldReceive('getText')->andReturn('this is a text : ');
        $GLOBALS['Response']->shouldReceive('addUniqueFeedback')->withArgs(['error', 'this is a text : aaaaa']);
        $GLOBALS['Language']->shouldReceive('getText')->andReturn('this is a text : ');
        $GLOBALS['Response']->shouldReceive('addUniqueFeedback')->withArgs(['error', 'this is a text : bbbbb']);
        $source_field->shouldReceive('setHasErrors')->withArgs([true]);
        $target_field->shouldReceive('setHasErrors')->withArgs([true]);

        $value_field_list = array(
            10 => '',
            11 => ''
        );

        $this->assertFalse($this->tracker_rules_date_validator->validateDateRules($value_field_list, [$tracker_rule_date, $tracker_rule_date2]));
    }
}
