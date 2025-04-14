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
use Tracker_FormElementFactory;
use Tracker_Rule_Date;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerRulesDateValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private TrackerRulesDateValidator $tracker_rules_date_validator;

    private Tracker_FormElementFactory&MockObject $formelement_factory;

    public function setUp(): void
    {
        $this->formelement_factory          = $this->createMock(\Tracker_FormElementFactory::class);
        $this->tracker_rules_date_validator = new TrackerRulesDateValidator($this->formelement_factory, new \Psr\Log\NullLogger());
    }

    public function testValidateDateRulesReturnsTrueWhenThereAreValidDateRules(): void
    {
        $tracker_rule_date  = $this->createMock(\Tracker_Rule_Date::class);
        $tracker_rule_date2 = $this->createMock(\Tracker_Rule_Date::class);

        $tracker_rule_date->method('validate')->willReturn(true);
        $tracker_rule_date->method('getSourceFieldId')->willReturn(10);
        $tracker_rule_date->method('getTargetFieldId')->willReturn(11);
        $tracker_rule_date2->method('validate')->willReturn(true);
        $tracker_rule_date2->method('getSourceFieldId')->willReturn(12);
        $tracker_rule_date2->method('getTargetFieldId')->willReturn(13);

        $value_field_list = [
            10 => '',
            11 => '',
            12 => '',
            13 => '',
        ];
        $this->assertTrue($this->tracker_rules_date_validator->validateDateRules($value_field_list, [$tracker_rule_date, $tracker_rule_date2]));
    }

    public function testValidateDateRulesReturnsFalseAndFeedbackWhenADateIsnotValid(): void
    {
        $source_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $source_field->method('getID')->willReturn(123);
        $source_field->method('getLabel')->willReturn('aaaaa');
        $tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $source_field->method('getTracker')->willReturn($tracker);
        $target_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $target_field->method('getID')->willReturn(789);
        $target_field->method('getLabel')->willReturn('bbbbb');

        $comparator         =  Tracker_Rule_Date::COMPARATOR_GREATER_THAN;
        $tracker_rule_date  = $this->createMock(\Tracker_Rule_Date::class);
        $tracker_rule_date2 = $this->createMock(\Tracker_Rule_Date::class);

        $tracker_rule_date->method('validate')->willReturn(true);
        $tracker_rule_date->method('getSourceFieldId')->willReturn(10);
        $tracker_rule_date->method('getTargetFieldId')->willReturn(11);
        $tracker_rule_date->method('getComparator')->willReturn($comparator);

        $tracker_rule_date2->method('validate')->willReturn(false);
        $tracker_rule_date2->method('getSourceFieldId')->willReturn(12);
        $tracker_rule_date2->method('getTargetFieldId')->willReturn(13);
        $tracker_rule_date2->method('getComparator')->willReturn($comparator);
        $this->formelement_factory->method('getFormElementById')->willReturnCallback(static fn (int $id) => match ($id) {
            12 => $source_field,
            13 => $target_field,
        });

        $GLOBALS['Response']->method('addFeedback')->with('error', 'Error on the tracker #' . $tracker->getId() . ' date value : aaaaa must be > to bbbbb.');
        $source_field->method('setHasErrors')->with(true);
        $target_field->method('setHasErrors')->with(true);

        $value_field_list = [
            10 => '',
            11 => '',
            12 => '',
            13 => '',
        ];
        $this->assertFalse($this->tracker_rules_date_validator->validateDateRules($value_field_list, [$tracker_rule_date, $tracker_rule_date2]));
    }

    public function testValidateDateRulesReturnsFalseAndFeedbackDuringCSVImportWhenADateIsValidButNotInFieldList(): void
    {
        $source_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $source_field->method('getID')->willReturn(123);
        $source_field->method('getLabel')->willReturn('aaaaa');

        $target_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $target_field->method('getID')->willReturn(789);
        $target_field->method('getLabel')->willReturn('bbbbb');
        $comparator =  Tracker_Rule_Date::COMPARATOR_GREATER_THAN;

        $tracker_rule_date  = $this->createMock(\Tracker_Rule_Date::class);
        $tracker_rule_date2 = $this->createMock(\Tracker_Rule_Date::class);
        $tracker_rule_date->method('validate')->willReturn(true);
        $tracker_rule_date->method('getSourceFieldId')->willReturn(10);
        $tracker_rule_date->method('getTargetFieldId')->willReturn(11);
        $tracker_rule_date->method('getComparator')->willReturn($comparator);

        $tracker_rule_date2->method('validate')->willReturn(true);
        $tracker_rule_date2->method('getSourceFieldId')->willReturn(12);
        $tracker_rule_date2->method('getTargetFieldId')->willReturn(13);
        $tracker_rule_date2->method('getComparator')->willReturn($comparator);
        $this->formelement_factory->method('getFormElementById')->willReturnCallback(static fn (int $id) => match ($id) {
            12 => $source_field,
            13 => $target_field,
        });

        $GLOBALS['Response']->method('addUniqueFeedback')->willReturnCallback(static fn (string $level, string $message) => match (true) {
            $level === 'error' && $message === 'Missing field in data:aaaaa',
                $level === 'error' && $message === 'Missing field in data:bbbbb' => true,
        });
        $source_field->method('setHasErrors')->with(true);
        $target_field->method('setHasErrors')->with(true);

        $value_field_list = [
            10 => '',
            11 => '',
        ];

        $this->assertFalse($this->tracker_rules_date_validator->validateDateRules($value_field_list, [$tracker_rule_date, $tracker_rule_date2]));
    }
}
