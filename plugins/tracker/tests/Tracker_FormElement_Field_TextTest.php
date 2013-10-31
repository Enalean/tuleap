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
Mock::generatePartial(
    'Tracker_FormElement_Field_Text', 
    'Tracker_FormElement_Field_TextTestVersion', 
    array('getValueDao', 'getRuleString', 'isRequired', 'getProperty')
);

Mock::generate('Tracker_Artifact_ChangesetValue_Text');

Mock::generate('Tracker_FormElement_Field_Value_TextDao');

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

Mock::generate('Tracker_Artifact');

require_once('common/valid/Rule.class.php');
Mock::generate('Rule_String');

class Tracker_FormElement_Field_TextTestVersion_Expose_ProtectedMethod extends Tracker_FormElement_Field_TextTestVersion {
    public function buildMatchExpression($a, $b) { return parent::buildMatchExpression($a, $b); }
    public function quote($a) { return "'$a'"; }
}

class Tracker_FormElement_Field_TextTest extends TuleapTestCase {
    
    function testNoDefaultValue() {
        $str_field = new Tracker_FormElement_Field_TextTestVersion();
        $this->assertFalse($str_field->hasDefaultValue());
    }
    
    function testDefaultValue() {
        $str_field = new Tracker_FormElement_Field_TextTestVersion();
        $str_field->setReturnValue('getProperty', 'foo bar long text with nice stories', array('default_value'));
        $this->assertTrue($str_field->hasDefaultValue());
        $this->assertEqual($str_field->getDefaultValue(), 'foo bar long text with nice stories');
    }
    
    function testGetChangesetValue() {
        $value_dao = new MockTracker_FormElement_Field_Value_TextDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValueAt(0, 'getRow', array('id' => 123, 'field_id' => 1, 'value' => 'My Text', 'body_format' => 'text'));
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);
        
        $text_field = new Tracker_FormElement_Field_TextTestVersion();
        $text_field->setReturnReference('getValueDao', $value_dao);
        
        $this->assertIsA($text_field->getChangesetValue(null, 123, false), 'Tracker_Artifact_ChangesetValue_Text');
    }
    
    function testGetChangesetValue_doesnt_exist() {
        $value_dao = new MockTracker_FormElement_Field_Value_TextDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);
        
        $text_field = new Tracker_FormElement_Field_TextTestVersion();
        $text_field->setReturnReference('getValueDao', $value_dao);
        
        $this->assertNull($text_field->getChangesetValue(null, 123, false));
    }
    
    function testSpecialCharactersInCSVExport() {
        $text_field = new Tracker_FormElement_Field_TextTestVersion();
        $this->assertEqual("Une chaine sans accent", $text_field->fetchCSVChangesetValue(null, null, "Une chaine sans accent"));
        $this->assertEqual("Lé chaîne avé lê àccent dô où ça", $text_field->fetchCSVChangesetValue(null, null, "Lé chaîne avé lê àccent dô où ça"));
        $this->assertEqual("This, or that", $text_field->fetchCSVChangesetValue(null, null, "This, or that"));
        $this->assertEqual("This; or that", $text_field->fetchCSVChangesetValue(null, null, "This; or that"));
        $this->assertEqual("This thing is > that thing", $text_field->fetchCSVChangesetValue(null, null, "This thing is > that thing"));
        $this->assertEqual("This thing & that thing", $text_field->fetchCSVChangesetValue(null, null, "This thing & that thing"));
    }
    
    function testIsValid() {
        $artifact = new MockTracker_Artifact();
        
        $rule_string = new MockRule_String();
        $rule_string->setReturnValue('isValid', true);
        
        $text = new Tracker_FormElement_Field_TextTestVersion();
        $text->setReturnReference('getRuleString', $rule_string);
        
        $this->assertTrue($text->isValid($artifact, "Du texte"));
    }
    
    function testHasChanges() {
        $value = new MockTracker_Artifact_ChangesetValue_Text();
        $value->setReturnValue('getText', 'v1');
        
        $text = new Tracker_FormElement_Field_TextTestVersion();
        
        $this->assertTrue($text->hasChanges($value, 'v2'));
    }
    
    function testIsValidRequiredField() {
        $rule_string = new MockRule_String();
        $rule_string->expectCallCount('isValid', 3);
        
        $f = new Tracker_FormElement_Field_TextTestVersion();
        $f->setReturnValue('isRequired', true);
        $f->setReturnReference('getRuleString', $rule_string);
        
        $a = new MockTracker_Artifact();
        $f->isValid($a, 'This is a text');
        $f->isValid($a, '2009-08-45');
        $f->isValid($a, 25);
        $this->assertFalse($f->isValid($a, ''));
        $this->assertFalse($f->isValid($a, null));
    }
    
    function testIsValidNotRequiredField() {
        $rule_string = new MockRule_String();
        $rule_string->expectCallCount('isValid', 5);
        
        $f = new Tracker_FormElement_Field_TextTestVersion();
        $f->setReturnValue('isRequired', false);
        $f->setReturnReference('getRuleString', $rule_string);

        $value_1 = array(
            'content' => 'This is a text',
            'format'  => 'text'
        );

        $value_2 = array(
            'content' => '2009-08-45',
            'format'  => 'text'
        );

        $value_3 = array(
            'content' => 25,
            'format'  => 'text'
        );

        $value_4 = array(
            'content' => '',
            'format'  => 'text'
        );

        $value_5 = array(
            'content' => null,
            'format'  => 'text'
        );

        $a = new MockTracker_Artifact();
        $f->isValid($a, $value_1);
        $f->isValid($a, $value_2);
        $f->isValid($a, $value_3);
        $f->isValid($a, $value_4);
        $f->isValid($a, $value_5);
    }
    
    function testSoapAvailableValues() {
        $f = new Tracker_FormElement_Field_TextTestVersion();
        $this->assertNull($f->getSoapAvailableValues());
    }
    
    function testGetFieldData() {
        $f = new Tracker_FormElement_Field_TextTestVersion();
        $this->assertEqual('this is a text value', $f->getFieldData('this is a text value'));
    }
    
    function test_buildMatchExpression() {
        $f = new Tracker_FormElement_Field_TextTestVersion_Expose_ProtectedMethod();
        $this->assertEqual($f->buildMatchExpression('field', 'tutu'), "field LIKE '%tutu%'");
        $this->assertEqual($f->buildMatchExpression('field', 'tutu toto'), "field LIKE '%tutu%' AND field LIKE '%toto%'");
        $this->assertEqual($f->buildMatchExpression('field', '/regexp/'), "field RLIKE 'regexp'");
        $this->assertEqual($f->buildMatchExpression('field', '!/regexp/'), "field NOT RLIKE 'regexp'");
    }
}

?>
