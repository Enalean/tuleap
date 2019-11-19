<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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
    'Tracker_FormElement_Field_List_Bind_UsersValue',
    'Tracker_FormElement_Field_List_Bind_UsersValueTestVersion',
    array('getUserHelper', 'getUserManager', 'getId')
);

Mock::generate('UserHelper');

Mock::generate('UserManager');

Mock::generate('PFUser');

class Tracker_FormElement_Field_List_Bind_UsersValueTest extends TuleapTestCase
{

    public function testGetLabel()
    {
        $uh = new MockUserHelper();
        $uh->setReturnValue('getDisplayNameFromUserId', 'John Smith', array(123));

        $bv = new Tracker_FormElement_Field_List_Bind_UsersValueTestVersion();
        $bv->setReturnValue('getId', 123);
        $bv->setReturnReference('getUserHelper', $uh);

        $this->assertEqual($bv->getLabel(), 'John Smith');
    }

    public function testGetUser()
    {
        $u = mock('PFUser');

        $uh = new MockUserManager();
        $uh->setReturnValue('getUserById', $u, array(123));

        $bv = new Tracker_FormElement_Field_List_Bind_UsersValueTestVersion();
        $bv->setReturnValue('getId', 123);
        $bv->setReturnReference('getUserManager', $uh);

        $this->assertEqual($bv->getUser(), $u);
    }
}

class Tracker_FormElement_Field_List_Bind_UsersValue_fetchJSONTest extends TuleapTestCase
{
    public $user_manager;
    public $user;

    public function setUp()
    {
        parent::setUp();
        $this->user_manager = mock('UserManager');
        $this->user         = mock('PFUser');
        UserManager::setInstance($this->user_manager);

        stub($this->user)->getRealName()->returns('Le roi arthur');
        stub($this->user_manager)->getUserById()->returns($this->user);
    }

    public function tearDown()
    {
        UserManager::clearInstance();
        parent::tearDown();
    }


    public function itReturnsTheUserNameAsWell()
    {
        $value = new Tracker_FormElement_Field_List_Bind_UsersValue(12, 'neo', 'Thomas A. Anderson (neo)');
        $json = $value->fetchFormattedForJson();
        $this->assertEqual(
            $json,
            [
                'id'         => '12',
                'label'      => 'Thomas A. Anderson (neo)',
                'is_hidden'  => false,
                'username'   => 'neo',
                'realname'   => 'Le roi arthur',
                'avatar_url' => ''
            ]
        );
    }

    public function itReturnsNullForGetJsonIfUserIsNone()
    {
        $value = new Tracker_FormElement_Field_List_Bind_UsersValue(100, 'none', 'none');
        $json = $value->getJsonValue();
        $this->assertNull($json);
    }
}
