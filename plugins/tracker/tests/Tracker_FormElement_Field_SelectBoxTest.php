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

class Tracker_FormElement_Field_Selectbox_getFieldDataFromSoapValue extends TuleapTestCase {
    private $field;
    private $bind;
    
    public function setUp() {
        parent::setUp();
        $this->bind  = mock('Tracker_FormElement_Field_List_Bind');
        $this->field = aSelectBoxField()->withBind($this->bind)->build();
    }


    public function itFallsBackToValueStringProcessing() {
        $soap_value = (object) array(
            'field_name'  => '',
            'field_label' => '',
            'field_value' => (object) array(
                'value' => 'Zoulou'
            )
        );

        expect($this->bind)->getFieldData('Zoulou', false)->once();
        stub($this->bind)->getFieldData()->returns(1586);

        $this->assertEqual(1586, $this->field->getFieldDataFromSoapValue($soap_value));
    }
    
    public function itExtractsDataFromBindValue() {
        $soap_value = (object) array(
            'field_name'  => '',
            'field_label' => '',
            'field_value' => (object) array(
                'bind_value' => array(
                    (object) array('bind_value_id' => 1586, 'bind_value_label' => '')
                )
            )
        );

        $this->assertEqual(1586, $this->field->getFieldDataFromSoapValue($soap_value));
    }

    public function itPrefersBindValueOnStringValue() {
        $soap_value = (object) array(
            'field_name'  => '',
            'field_label' => '',
            'field_value' => (object) array(
                'value'      => '1586',
                'bind_value' => array(
                    (object) array('bind_value_id' => 2331, 'bind_value_label' => '')
                )
            )
        );
        $this->assertEqual(2331, $this->field->getFieldDataFromSoapValue($soap_value));
    }
}
?>
