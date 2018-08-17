<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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
require_once('bootstrap.php');
Mock::generatePartial(
    'Tracker_FormElement_Container_Column', 
    'Tracker_FormElement_Container_ColumnTestVersion', 
    array(
        'getFormElements'
    )
);

Mock::generate('Tracker_FormElement_Field_Date');

class Tracker_FormElement_Container_ColumnTest extends TuleapTestCase {

    public function testIsDeletableWithFields() {
        $column = new Tracker_FormElement_Container_ColumnTestVersion();
        $e1 = new MockTracker_FormElement_Field_Date();
        $column->setReturnValue('getFormElements', [$e1]);
        $this->assertFalse($column->getCannotRemoveMessage());
    }
    
    public function testIsDeletableWithoutFields() {
        $expected_message = '';
        $column = new Tracker_FormElement_Container_ColumnTestVersion();
        $column->setReturnValue('getFormElements', null);
        $this->assertEqual($expected_message, $column->getCannotRemoveMessage());
    }
    
}

class Tracker_FormElement_Container_Column_ExportXMLTest extends TuleapTestCase {

    public function itCallsExportPermissionsToXMLForEachSubfield() {
        $column = partial_mock(
            'Tracker_FormElement_Container_Column',
            array('getAllFormElements')
        );

        $field_01 = mock('Tracker_FormElement_Field_String');
        $field_02 = mock('Tracker_FormElement_Field_Float');
        $field_03 = mock('Tracker_FormElement_Field_Text');

        stub($column)->getAllFormElements()->returns(array(
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

        $column->exportPermissionsToXML($xml, $ugroups, $mapping);
    }
}