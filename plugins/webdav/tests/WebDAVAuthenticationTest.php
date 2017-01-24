<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

require_once 'bootstrap.php';

Mock::generate('BaseLanguage');
Mock::generate('PFUser');
Mock::generatePartial(
    'WebDAVAuthentication',
    'WebDAVAuthenticationTestVersion',
array('issetUsername', 'setHeader', 'getUsername', 'getPassword', 'getUser', 'errorWrongInput')
);

/**
 * This is the unit test of WebDAVAuthentication
 */
class WebDAVAuthenticationTest extends TuleapTestCase {


    public function setUp() {
        parent::setUp();
        ForgeConfig::store();
    }

    public function tearDown() {
        ForgeConfig::restore();
        parent::tearDown();
    }

    /**
     * Testing when user gives only the username
     */
    function testAuthenticateFailureWithOnlyUsername() {

        $webDAVAuthentication = new WebDAVAuthenticationTestVersion($this);
        $webDAVAuthentication->setReturnValue('issetUsername', true);
        $webDAVAuthentication->setReturnValue('getUsername', 'username');
        $webDAVAuthentication->setReturnValue('getPassword', null);
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);
        $webDAVAuthentication->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVAuthentication->authenticate(), null);

    }

    /**
     * Testing when the user gives only the password
     */
    function testAuthenticateFailureWithOnlyPassword() {

        $webDAVAuthentication = new WebDAVAuthenticationTestVersion($this);
        $webDAVAuthentication->setReturnValue('issetUsername', true);
        $webDAVAuthentication->setReturnValue('getUsername', null);
        $webDAVAuthentication->setReturnValue('getPassword', 'password');
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);
        $webDAVAuthentication->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVAuthentication->authenticate(), null);

    }

    /**
     * Testing when the user gives a wrong username or password
     */
    function testAuthenticateFailureWithWrongUsernameAndPassword() {

        $webDAVAuthentication = new WebDAVAuthenticationTestVersion($this);
        $webDAVAuthentication->setReturnValue('issetUsername', true);
        $webDAVAuthentication->setReturnValue('getUsername', 'username');
        $webDAVAuthentication->setReturnValue('getPassword', 'password');
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);
        $webDAVAuthentication->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVAuthentication->authenticate(), null);

    }

    /**
     * Testing when the user is authenticated as anonymous
     */
    function testAuthenticateSuccessWithAnonymousUserNotAllowed() {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $webDAVAuthentication = new WebDAVAuthenticationTestVersion($this);
        $webDAVAuthentication->setReturnValue('issetUsername', true);
        $webDAVAuthentication->setReturnValue('getUsername', null);
        $webDAVAuthentication->setReturnValue('getPassword', null);
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);
        $webDAVAuthentication->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVAuthentication->authenticate(), null);

    }

    /**
     * Testing when the user is authenticated as anonymous
     */
    function testAuthenticateSuccessWithAnonymousUserAllowed() {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $webDAVAuthentication = new WebDAVAuthenticationTestVersion($this);
        $webDAVAuthentication->setReturnValue('issetUsername', true);
        $webDAVAuthentication->setReturnValue('getUsername', null);
        $webDAVAuthentication->setReturnValue('getPassword', null);
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);
        $webDAVAuthentication->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVAuthentication->authenticate(), $user);

    }

    /**
     * Testing when the user is authenticated as a registered user
     */
    function testAuthenticateSuccessWithNotAnonymousUser() {

        $webDAVAuthentication = new WebDAVAuthenticationTestVersion($this);
        $webDAVAuthentication->setReturnValue('issetUsername', true);
        $webDAVAuthentication->setReturnValue('getUsername', 'username');
        $webDAVAuthentication->setReturnValue('getPassword', 'password');
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', false);
        $webDAVAuthentication->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVAuthentication->authenticate(), $user);

    }
}
