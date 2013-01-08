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
Mock::generate('Tracker_FormElementFactory');

Mock::generatePartial(
    'Tracker_FormElement_Container_Fieldset', 
    'Tracker_FormElement_Container_FieldsetTestVersion', 
    array(
        'getFormElementFactory',
    )
);
Mock::generatePartial(
    'Tracker_FormElement_Container_Fieldset', 
    'Tracker_FormElement_Container_FieldsetTestVersion_for_afterSaveObject', 
    array(
        'getFormElementFactory',
        'getFormElements',
        'getId',
    )
);


Mock::generate('Tracker_FormElement_Field_Date');

Mock::generate('Tracker');

class Tracker_FormElement_Container_FieldsetTest extends UnitTestCase {

    //testing field import
    public function testImportFormElement() {
        
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <formElement type="mon_type" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <formElements>
                    <formElement type="date" ID="F1" rank="1" required="1">
                        <name>date</name>
                        <label>date</label>
                        <description>date</description>
                    </formElement>
                </formElements>
            </formElement>'
        );
        
        $mapping = array();
        
        $a_formelement = new MockTracker_FormElement_Field_Date();
        
        $factory = new MockTracker_FormElementFactory();
        $factory->setReturnReference('getInstanceFromXML', $a_formelement);
        
        $f = new Tracker_FormElement_Container_FieldsetTestVersion();
        $f->setTracker(aTracker()->withProject(mock('Project'))->build());
        $f->setReturnReference('getFormElementFactory', $factory);
        
        $f->continueGetInstanceFromXML($xml, $mapping);
        
        $container_should_load_child = array($a_formelement);
        $this->assertEqual($f->getFormElements(), $container_should_load_child);
    }
    
    public function test_afterSaveObject() {
        $a_formelement = new MockTracker_FormElement_Field_Date();
        $factory       = new MockTracker_FormElementFactory();
        $tracker       = new MockTracker();
        $f = new Tracker_FormElement_Container_FieldsetTestVersion_for_afterSaveObject();
        $f->setReturnReference('getFormElementFactory', $factory);
        $f->setReturnValue('getFormElements', array($a_formelement));
        $f->setReturnValue('getId', 66);
        
        $factory->expect('saveObject', array($tracker, $a_formelement, 66));
        
        $f->afterSaveObject($tracker);
    }
    
    public function testIsDeletableWithFields() {
        $fieldset = new Tracker_FormElement_Container_FieldsetTestVersion_for_afterSaveObject();
        $e1 = new MockTracker_FormElement_Field_Date();
        $elements = array($e1);
        $fieldset->setReturnReference('getFormElements', $e1);
        $this->assertFalse($fieldset->canBeUnused());
    }
    
    public function testIsDeletableWithoutFields() {
        $fieldset = new Tracker_FormElement_Container_FieldsetTestVersion_for_afterSaveObject();
        $fieldset->setReturnValue('getFormElements', null);
        $this->assertTrue($fieldset->canBeUnused());
    }
    
}
?>