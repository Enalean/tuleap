<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\User\SessionNotCreatedException;

Mock::generate('UserManager');

class User_SOAPServerTest extends TuleapTestCase {
    
    public function testLoginAsReturnsSoapFaultsWhenUserManagerThrowsAnException() {
        $this->GivenAUserManagerThatIsProgrammedToThrow(new UserNotAuthorizedException())
                ->thenLoginAsReturns(new SoapFault('3300', 'Permission denied. You must be site admin to loginAs someonelse'));
        $this->GivenAUserManagerThatIsProgrammedToThrow(new UserNotActiveException())
                ->thenLoginAsReturns(new SoapFault('3302', 'User not active'));
        $this->GivenAUserManagerThatIsProgrammedToThrow(new SessionNotCreatedException())
                ->thenLoginAsReturns(new SoapFault('3303', 'Temporary error creating a session, please try again in a couple of seconds'));
    }
    
    public function testLoginAsReturnsASessionHash() {
        $admin_session_hash = 'admin_session_hash';
        $um = $this->GivenAUserManagerWithValidAdmin($admin_session_hash);
        
        $user_soap_server      = new User_SOAPServer($um);
        $user_name             = 'toto';
        $expected_session_hash = 'expected_session_hash';
        
        
        $um->setReturnValue('loginAs', $expected_session_hash, array($user_name));
        $user_session_hash = $user_soap_server->loginAs($admin_session_hash, $user_name);
        $this->assertEqual($expected_session_hash, $user_session_hash);
    }
    
    /**
     * @return Mock
     */
    private function GivenAUserManagerWithValidAdmin($admin_session_hash) {        
        $adminUser = mock('PFUser');
        $adminUser->setReturnValue('isLoggedIn', true);
        
        $um = new MockUserManager();
        $um->setReturnValue('getCurrentUser', $adminUser, array($admin_session_hash));
        
        return $um;
    }
    
    public function testLoginAsReturnsASoapFaultIfUserNotLoggedIn() {
        $admin_session_hash = 'admin_session_hash';
        
        $user = mock('PFUser');
        $user->setReturnValue('isLoggedIn', false);
        
        $um = new MockUserManager();
        $um->setReturnValue('getCurrentUser', $user);
        
        $user_soap_server = new User_SOAPServer($um);
        $user_name        = 'toto';

        $this->expectException('SoapFault');
 
        $um->expectNever('loginAs');
        $user_soap_server->loginAs($admin_session_hash, $user_name);
    }
    
    private function GivenAUserManagerThatIsProgrammedToThrow($exception) {
        $adminUser = mock('PFUser');
        $adminUser->setReturnValue('isLoggedIn', true);
        
        $um = new MockUserManager();
        $um->setReturnValue('getCurrentUser', $adminUser);
        
        $um->throwOn('loginAs', $exception);
        $server = new User_SOAPServer($um);
        return new UserManagerAsserter($server, $this);
    }
}

class UserManagerAsserter {

    private $server;
    private $asserter;

    public function __construct(User_SOAPServer $server, TuleapTestCase $asserter) {
        $this->server   = $server;
        $this->asserter = $asserter;
    }
    
    public function thenLoginAsReturns(SoapFault $expected) {
        $returned = $this->server->loginAs(null, null);
        $this->asserter->assertIsA($returned, 'SoapFault');
        $this->asserter->assertEqual($returned->getCode(), $expected->getCode());
        $this->asserter->assertEqual($returned->getMessage(), $expected->getMessage());
    }
}
