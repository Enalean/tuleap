<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('bootstrap.php');

/**
 * Checks that the default values are ok for all field element types that exetend list
 */
class Tracker_FormElement_Field_ListTypes_GetDefaultValueTest extends TuleapTestCase
{

    /**
     *
     * @var Tracker_FormElement_Field_List_Bind_Static
     */
    private $bind;

    function setUp()
    {
        $this->bind = mock('Tracker_FormElement_Field_List_Bind_Static');
    }

    public function testSelectBoxWithOneValue()
    {
        stub($this->bind)->getDefaultValues()->returns(array(300 => 0));

        $field = partial_mock('Tracker_FormElement_Field_Selectbox', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), 300);
    }

    public function testSelectBoxWithMultipleValues()
    {
        stub($this->bind)->getDefaultValues()->returns(array(300 => 0, 200 => 4));

        $field = partial_mock('Tracker_FormElement_Field_Selectbox', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), Tracker_FormElement_Field_List_Bind::NONE_VALUE);
    }

    public function testSelectBoxWithNoValue()
    {
        stub($this->bind)->getDefaultValues()->returns(array());

        $field = partial_mock('Tracker_FormElement_Field_Selectbox', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), Tracker_FormElement_Field_List_Bind::NONE_VALUE);
    }

    public function testMultiSelectBoxWithOneValue()
    {
        stub($this->bind)->getDefaultValues()->returns(array(300 => 0));

        $field = partial_mock('Tracker_FormElement_Field_MultiSelectbox', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), array(300));
    }

    public function testMultiSelectBoxWithMultipleValues()
    {
        stub($this->bind)->getDefaultValues()->returns(array(300 => 0, 200 => 4));

        $field = partial_mock('Tracker_FormElement_Field_MultiSelectbox', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), array(300, 200));
    }

    public function testMultiSelectBoxWithNoValue()
    {
        stub($this->bind)->getDefaultValues()->returns(array());

        $field = partial_mock('Tracker_FormElement_Field_MultiSelectbox', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), array(Tracker_FormElement_Field_List_Bind::NONE_VALUE));
    }

    public function testRadioWithOneValue()
    {
        stub($this->bind)->getDefaultValues()->returns(array(300 => 0));

        $field = partial_mock('Tracker_FormElement_Field_Radiobutton', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), 300);
    }

    public function testRadioWithMultipleValues()
    {
        stub($this->bind)->getDefaultValues()->returns(array(300 => 0, 200 => 4));

        $field = partial_mock('Tracker_FormElement_Field_Radiobutton', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), Tracker_FormElement_Field_List_Bind::NONE_VALUE);
    }

    public function testRadioWithNoValue()
    {
        stub($this->bind)->getDefaultValues()->returns(array());

        $field = partial_mock('Tracker_FormElement_Field_Radiobutton', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), Tracker_FormElement_Field_List_Bind::NONE_VALUE);
    }

    public function testCheckboxWithOneValue()
    {
        stub($this->bind)->getDefaultValues()->returns(array(300 => 0));

        $field = partial_mock('Tracker_FormElement_Field_Checkbox', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), array(300));
    }

    public function testCheckboxWithMultipleValues()
    {
        stub($this->bind)->getDefaultValues()->returns(array(300 => 0, 200 => 4));

        $field = partial_mock('Tracker_FormElement_Field_Checkbox', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), array(300, 200));
    }

    public function testCheckboxWithNoValue()
    {
        stub($this->bind)->getDefaultValues()->returns(array());

        $field = partial_mock('Tracker_FormElement_Field_Checkbox', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), array(Tracker_FormElement_Field_List_Bind::NONE_VALUE));
    }

    public function testOpenListWithOneValue()
    {
        stub($this->bind)->getDefaultValues()->returns(array(300 => 0));

        $field = partial_mock('Tracker_FormElement_Field_OpenList', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), 'b300');
    }

    public function testOpenListWithMultipleValues()
    {
        stub($this->bind)->getDefaultValues()->returns(array(300 => 0, 200 => 4));

        $field = partial_mock('Tracker_FormElement_Field_OpenList', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), 'b300,b200');
    }

    public function itVerifiesThatOpenListDefaultValueIsNotBindedToSomethingWhenAnAdministratorHaveNotDefinedAPreference()
    {
        stub($this->bind)->getDefaultValues()->returns(array());

        $field = partial_mock('Tracker_FormElement_Field_OpenList', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), '');
    }

    public function testSubmittedByWithOneValue()
    {
        stub($this->bind)->getDefaultValues()->returns(array(300 => 0));

        $field = partial_mock('Tracker_FormElement_Field_SubmittedBy', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), Tracker_FormElement_Field_List_Bind::NONE_VALUE);
    }

    public function testSubmittedByWithMultipleValues()
    {
        stub($this->bind)->getDefaultValues()->returns(array(300 => 0, 200 => 4));

        $field = partial_mock('Tracker_FormElement_Field_SubmittedBy', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), Tracker_FormElement_Field_List_Bind::NONE_VALUE);
    }

    public function testSubmittedByWithNoValue()
    {
        stub($this->bind)->getDefaultValues()->returns(array());

        $field = partial_mock('Tracker_FormElement_Field_SubmittedBy', array('getBind'));
        stub($field)->getBind()->returns($this->bind);

        $this->assertEqual($field->getDefaultValue(), Tracker_FormElement_Field_List_Bind::NONE_VALUE);
    }
}
