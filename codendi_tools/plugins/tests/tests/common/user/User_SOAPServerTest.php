<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/user/User_SOAPServer.class.php';

Mock::generate('UserManager');
Mock::generate('User');

class User_SOAPServerTest extends UnitTestCase {
    public function testReturnsSoapFaultsWhenUserManagerThrowsAnException() {
        $this->givenAUserManagerThatIsProgrammedToThrow(new User_Not_Authorized())
                ->thenLoginAsReturns(new SoapFault('3300', 'Permission denied'));
        $this->givenAUserManagerThatIsProgrammedToThrow(new User_Not_In_Order())
                ->thenLoginAsReturns(new SoapFault('3301', 'User not active'));
        $this->givenAUserManagerThatIsProgrammedToThrow(new Session_Not_Created())
                ->thenLoginAsReturns(new SoapFault('3302', 'Temporary error creating a session, please try again in a couple of seconds'));
    }

    private function givenAUserManagerThatIsProgrammedToThrow($exception) {
        $um = new MockUserManager();
        $um->throwOn('loginAs', $exception);
        $server = new User_SOAPServer($um);
        return new UserManagerAsserter($server, $this);
    }
}
class UserManagerAsserter {
    public function __construct(User_SOAPServer $server, $asserter) {
        $this->server = $server;
        $this->asserter = $asserter;
    }
    public function thenLoginAsReturns($expected) {
        $this->asserter->assertIdentical($this->server->loginAs(null, null), $expected);
        
    }
    
}

?>