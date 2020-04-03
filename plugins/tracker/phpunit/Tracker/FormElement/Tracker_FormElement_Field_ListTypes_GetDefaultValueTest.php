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

final class Tracker_FormElement_Field_ListTypes_GetDefaultValueTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     *
     * @var Tracker_FormElement_Field_List_Bind_Static
     */
    private $bind;

    protected function setUp(): void
    {
        $this->bind = \Mockery::spy(\Tracker_FormElement_Field_List_Bind_Static::class);
    }

    public function testSelectBoxWithOneValue(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([300 => 0]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals(300, $field->getDefaultValue());
    }

    public function testSelectBoxWithMultipleValues(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([300 => 0, 200 => 4]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }

    public function testSelectBoxWithNoValue(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }

    public function testMultiSelectBoxWithOneValue(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([300 => 0]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_MultiSelectbox::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals([300], $field->getDefaultValue());
    }

    public function testMultiSelectBoxWithMultipleValues(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([300 => 0, 200 => 4]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_MultiSelectbox::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals([300, 200], $field->getDefaultValue());
    }

    public function testMultiSelectBoxWithNoValue(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_MultiSelectbox::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals([Tracker_FormElement_Field_List_Bind::NONE_VALUE], $field->getDefaultValue());
    }

    public function testRadioWithOneValue(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([300 => 0]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Radiobutton::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals(300, $field->getDefaultValue());
    }

    public function testRadioWithMultipleValues(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([300 => 0, 200 => 4]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Radiobutton::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }

    public function testRadioWithNoValue(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Radiobutton::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }

    public function testCheckboxWithOneValue(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([300 => 0]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Checkbox::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals([300], $field->getDefaultValue());
    }

    public function testCheckboxWithMultipleValues(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([300 => 0, 200 => 4]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Checkbox::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals([300, 200], $field->getDefaultValue());
    }

    public function testCheckboxWithNoValue(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Checkbox::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals([Tracker_FormElement_Field_List_Bind::NONE_VALUE], $field->getDefaultValue());
    }

    public function testOpenListWithOneValue(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([300 => 0]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_OpenList::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals('b300', $field->getDefaultValue());
    }

    public function testOpenListWithMultipleValues(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([300 => 0, 200 => 4]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_OpenList::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals('b300,b200', $field->getDefaultValue());
    }

    public function testItVerifiesThatOpenListDefaultValueIsNotBindedToSomethingWhenAnAdministratorHaveNotDefinedAPreference(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_OpenList::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals('', $field->getDefaultValue());
    }

    public function testSubmittedByWithOneValue(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([300 => 0]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_SubmittedBy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }

    public function testSubmittedByWithMultipleValues(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([300 => 0, 200 => 4]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_SubmittedBy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }

    public function testSubmittedByWithNoValue(): void
    {
        $this->bind->shouldReceive('getDefaultValues')->andReturns([]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_SubmittedBy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getBind')->andReturns($this->bind);

        $this->assertEquals(Tracker_FormElement_Field_List_Bind::NONE_VALUE, $field->getDefaultValue());
    }
}
