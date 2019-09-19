<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

Mock::generate('UserManager');
Mock::generate('PFUser');
Mock::generate('UserDao');
Mock::generate('DataAccessResult');
Mock::generatePartial('UserHelper', 'UserHelperTestVersion', array('_getUserDao', '_getCurrentUserUsernameDisplayPreference', '_getUserManager', '_isUserNameNone'));

class UserHelperTest extends TuleapTestCase
{

    function testGetDisplayName()
    {
        $uh = new UserHelperTestVersion($this);
        $uh->setReturnValueAt(0, '_getCurrentUserUsernameDisplayPreference', 1);
        $uh->setReturnValueAt(1, '_getCurrentUserUsernameDisplayPreference', 2);
        $uh->setReturnValueAt(2, '_getCurrentUserUsernameDisplayPreference', 3);
        $uh->setReturnValueAt(3, '_getCurrentUserUsernameDisplayPreference', 4);
        $uh->setReturnValueAt(4, '_getCurrentUserUsernameDisplayPreference', 666);

        $uh->__construct();
        $this->assertEqual("user_name (realname)", $uh->getDisplayName("user_name", "realname"));

        $uh->__construct();
        $this->assertEqual("user_name", $uh->getDisplayName("user_name", "realname"));

        $uh->__construct();
        $this->assertEqual("realname", $uh->getDisplayName("user_name", "realname"));

        $uh->__construct();
        $this->assertEqual("realname (user_name)", $uh->getDisplayName("user_name", "realname"));

        $uh->__construct();
        $this->assertEqual("realname (user_name)", $uh->getDisplayName("user_name", "realname"));
    }

    function testGetDisplayNameFromUser()
    {
        $user = mock('PFUser');
        $user->setReturnValue('getUserName', 'user_name');
        $user->setReturnValue('getRealName', 'realname');

        $uh = new UserHelperTestVersion($this);
        $uh->setReturnValue('_getCurrentUserUsernameDisplayPreference', 1);
        $uh->__construct();
        $this->assertEqual("user_name (realname)", $uh->getDisplayNameFromUser($user));
        $this->assertNull($uh->getDisplayNameFromUser(null));
    }

    function testGetDisplayNameFromUserId()
    {
        $user = mock('PFUser');
        $user->setReturnValue('getUserName', 'user_name');
        $user->setReturnValue('getRealName', 'realname');

        $um = new MockUserManager();
        $um->setReturnValue('isUserLoadedById', true, array(123));
        $um->setReturnReference('getUserById', $user, array(123));

        $uh = new UserHelperTestVersion($this);
        $uh->setReturnValue('_getCurrentUserUsernameDisplayPreference', 1);
        $uh->setReturnValue('_getUserManager', $um);
        $uh->__construct();
        $this->assertEqual("user_name (realname)", $uh->getDisplayNameFromUserId(123));
    }

    function testGetDisplayNameFromUserName()
    {
        $user = mock('PFUser');
        $user->setReturnValue('getUserName', 'user_name');
        $user->setReturnValue('getRealName', 'realname');

        $um = new MockUserManager();
        $um->setReturnValue('isUserLoadedByUserName', true, array('user_name'));
        $um->setReturnReference('getUserByUserName', $user, array('user_name'));

        $uh = new UserHelperTestVersion($this);
        $uh->setReturnValue('_isUserNameNone', false);
        $uh->setReturnValue('_getCurrentUserUsernameDisplayPreference', 1);
        $uh->setReturnValue('_getUserManager', $um);
        $uh->__construct();
        $this->assertEqual("user_name (realname)", $uh->getDisplayNameFromUserName('user_name'));
    }

    function testGetDisplayNameForNone()
    {
        $user = mock('PFUser');
        $user->setReturnValue('isNone', true);
        $user->setReturnValue('getUserName', 'None');
        $user->setReturnValue('getRealName', '0');

        $um = new MockUserManager();
        $um->setReturnValue('isUserLoadedById', true, array(100));
        $um->setReturnReference('getUserById', $user, array(100));

        $uh = new UserHelperTestVersion($this);
        $uh->setReturnValue('_getUserManager', $um);
        $uh->setReturnValue('_isUserNameNone', true, array('None'));
        $uh->setReturnValue('_isUserNameNone', true, array('Aucun'));
        $uh->setReturnValue('_getCurrentUserUsernameDisplayPreference', 4);
        $uh->setReturnValueAt(0, '_getCurrentUserUsernameDisplayPreference', 1);
        $uh->setReturnValueAt(1, '_getCurrentUserUsernameDisplayPreference', 2);
        $uh->setReturnValueAt(2, '_getCurrentUserUsernameDisplayPreference', 3);

        $uh->__construct();
        $this->assertEqual("None", $uh->getDisplayNameFromUser($user));

        $uh->__construct();
        $this->assertEqual("None", $uh->getDisplayNameFromUser($user));

        $uh->__construct();
        $this->assertEqual("None", $uh->getDisplayNameFromUser($user));

        $uh->__construct();
        $this->assertEqual("None", $uh->getDisplayNameFromUser($user));
        $this->assertEqual("None", $uh->getDisplayNameFromUserId(100));
        $this->assertEqual("None", $uh->getDisplayNameFromUserName("None"));
        $this->assertEqual("Aucun", $uh->getDisplayNameFromUserName("Aucun"));
    }

    function testInternalCachingById()
    {
        $dao = new MockUserDao($this);
        $dar = new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserId', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('user_name' => 'user_name', 'realname' => 'realname', 'user_id' => 123));
        $dar->setReturnValueAt(1, 'getRow', false);

        $dao->expectNever('searchByUserName', 'User should be cached');

        $um = new MockUserManager();
        $um->setReturnValue('isUserLoadedById', false, array(123));
        $um->setReturnValue('isUserLoadedByUserName', false, array('user_name'));
        $um->expectNever('getUserById');
        $um->expectNever('getUserByUserName');

        $uh = new UserHelperTestVersion($this);
        $uh->setReturnValue('_getUserManager', $um);
        $uh->setReturnValue('_isUserNameNone', false);
        $uh->setReturnValue('_getCurrentUserUsernameDisplayPreference', 1);
        $uh->setReturnReference('_getUserDao', $dao);

        $uh->__construct();
        $this->assertEqual("user_name (realname)", $uh->getDisplayNameFromUserId(123));
        $this->assertEqual("user_name (realname)", $uh->getDisplayNameFromUserName('user_name'));
    }
    function testInternalCachingByUserName()
    {
        $dao = new MockUserDao($this);
        $dar = new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserName', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('user_name' => 'user_name', 'realname' => 'realname', 'user_id' => 123));
        $dar->setReturnValueAt(1, 'getRow', false);

        $dao->expectNever('searchByUserId', 'User should be cached');

        $um = new MockUserManager();
        $um->setReturnValue('isUserLoadedById', false, array(123));
        $um->setReturnValue('isUserLoadedByUserName', false, array('user_name'));
        $um->expectNever('getUserById');
        $um->expectNever('getUserByUserName');

        $uh = new UserHelperTestVersion($this);
        $uh->setReturnValue('_getUserManager', $um);
        $uh->setReturnValue('_isUserNameNone', false);
        $uh->setReturnValue('_getCurrentUserUsernameDisplayPreference', 1);
        $uh->setReturnReference('_getUserDao', $dao);

        $uh->__construct();
        $this->assertEqual("user_name (realname)", $uh->getDisplayNameFromUserName('user_name'));
        $this->assertEqual("user_name (realname)", $uh->getDisplayNameFromUserId(123));
    }

    function itCachesUnknownNames()
    {
        $name = "L'équipe de développement de PhpWiki";

        $dao = mock('UserDao');
        stub($dao)->searchByUserName($name)->returnsEmptyDar();

        $um = mock('UserManager');
        stub($um)->isUserLoadedByUserName($name)->returns(false);

        $uh = new UserHelperTestVersion();
        stub($uh)->_getUserManager()->returns($um);
        stub($uh)->_isUserNameNone()->returns(false);
        stub($uh)->_getCurrentUserUsernameDisplayPreference()->returns(1);
        stub($uh)->_getUserDao()->returns($dao);

        $uh->__construct();
        $this->assertEqual($uh->getDisplayNameFromUserName($name), $name);
    }
}
