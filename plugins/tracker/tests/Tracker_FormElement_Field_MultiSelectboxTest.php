<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_FormElement_Field_MultiSelectbox_getFieldDataFromSoapValue extends TuleapTestCase {
    private $field;
    private $bind;
    
    public function setUp() {
        parent::setUp();
        $this->bind  = mock('Tracker_FormElement_Field_List_Bind_Static');
        $this->field = aMultiSelectBoxField()->withBind($this->bind)->build();
    }


    public function itFallsBackToValueStringProcessing() {
        $soap_value = (object) array(
            'field_name'  => '',
            'field_label' => '',
            'field_value' => (object) array(
                'value' => 'Bravo,Zoulou'
            )
        );

        expect($this->bind)->getFieldData('Bravo,Zoulou', true)->once();
        stub($this->bind)->getFieldData()->returns(array(1586, 2894));

        $this->assertIdentical(array(1586,2894), $this->field->getFieldDataFromSoapValue($soap_value));
    }
    
    public function itExtractsDataFromBindValue() {
        $soap_value = (object) array(
            'field_name'  => '',
            'field_label' => '',
            'field_value' => (object) array(
                'bind_value' => array(
                    (object) array('bind_value_id' => 1586, 'bind_value_label' => ''),
                    (object) array('bind_value_id' => 2894, 'bind_value_label' => '')
                )
            )
        );
        $this->assertIdentical(array(1586,2894), $this->field->getFieldDataFromSoapValue($soap_value));
    }
}
?>
