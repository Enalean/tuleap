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
Mock::generatePartial('Tracker_FormElement_Field_Float', 'Tracker_FormElement_Field_FloatTestVersion', array('getValueDao', 'isRequired', 'getProperty'));

Mock::generate('Tracker_Artifact_ChangesetValue_Float');

Mock::generate('Tracker_FormElement_Field_Value_FloatDao');

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

Mock::generate('Tracker_Artifact');

class Tracker_FormElement_Field_FloatTest extends TuleapTestCase {
    
    function testNoDefaultValue() {
        $float_field = new Tracker_FormElement_Field_FloatTestVersion();
        $this->assertFalse($float_field->hasDefaultValue());
    }
    
    function testDefaultValue() {
        $float_field = new Tracker_FormElement_Field_FloatTestVersion();
        $float_field->setReturnValue('getProperty', '12.34', array('default_value'));
        $this->assertTrue($float_field->hasDefaultValue());
        $this->assertEqual($float_field->getDefaultValue(), 12.34);
    }
    
    function testGetChangesetValue() {
        $value_dao = new MockTracker_FormElement_Field_Value_FloatDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValueAt(0, 'getRow', array('id' => 123, 'field_id' => 1, 'value' => '1.003'));
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);
        
        $float_field = new Tracker_FormElement_Field_FloatTestVersion();
        $float_field->setReturnReference('getValueDao', $value_dao);
        
        $this->assertIsA($float_field->getChangesetValue(null, 123, false), 'Tracker_Artifact_ChangesetValue_Float');
    }
    
    function testGetChangesetValue_doesnt_exist() {
        $value_dao = new MockTracker_FormElement_Field_Value_FloatDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);
        
        $float_field = new Tracker_FormElement_Field_FloatTestVersion();
        $float_field->setReturnReference('getValueDao', $value_dao);
        
        $this->assertNull($float_field->getChangesetValue(null, 123, false));
    }
    
    function testIsValidRequiredField() {
        $f = new Tracker_FormElement_Field_FloatTestVersion();
        $f->setReturnValue('isRequired', true);
        $a = new MockTracker_Artifact();
        $this->assertTrue($f->isValid($a, 2));
        $this->assertTrue($f->isValid($a, 789));
        $this->assertTrue($f->isValid($a, 1.23));
        $this->assertTrue($f->isValid($a, -1.45));
        $this->assertTrue($f->isValid($a, 0));
        $this->assertTrue($f->isValid($a, 0.0000));
        $this->assertTrue($f->isValid($a, '56.789'));
        $this->assertFalse($f->isValid($a, 'toto'));
        $this->assertFalse($f->isValid($a, '12toto'));
        $this->assertFalse($f->isValid($a, ''));
        $this->assertFalse($f->isValid($a, null));
    }
    
    function testIsValidNotRequiredField() {
        $f = new Tracker_FormElement_Field_FloatTestVersion();
        $f->setReturnValue('isRequired', false);
        $a = new MockTracker_Artifact();
        $this->assertTrue($f->isValid($a, ''));
        $this->assertTrue($f->isValid($a, null));
    }
    
    function testSoapAvailableValues() {
        $f = new Tracker_FormElement_Field_FloatTestVersion();
        $this->assertNull($f->getSoapAvailableValues());
    }
    
    function testGetFieldData() {
        $f = new Tracker_FormElement_Field_FloatTestVersion();
        $this->assertEqual('3.14159', $f->getFieldData('3.14159'));
    }
    
    function testFetchChangesetValue() {
        $f = new Tracker_FormElement_Field_FloatTestVersion();
        $this->assertIdentical('3.1416', $f->fetchChangesetValue(123, 456, 3.14159));
        $this->assertIdentical('0.0000', $f->fetchChangesetValue(123, 456, 0));
        $this->assertIdentical('2.0000', $f->fetchChangesetValue(123, 456, 2));
        $this->assertIdentical('', $f->fetchChangesetValue(123, 456, null));
    }
    
}
?>