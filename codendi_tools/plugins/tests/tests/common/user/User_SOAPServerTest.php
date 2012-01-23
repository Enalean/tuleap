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
    private $admin_session_hash = 'ghjghjgj';
    private $user_name = 'johan';
    private $userSessionsHash = 'qmslgfjqmsljfhqsmljhqziojfijhzr';

    
    public function testOpenSessionForUser() {
        $userManager = new MockUserManager();

        $user = new MockUser();
        $user->setReturnValue('isSuperUser', true);
        $userManager->setReturnValue('getCurrentUser', $user, array($this->admin_session_hash));
        
        $userManager->setReturnValue('login', $this->userSessionsHash, array($this->user_name));

        $server = new User_SOAPServer($userManager);
        $this->assertEqual($this->userSessionsHash, $server->loginAs($this->user_name, $this->admin_session_hash));
    }
    
    public function testReturnsSoapFaultIfAdminSessionHashNotValid() {
        $userManager = new MockUserManager();
        
        $user = new MockUser();
        $user->setReturnValue('isSuperUser', false);
        $userManager->setReturnValue('getCurrentUser', $user, array($this->admin_session_hash));
        
        $this->expectException('SoapFault');
        $server = new User_SOAPServer($userManager);
        $server->loginAs($this->user_name, $this->admin_session_hash);
    }
}

?>