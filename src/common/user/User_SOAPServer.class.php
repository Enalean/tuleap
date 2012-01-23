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

require_once 'UserManager.class.php';

class User_SOAPServer {

    const PERMISSION_DENIED = "3300";
    private $userManager;

    public function __construct(UserManager $userManager) {
        $this->userManager = $userManager;
    }
    
    public function loginAs($login_name, $admin_session_hash) {
        $user = $this->userManager->getCurrentUser($admin_session_hash);
        if ($user->isSuperUser()) {
            return $this->userManager->login($login_name);
        }
        throw new SoapFault(self::PERMISSION_DENIED, 'Permission denied');
    }
    
    /*public function loginAs($login_name, $admin_session_hash) {
        $user = $this->userManager->getCurrentUser($admin_session_hash);
        if ($user->isSuperUser()) {
            $log_as_user = $this->userManager->loginAs($login_name, $admin_session_hash);
            return $log_as_user->getSessionHash();
        }
        throw new SoapFault(self::PERMISSION_DENIED, 'Permission denied');
    }*/
}

?>
