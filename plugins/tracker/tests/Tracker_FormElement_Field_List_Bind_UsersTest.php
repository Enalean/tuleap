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
    'Tracker_FormElement_Field_List_Bind_Users',
    'Tracker_FormElement_Field_List_Bind_UsersTestVersion',
    array(
        'getAllValues'
    )
);
Mock::generate('Tracker_FormElement_Field_List_Bind_UsersValue');
Mock::generate('Tracker_FormElement_Field_List');

Mock::generate('Tracker_Artifact_ChangesetValue_List');

Mock::generate('Tracker_FormElement_Field_List_Bind_UsersValue');


class Tracker_FormElement_Field_List_Bind_UsersTest extends UnitTestCase {
    
    public function testGetSoapAvailableValues() {
        $field = new MockTracker_FormElement_Field_List();
        $field->setReturnValue('getId', 123);
        $value_function = 'project_members';
        $default_values = $decorators = '';
        
        $users = new Tracker_FormElement_Field_List_Bind_Users($field, $value_function, $default_values, $decorators);
        
        $this->assertEqual(count($users->getSoapAvailableValues()), 1);
        $soap_values = array(
                        array('field_id' => 123,
                              'bind_value_id' => 0,
                              'bind_value_label' => 'project_members',
                             )
                        );
        $this->assertEqual($users->getSoapAvailableValues(), $soap_values);
    }
    
    public function testGetRecipients() {
        //$recipients = array();
        //foreach ($changeset_value->getBindValues() as $user_value) {
        //    $recipients[] = $user_value->getUser()->getUserName();
        //}
        //return $recipients;
        
        //$user1 = new MockUser(); $user1->setReturnValue('getUserName', 'u1');
        //$user2 = new MockUser(); $user2->setReturnValue('getUserName', 'u2');
        
        $changeset_value = new MockTracker_Artifact_ChangesetValue_List();
        $changeset_value->setReturnValue(
            'getListValues',
            array(
                $u1 = new MockTracker_FormElement_Field_List_Bind_UsersValue(),
                $u2 = new MockTracker_FormElement_Field_List_Bind_UsersValue(),
            )
        );
        //$u1->setReturnReference('getUser', $user1);
        //$u2->setReturnReference('getUser', $user2);
        $u1->setReturnValue('getUsername', 'u1');
        $u2->setReturnValue('getUsername', 'u2');
        
        $field = new MockTracker_FormElement_Field_List();
        $field->setReturnValue('getId', 123);
        $value_function = 'project_members';
        $default_values = $decorators = '';
        
        $users = new Tracker_FormElement_Field_List_Bind_Users($field, $value_function, $default_values, $decorators);
        $this->assertEqual($users->getRecipients($changeset_value), array('u1', 'u2'));
    }
    
    public function testFormatChangesetValueNoneValue() {
        $field = new Tracker_FormElement_Field_List_Bind_UsersTestVersion();
        $field2 = new Tracker_FormElement_Field_List_Bind_UsersTestVersion();
        $field3 = new Tracker_FormElement_Field_List_Bind_UsersTestVersion();
        $value = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $value2 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $value3 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $value->setReturnValue('getId', 100);
        $value->setReturnValue('fetchFormatted', 'None');
        $value2->setReturnValue('getId', 0);
        $value2->setReturnValue('fetchFormatted', 'SuperSuperAdmin');
        $value3->setReturnValue('getId', 123);
        $value3->setReturnValue('fetchFormatted', 'Bob.Johns');
        $value->expectNever('fetchFormatted');
        $value2->expectOnce('fetchFormatted');
        $value3->expectOnce('fetchFormatted');
        $this->assertEqual($field->formatChangesetValue($value), '');
        $this->assertNotEqual($field2->formatChangesetValue($value2), '');
        $this->assertNotEqual($field3->formatChangesetValue($value3), '');
    }

     public function testGetFieldDataReturnsMultiIds() {
        $field = new Tracker_FormElement_Field_List_Bind_UsersTestVersion();

        $soap_values = '12,13,14,15';
        $expected = array(12,13,14,15);

        $this->assertEqual($expected, $field->getFieldData($soap_values, true));
    }

    public function testGetFieldDataReturnsOneId() {

        $bv1 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv1->setReturnValue('getUsername', 'john.smith');
        $bv2 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv2->setReturnValue('getUsername', 'sam.anderson');
        $bv3 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv3->setReturnValue('getUsername', 'tom.brown');
        $bv4 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv4->setReturnValue('getUsername', 'patty.smith');
        $field_param = $is_rank_alpha = $default_values = $decorators = '';
        $values = array(12 => $bv1, 110 => $bv2, 113 => $bv3, 115 => $bv4);
        $field = new Tracker_FormElement_Field_List_Bind_UsersTestVersion($field_param, $is_rank_alpha, $values, $default_values, $decorators);
        $field->setReturnReference('getAllValues', $values);

        $soap_values = '12';
        $expected = 12;

        $this->assertEqual($expected, $field->getFieldData($soap_values, false));
    }
}
?>
