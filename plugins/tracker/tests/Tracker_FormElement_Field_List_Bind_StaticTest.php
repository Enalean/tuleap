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
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_FormElement_Field_List_Bind_StaticValue');
Mock::generate('Tracker_FormElement_Field_List');

class Tracker_FormElement_Field_List_Bind_StaticTest extends UnitTestCase {
    
    public function testGetBindValues() {
        $bv1 = new MockTracker_FormElement_Field_List_Bind_StaticValue();
        $bv2 = new MockTracker_FormElement_Field_List_Bind_StaticValue();
        $field = $is_rank_alpha = $default_values = $decorators = '';
        $values = array(101 => $bv1, 102 => $bv2);
        $static = new Tracker_FormElement_Field_List_Bind_Static($field, $is_rank_alpha, $values, $default_values, $decorators);
        $this->assertEqual($static->getBindValues(), $values);
        $this->assertEqual($static->getBindValues(array()), array(), 'Dont give more than what we are asking');
        $this->assertEqual($static->getBindValues(array(102)), array(102 => $bv2));
        $this->assertEqual($static->getBindValues(array(666)), array(), 'What do we have to do with unknown value?');
    }
    
    public function testGetSoapAvailableValues() {
        $bv1 = new MockTracker_FormElement_Field_List_Bind_StaticValue();
        $bv1->setReturnValue('getId', 101);
        $bv1->setReturnValue('getSoapValue', 'bv label 1');
        $bv2 = new MockTracker_FormElement_Field_List_Bind_StaticValue();
        $bv2->setReturnValue('getId', 102);
        $bv2->setReturnValue('getSoapValue', 'bv label 2');
        $field = new MockTracker_FormElement_Field_List();
        $field->setReturnValue('getId', 123);
        $is_rank_alpha = $default_values = $decorators = '';
        $values = array(101 => $bv1, 102 => $bv2);
        $static = new Tracker_FormElement_Field_List_Bind_Static($field, $is_rank_alpha, $values, $default_values, $decorators);
        
        $this->assertEqual(count($static->getSoapAvailableValues()), 2);
        $soap_values = array(
            array(
                'bind_value_id' => 101,
                'bind_value_label' => 'bv label 1',
            ),
            array(
                'bind_value_id' => 102,
                'bind_value_label' => 'bv label 2'
            )
        );
        $this->assertEqual($static->getSoapAvailableValues(), $soap_values);
    }
    
    function testGetFieldData() {
        $bv1 = aFieldListStaticValue()->withLabel('1 - Ordinary')->build();
        $bv2 = aFieldListStaticValue()->withLabel('9 - Critical')->build();
        $values = array(13564 => $bv1, 13987 => $bv2);
        $f = aBindStatic()->withValues($values)->build();
        $this->assertEqual('13564', $f->getFieldData('1 - Ordinary', false));
    }
    
    function testGetFieldDataMultiple() {
        $bv1 = aFieldListStaticValue()->withLabel('Admin')->build();
        $bv2 = aFieldListStaticValue()->withLabel('Tracker')->build();
        $bv3 = aFieldListStaticValue()->withLabel('User Interface')->build();
        $bv4 = aFieldListStaticValue()->withLabel('Docman')->build();
        $values = array(13564 => $bv1, 13987 => $bv2, 125 => $bv3, 666 => $bv4);

        $res = array('13564', '125', '666');
        $f = aBindStatic()->withValues($values)->build();
        $this->assertEqual($res, $f->getFieldData('Admin,User Interface,Docman', true));
    }
}
?>
