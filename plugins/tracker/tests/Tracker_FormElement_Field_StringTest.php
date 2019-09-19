<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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
require_once('bootstrap.php');

class Tracker_FormElement_Field_StringTest extends TuleapTestCase
{

    function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
    }

    function testNoDefaultValue()
    {
        $str_field = \Mockery::mock(\Tracker_FormElement_Field_String::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $str_field->shouldReceive('getProperty')->andReturn(null);
        $this->assertFalse($str_field->hasDefaultValue());
    }


    function testDefaultValue()
    {
        $str_field = \Mockery::mock(\Tracker_FormElement_Field_String::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $str_field->shouldReceive('getProperty')->with('default_value')->andReturns('foo');
        $this->assertTrue($str_field->hasDefaultValue());
        $this->assertEqual($str_field->getDefaultValue(), 'foo');
    }

    function testIsValid()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);

        $rule_string = \Mockery::spy(\Rule_String::class);
        $rule_string->shouldReceive('isValid')->andReturns(true);

        $rule_nocr = \Mockery::spy(\Rule_NoCr::class);
        $rule_nocr->shouldReceive('isValid')->andReturns(true);

        $string = \Mockery::mock(\Tracker_FormElement_Field_String::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $string->shouldReceive('getRuleString')->andReturns($rule_string);
        $string->shouldReceive('getRuleNoCr')->andReturns($rule_nocr);
        $string->shouldReceive('getProperty')->andReturns(null);

        $this->assertTrue($string->isValid($artifact, "Du texte"));
    }

    function testIsValid_cr()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);

        $rule_string = \Mockery::spy(\Rule_String::class);
        $rule_string->shouldReceive('isValid')->andReturns(true);

        $rule_nocr = \Mockery::spy(\Rule_NoCr::class);
        $rule_nocr->shouldReceive('isValid')->andReturns(false);

        $string = \Mockery::mock(\Tracker_FormElement_Field_String::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $string->shouldReceive('getRuleString')->andReturns($rule_string);
        $string->shouldReceive('getRuleNoCr')->andReturns($rule_nocr);
        $string->shouldReceive('getProperty')->andReturns(null);

        $this->assertFalse($string->isValid($artifact, "Du texte \n sur plusieurs lignes"));
    }

    public function itAcceptsStringRespectingMaxCharsProperty()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);

        $rule_string = \Mockery::spy(\Rule_String::class);
        $rule_string->shouldReceive('isValid')->andReturns(true);

        $rule_nocr = \Mockery::spy(\Rule_NoCr::class);
        $rule_nocr->shouldReceive('isValid')->andReturns(true);

        $string = \Mockery::mock(\Tracker_FormElement_Field_String::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $string->shouldReceive('getRuleString')->andReturns($rule_string);
        $string->shouldReceive('getRuleNoCr')->andReturns($rule_nocr);
        stub($string)->getProperty('maxchars')->returns(6);

        $this->assertTrue($string->isValid($artifact, 'Tuleap'));
    }

    public function itAcceptsStringWhenMaxCharsPropertyIsNotDefined()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);

        $rule_string = \Mockery::spy(\Rule_String::class);
        $rule_string->shouldReceive('isValid')->andReturns(true);

        $rule_nocr = \Mockery::spy(\Rule_NoCr::class);
        $rule_nocr->shouldReceive('isValid')->andReturns(true);

        $string = \Mockery::mock(\Tracker_FormElement_Field_String::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $string->shouldReceive('getRuleString')->andReturns($rule_string);
        $string->shouldReceive('getRuleNoCr')->andReturns($rule_nocr);
        stub($string)->getProperty('maxchars')->returns(0);

        $this->assertTrue($string->isValid($artifact, 'Tuleap'));
    }

    public function itRejectsStringNotRespectingMaxCharsProperty()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);

        $rule_string = \Mockery::spy(\Rule_String::class);
        $rule_string->shouldReceive('isValid')->andReturns(true);

        $rule_nocr = \Mockery::spy(\Rule_NoCr::class);
        $rule_nocr->shouldReceive('isValid')->andReturns(true);

        $string = \Mockery::mock(\Tracker_FormElement_Field_String::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $string->shouldReceive('getRuleString')->andReturns($rule_string);
        $string->shouldReceive('getRuleNoCr')->andReturns($rule_nocr);
        stub($string)->getProperty('maxchars')->returns(1);

        $this->assertFalse($string->isValid($artifact, 'Tuleap'));
    }

    function testGetFieldData()
    {
        $f = \Mockery::mock(\Tracker_FormElement_Field_String::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertEqual('this is a string value', $f->getFieldData('this is a string value'));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6449
     */
    function itIsEmptyWhenThereIsNoContent()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $field    = aStringField()->build();
        $this->assertTrue($field->isEmpty('', $artifact));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6449
     */
    function itIsEmptyWhenThereIsOnlyWhitespaces()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $field    = aStringField()->build();
        $this->assertTrue($field->isEmpty('  ', $artifact));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6449
     */
    function itIsNotEmptyWhenThereIsContent()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $field    = aStringField()->build();
        $this->assertFalse($field->isEmpty('sdf', $artifact));
    }
}

class Tracker_FormElement_Field_String_RESTTests extends TuleapTestCase
{

    public function itReturnsTheValueIndexedByFieldName()
    {
        $field = aStringField()->build();
        $value = array(
            "field_id" => 330,
            "value"    => 'My awesome content'
        );

        $this->assertEqual('My awesome content', $field->getFieldDataFromRESTValueByField($value));
    }
}

class Tracker_FormElement_Field_String_Changes extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->field = aStringField()->build();
        $this->previous_value = mockery_stub(\Tracker_Artifact_ChangesetValue_String::class)->getText()->returns('1');
    }

    public function itReturnsTrueIfThereIsAChange()
    {
        $new_value = '1.0';

        $this->assertTrue($this->field->hasChanges(\Mockery::spy(\Tracker_Artifact::class), $this->previous_value, $new_value));
    }

    public function itReturnsFalseIfThereIsNoChange()
    {
        $new_value = '1';

        $this->assertFalse($this->field->hasChanges(\Mockery::spy(\Tracker_Artifact::class), $this->previous_value, $new_value));
    }
}
