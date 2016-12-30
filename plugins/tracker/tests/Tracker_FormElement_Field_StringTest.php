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
Mock::generatePartial('Tracker_FormElement_Field_String', 'Tracker_FormElement_Field_StringTestVersion', array('getRuleString', 'getRuleNoCr', 'getProperty'));

Mock::generate('Tracker_Artifact');

require_once('common/valid/Rule.class.php');
Mock::generate('Rule_String');
Mock::generate('Rule_NoCr');

require_once('common/include/Response.class.php');
Mock::generate('Response');

class Tracker_FormElement_Field_StringTest extends TuleapTestCase {

    function setUp() {
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Language'] = new MockBaseLanguage();
    }

    function tearDrop() {
        unset($GLOBALS['Response']);
    }

    function testNoDefaultValue() {
        $str_field = new Tracker_FormElement_Field_StringTestVersion();
        $this->assertFalse($str_field->hasDefaultValue());
    }

    function testDefaultValue() {
        $str_field = new Tracker_FormElement_Field_StringTestVersion();
        $str_field->setReturnValue('getProperty', 'foo', array('default_value'));
        $this->assertTrue($str_field->hasDefaultValue());
        $this->assertEqual($str_field->getDefaultValue(), 'foo');
    }

    function testIsValid() {
        $artifact = new MockTracker_Artifact();

        $rule_string = new MockRule_String();
        $rule_string->setReturnValue('isValid', true);

        $rule_nocr = new MockRule_NoCr();
        $rule_nocr->setReturnValue('isValid', true);

        $string = new Tracker_FormElement_Field_StringTestVersion();
        $string->setReturnReference('getRuleString', $rule_string);
        $string->setReturnReference('getRuleNoCr', $rule_nocr);

        $this->assertTrue($string->isValid($artifact, "Du texte"));
    }

    function testIsValid_cr() {
        $artifact = new MockTracker_Artifact();

        $rule_string = new MockRule_String();
        $rule_string->setReturnValue('isValid', true);

        $rule_nocr = new MockRule_NoCr();
        $rule_nocr->setReturnValue('isValid', false);

        $string = new Tracker_FormElement_Field_StringTestVersion();
        $string->setReturnReference('getRuleString', $rule_string);
        $string->setReturnReference('getRuleNoCr', $rule_nocr);

        $this->assertFalse($string->isValid($artifact, "Du texte \n sur plusieurs lignes"));
    }

    public function itAcceptsStringRespectingMaxCharsProperty()
    {
        $artifact = new MockTracker_Artifact();

        $rule_string = new MockRule_String();
        $rule_string->setReturnValue('isValid', true);

        $rule_nocr = new MockRule_NoCr();
        $rule_nocr->setReturnValue('isValid', true);

        $string = new Tracker_FormElement_Field_StringTestVersion();
        $string->setReturnReference('getRuleString', $rule_string);
        $string->setReturnReference('getRuleNoCr', $rule_nocr);
        stub($string)->getProperty('maxchars')->returns(6);

        $this->assertTrue($string->isValid($artifact, 'Tuleap'));
    }

    public function itAcceptsStringWhenMaxCharsPropertyIsNotDefined()
    {
        $artifact = new MockTracker_Artifact();

        $rule_string = new MockRule_String();
        $rule_string->setReturnValue('isValid', true);

        $rule_nocr = new MockRule_NoCr();
        $rule_nocr->setReturnValue('isValid', true);

        $string = new Tracker_FormElement_Field_StringTestVersion();
        $string->setReturnReference('getRuleString', $rule_string);
        $string->setReturnReference('getRuleNoCr', $rule_nocr);
        stub($string)->getProperty('maxchars')->returns(0);

        $this->assertTrue($string->isValid($artifact, 'Tuleap'));
    }

    public function itRejectsStringNotRespectingMaxCharsProperty()
    {
        $artifact = new MockTracker_Artifact();

        $rule_string = new MockRule_String();
        $rule_string->setReturnValue('isValid', true);

        $rule_nocr = new MockRule_NoCr();
        $rule_nocr->setReturnValue('isValid', true);

        $string = new Tracker_FormElement_Field_StringTestVersion();
        $string->setReturnReference('getRuleString', $rule_string);
        $string->setReturnReference('getRuleNoCr', $rule_nocr);
        stub($string)->getProperty('maxchars')->returns(1);

        $this->assertFalse($string->isValid($artifact, 'Tuleap'));
    }

    function testSoapAvailableValues() {
        $f = new Tracker_FormElement_Field_StringTestVersion();
        $this->assertNull($f->getSoapAvailableValues());
    }

    function testGetFieldData() {
        $f = new Tracker_FormElement_Field_StringTestVersion();
        $this->assertEqual('this is a string value', $f->getFieldData('this is a string value'));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6449
     */
    function itIsEmptyWhenThereIsNoContent() {
        $artifact = new MockTracker_Artifact();
        $field    = aStringField()->build();
        $this->assertTrue($field->isEmpty('', $artifact));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6449
     */
    function itIsEmptyWhenThereIsOnlyWhitespaces() {
        $artifact = new MockTracker_Artifact();
        $field    = aStringField()->build();
        $this->assertTrue($field->isEmpty('  ', $artifact));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6449
     */
    function itIsNotEmptyWhenThereIsContent() {
        $artifact = new MockTracker_Artifact();
        $field    = aStringField()->build();
        $this->assertFalse($field->isEmpty('sdf', $artifact));
    }
}

class Tracker_FormElement_Field_String_RESTTests extends TuleapTestCase {

    public function itReturnsTheValueIndexedByFieldName() {
        $field = aStringField()->build();
        $value = array(
            "field_id" => 330,
            "value"    => 'My awesome content'
        );

        $this->assertEqual('My awesome content', $field->getFieldDataFromRESTValueByField($value));
    }
}

class Tracker_FormElement_Field_String_Changes extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->field = aStringField()->build();
        $this->previous_value = stub('Tracker_Artifact_ChangesetValue_String')->getText()->returns('1');
    }

    public function itReturnsTrueIfThereIsAChange() {
        $new_value = '1.0';

        $this->assertTrue($this->field->hasChanges(mock('Tracker_Artifact'), $this->previous_value, $new_value));
    }

    public function itReturnsFalseIfThereIsNoChange() {
        $new_value = '1';

        $this->assertFalse($this->field->hasChanges(mock('Tracker_Artifact'), $this->previous_value, $new_value));
    }
}