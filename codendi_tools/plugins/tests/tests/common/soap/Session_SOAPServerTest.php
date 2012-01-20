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

require_once 'common/soap/Session_SOAPServer.class.php';


class Session_SOAPServerTest extends UnitTestCase {
    
    public function openSessionForUser() {
        $admin_session_hash = 'ghjghjgj';
        $user_name = 'johan';
        
        $server = new Session_SOAPServer();
        
        $server->loginAs($admin_session_hash, $user_name);
    }
    
    //returnsSessionHashOfTheUser
    //returnsSoapFaultIfAdminSessionHashNotValid
    //returnsSoapFaultIfUserDoesNotExist
}

?>