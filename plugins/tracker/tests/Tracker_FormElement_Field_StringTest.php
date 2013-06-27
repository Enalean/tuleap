<?php
/**
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
Mock::generatePartial('Tracker_FormElement_Field_String', 'Tracker_FormElement_Field_StringTestVersion', array('getRuleString', 'getRuleNoCr', 'getProperty'));

Mock::generate('Tracker_Artifact');

require_once('common/valid/Rule.class.php');
Mock::generate('Rule_String');
Mock::generate('Rule_NoCr');

require_once('common/include/Response.class.php');
Mock::generate('Response');

class Tracker_FormElement_Field_StringTest extends UnitTestCase {
    
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
    
    function testSoapAvailableValues() {
        $f = new Tracker_FormElement_Field_StringTestVersion();
        $this->assertNull($f->getSoapAvailableValues());
    }
    
    function testGetFieldData() {
        $f = new Tracker_FormElement_Field_StringTestVersion();
        $this->assertEqual('this is a string value', $f->getFieldData('this is a string value'));
    }
    
}
?>
