<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFields;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsets;
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetIntValue;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PostActionsMapperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PostActionsMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new PostActionsMapper();
    }

    public function testConvertToCIBuildWithNullId(): void
    {
        $first_ci_build = $this->createMock(\Transition_PostAction_CIBuild::class);
        $first_ci_build->method('getJobUrl')->willReturn('https://example.com/1');
        $second_ci_build = $this->createMock(\Transition_PostAction_CIBuild::class);
        $second_ci_build->method('getJobUrl')->willReturn('https://example.com/2');

        $result = $this->mapper->convertToCIBuildWithNullId($first_ci_build, $second_ci_build);

        $this->assertEquals(
            [
                new CIBuildValue('https://example.com/1'),
                new CIBuildValue('https://example.com/2'),
            ],
            $result
        );
    }

    public function testConvertToSetDateValueWithNullId(): void
    {
        $first_date = $this->createMock(\Transition_PostAction_Field_Date::class);
        $first_date->method('getFieldId')->willReturn(104);
        $first_date->method('getValueType')->willReturn(\Transition_PostAction_Field_Date::FILL_CURRENT_TIME);

        $second_date = $this->createMock(\Transition_PostAction_Field_Date::class);
        $second_date->method('getFieldId')->willReturn(108);
        $second_date->method('getValueType')->willReturn(\Transition_PostAction_Field_Date::CLEAR_DATE);

        $result = $this->mapper->convertToSetDateValueWithNullId($first_date, $second_date);
        $this->assertEquals(
            [
                new SetDateValue(104, \Transition_PostAction_Field_Date::FILL_CURRENT_TIME),
                new SetDateValue(108, \Transition_PostAction_Field_Date::CLEAR_DATE),
            ],
            $result
        );
    }

    public function testConvertToSetFloatValueWithNullId(): void
    {
        $first_float = $this->createMock(\Transition_PostAction_Field_Float::class);
        $first_float->method('getFieldId')->willReturn(104);
        $first_float->method('getValue')->willReturn(186.43);
        $second_float = $this->createMock(\Transition_PostAction_Field_Float::class);
        $second_float->method('getFieldId')->willReturn(108);
        $second_float->method('getValue')->willReturn(-83);

        $result = $this->mapper->convertToSetFloatValueWithNullId($first_float, $second_float);
        $this->assertEquals(
            [
                new SetFloatValue(104, 186.43),
                new SetFloatValue(108, -83),
            ],
            $result
        );
    }

    public function testConvertToSetIntValueWithNullId(): void
    {
        $first_int = $this->createMock(\Transition_PostAction_Field_Int::class);
        $first_int->method('getFieldId')->willReturn(104);
        $first_int->method('getValue')->willReturn(42);
        $second_int = $this->createMock(\Transition_PostAction_Field_Int::class);
        $second_int->method('getFieldId')->willReturn(108);
        $second_int->method('getValue')->willReturn(-18);

        $result = $this->mapper->convertToSetIntValueWithNullId($first_int, $second_int);
        $this->assertEquals(
            [
                new SetIntValue(104, 42),
                new SetIntValue(108, -18),
            ],
            $result
        );
    }

    public function testConvertToFrozenFieldsValueValueWithNullId(): void
    {
        $frozen_fields = $this->createMock(FrozenFields::class);
        $frozen_fields->method('getFieldIds')->willReturn([999]);

        $result = $this->mapper->convertToFrozenFieldValueWithNullId($frozen_fields);
        $this->assertEquals(
            [
                new FrozenFieldsValue([999]),
            ],
            $result
        );
    }

    public function testConvertToHiddenFieldsetsValueValueWithNullId(): void
    {
        $fieldset_01 = $this->createMock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = $this->createMock(\Tracker_FormElement_Container_Fieldset::class);

        $fieldset_01->method('getID')->willReturn('648');
        $fieldset_02->method('getID')->willReturn('701');

        $hidden_fieldsets = $this->createMock(HiddenFieldsets::class);
        $hidden_fieldsets->method('getFieldsets')->willReturn([
            $fieldset_01,
            $fieldset_02,
        ]);

        $result = $this->mapper->convertToHiddenFieldsetsValueWithNullId($hidden_fieldsets);
        $this->assertEquals(
            [
                new HiddenFieldsetsValue([648, 701]),
            ],
            $result
        );
    }
}
