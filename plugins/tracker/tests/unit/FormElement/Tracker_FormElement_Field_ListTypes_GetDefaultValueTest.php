<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_Field_List_Bind_Static;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\CheckboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\MultiSelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\RadioButtonFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SubmittedByFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_ListTypes_GetDefaultValueTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker_FormElement_Field_List_Bind_Static&MockObject $bind;

    protected function setUp(): void
    {
        $this->bind = $this->createMock(Tracker_FormElement_Field_List_Bind_Static::class);
    }

    public function testSelectBoxWithOneValue(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([300 => 0]);

        $field = SelectboxFieldBuilder::aSelectboxField(456)->build();
        $field->setBind($this->bind);

        self::assertEquals(300, $field->getDefaultValue());
    }

    public function testSelectBoxWithMultipleValues(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([300 => 0, 200 => 4]);

        $field = SelectboxFieldBuilder::aSelectboxField(456)->build();
        $field->setBind($this->bind);

        self::assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }

    public function testSelectBoxWithNoValue(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([]);

        $field = SelectboxFieldBuilder::aSelectboxField(456)->build();
        $field->setBind($this->bind);

        self::assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }

    public function testMultiSelectBoxWithOneValue(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([300 => 0]);

        $field = MultiSelectboxFieldBuilder::aMultiSelectboxField(789)->build();
        $field->setBind($this->bind);

        self::assertEquals([300], $field->getDefaultValue());
    }

    public function testMultiSelectBoxWithMultipleValues(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([300 => 0, 200 => 4]);

        $field = MultiSelectboxFieldBuilder::aMultiSelectboxField(789)->build();
        $field->setBind($this->bind);

        self::assertEquals([300, 200], $field->getDefaultValue());
    }

    public function testMultiSelectBoxWithNoValue(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([]);

        $field = MultiSelectboxFieldBuilder::aMultiSelectboxField(789)->build();
        $field->setBind($this->bind);

        self::assertEquals([Tracker_FormElement_Field_List_Bind::NONE_VALUE], $field->getDefaultValue());
    }

    public function testRadioWithOneValue(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([300 => 0]);

        $field = RadioButtonFieldBuilder::aRadioButtonField(963)->build();
        $field->setBind($this->bind);

        self::assertEquals(300, $field->getDefaultValue());
    }

    public function testRadioWithMultipleValues(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([300 => 0, 200 => 4]);

        $field = RadioButtonFieldBuilder::aRadioButtonField(963)->build();
        $field->setBind($this->bind);

        self::assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }

    public function testRadioWithNoValue(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([]);

        $field = RadioButtonFieldBuilder::aRadioButtonField(963)->build();
        $field->setBind($this->bind);

        self::assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }

    public function testCheckboxWithOneValue(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([300 => 0]);

        $field = CheckboxFieldBuilder::aCheckboxField(852)->build();
        $field->setBind($this->bind);

        self::assertEquals([300], $field->getDefaultValue());
    }

    public function testCheckboxWithMultipleValues(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([300 => 0, 200 => 4]);

        $field = CheckboxFieldBuilder::aCheckboxField(852)->build();
        $field->setBind($this->bind);

        self::assertEquals([300, 200], $field->getDefaultValue());
    }

    public function testCheckboxWithNoValue(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([]);

        $field = CheckboxFieldBuilder::aCheckboxField(852)->build();
        $field->setBind($this->bind);

        self::assertEquals([Tracker_FormElement_Field_List_Bind::NONE_VALUE], $field->getDefaultValue());
    }

    public function testOpenListWithOneValue(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([300 => 0]);

        $field = OpenListFieldBuilder::anOpenListField()->build();
        $field->setBind($this->bind);

        self::assertEquals('b300', $field->getDefaultValue());
    }

    public function testOpenListWithMultipleValues(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([300 => 0, 200 => 4]);

        $field = OpenListFieldBuilder::anOpenListField()->build();
        $field->setBind($this->bind);

        self::assertEquals('b300,b200', $field->getDefaultValue());
    }

    public function testItVerifiesThatOpenListDefaultValueIsNotBindedToSomethingWhenAnAdministratorHaveNotDefinedAPreference(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([]);

        $field = OpenListFieldBuilder::anOpenListField()->build();
        $field->setBind($this->bind);

        self::assertEquals('', $field->getDefaultValue());
    }

    public function testSubmittedByWithOneValue(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([300 => 0]);

        $field = SubmittedByFieldBuilder::aSubmittedByField(458)->build();
        $field->setBind($this->bind);

        self::assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }

    public function testSubmittedByWithMultipleValues(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([300 => 0, 200 => 4]);

        $field = SubmittedByFieldBuilder::aSubmittedByField(458)->build();
        $field->setBind($this->bind);

        self::assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }

    public function testSubmittedByWithNoValue(): void
    {
        $this->bind->method('getDefaultValues')->willReturn([]);

        $field = SubmittedByFieldBuilder::aSubmittedByField(458)->build();
        $field->setBind($this->bind);

        self::assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }
}
