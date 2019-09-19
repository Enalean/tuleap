<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\Tracker\Import\Spotter;

require_once('bootstrap.php');

Mock::generatePartial(
    'Tracker_FormElement_Field_List_Bind_Users',
    'Tracker_FormElement_Field_List_Bind_UsersTestVersion',
    array(
        'getAllValues',
        'getAllValuesByUGroupList'
    )
);
Mock::generate('Tracker_FormElement_Field_List_Bind_UsersValue');
Mock::generate('Tracker_FormElement_Field_List');

Mock::generate('Tracker_Artifact_ChangesetValue_List');

Mock::generate('Tracker_FormElement_Field_List_Bind_UsersValue');


class Tracker_FormElement_Field_List_Bind_UsersTest extends TuleapTestCase
{

    public function tearDown()
    {
        parent::tearDown();
        UserManager::clearInstance();
        Spotter::clearInstance();
    }

    function testGetFieldData()
    {
        $bv1 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv1->setReturnValue('getUsername', 'john.smith');
        $bv2 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv2->setReturnValue('getUsername', 'sam.anderson');
        $field = $is_rank_alpha = $default_values = $decorators = '';
        $values = array(108 => $bv1, 110 => $bv2);
        $f = new Tracker_FormElement_Field_List_Bind_UsersTestVersion($field, $is_rank_alpha, $values, $default_values, $decorators);
        $f->setReturnReference('getAllValues', $values);
        $this->assertEqual('108', $f->getFieldData('john.smith', false));
    }

    function testGetFieldDataMultiple()
    {
        $bv1 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv1->setReturnValue('getUsername', 'john.smith');
        $bv2 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv2->setReturnValue('getUsername', 'sam.anderson');
        $bv3 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv3->setReturnValue('getUsername', 'tom.brown');
        $bv4 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv4->setReturnValue('getUsername', 'patty.smith');
        $field = $is_rank_alpha = $default_values = $decorators = '';
        $values = array(108 => $bv1, 110 => $bv2, 113 => $bv3, 115 => $bv4);
        $f = new Tracker_FormElement_Field_List_Bind_UsersTestVersion($field, $is_rank_alpha, $values, $default_values, $decorators);
        $f->setReturnReference('getAllValues', $values);
        $res = array(108,113);
        $this->assertEqual($res, $f->getFieldData('john.smith,tom.brown', true));
    }

    public function testGetRecipients()
    {
        //$recipients = array();
        //foreach ($changeset_value->getBindValues() as $user_value) {
        //    $recipients[] = $user_value->getUser()->getUserName();
        //}
        //return $recipients;

        //$user1 = mock('PFUser'); $user1->setReturnValue('getUserName', 'u1');
        //$user2 = mock('PFUser'); $user2->setReturnValue('getUserName', 'u2');

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

    public function testFormatChangesetValueNoneValue()
    {
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

    public function itVerifiesAValueExist()
    {
        $user_manager = mock('UserManager');
        stub($user_manager)->getUserById(101)->returns(mock('PFUser'));
        stub($user_manager)->getUserById(102)->returns(mock('PFUser'));
        UserManager::setInstance($user_manager);
        $field      = new MockTracker_FormElement_Field_List();
        $bind_users = partial_mock(
            'Tracker_FormElement_Field_List_Bind_Users',
            array('getAllValues'),
            array($field, '', '', '')
        );
        stub($bind_users)->getAllValues()->returns(array(101 => 'user1'));

        $this->assertTrue($bind_users->isExistingValue(101));
        $this->assertFalse($bind_users->isExistingValue(102));

        $import_spotter = mock('Tuleap\Tracker\Import\Spotter');
        stub($import_spotter)->isImportRunning()->returns(true);
        Spotter::setInstance($import_spotter);

        $this->assertTrue($bind_users->isExistingValue(101));
        $this->assertTrue($bind_users->isExistingValue(102));
    }
}
