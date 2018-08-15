<?php
/**
 * Copyright (c) Enalean SAS. 2011 - 2018. All rights reserved
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

class Tracker_FormElement_Container_FieldsetTest extends TuleapTestCase {

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

        $f->continueGetInstanceFromXML($xml, $mapping, mock('User\XML\Import\IFindUserFromXMLReference'));

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
        
        $factory->expect('saveObject', array($tracker, $a_formelement, 66, false, false));
        
        $f->afterSaveObject($tracker, false, false);
    }
    
    public function testIsDeletableWithFields() {
        $fieldset = new Tracker_FormElement_Container_FieldsetTestVersion_for_afterSaveObject();
        $e1 = new MockTracker_FormElement_Field_Date();
        $fieldset->setReturnValue('getFormElements', [$e1]);
        $this->assertFalse($fieldset->getCannotRemoveMessage());
    }
    
    public function testIsDeletableWithoutFields() {
        $expected_message = '';
        $fieldset = new Tracker_FormElement_Container_FieldsetTestVersion_for_afterSaveObject();
        $fieldset->setReturnValue('getFormElements', null);
        $this->assertEqual($expected_message, $fieldset->getCannotRemoveMessage());
    }

}

class Tracker_FormElement_Container_Fieldset_ExportXMLTest extends TuleapTestCase {

    public function itCallsExportPermissionsToXMLForEachSubfield() {
        $fieldset = partial_mock(
            'Tracker_FormElement_Container_Fieldset',
            array('getAllFormElements')
        );

        $field_01 = mock('Tracker_FormElement_Field_String');
        $field_02 = mock('Tracker_FormElement_Field_Float');
        $field_03 = mock('Tracker_FormElement_Field_Text');

        stub($fieldset)->getAllFormElements()->returns(array(
            $field_01, $field_02, $field_03
        ));

        $data    = '<?xml version="1.0" encoding="UTF-8"?>
                    <permissions/>';
        $xml     = new SimpleXMLElement($data);
        $mapping = array();
        $ugroups = array();

        expect($field_01)->exportPermissionsToXML()->once();
        expect($field_02)->exportPermissionsToXML()->once();
        expect($field_03)->exportPermissionsToXML()->once();

        $fieldset->exportPermissionsToXML($xml, $ugroups, $mapping);
    }
}