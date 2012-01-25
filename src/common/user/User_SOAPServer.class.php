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

    private $userManager;

    public function __construct(UserManager $userManager) {
        $this->userManager = $userManager;
    }
    
   /**
    * loginAs allows the siteadmin to log as someone else
    *
    * @param string $username
    * 
    * @return string a session hash
    */
    public function loginAs($username) {
        try {
            return $this->userManager->loginAs($username);
        } catch (UserNotAuthorizedException $e) {
            return new SoapFault('3300', 'Permission denied');
        } catch (UserNotExistException $e) {
            return new SoapFault('3301', 'User not exist');
        }catch (UserNotActiveException $e) {
            return new SoapFault('3302', 'User not active');
        } catch (SessionNotCreatedException $e) {
            return new SoapFault('3303', 'Temporary error creating a session, please try again in a couple of seconds');
        }
    }
    
  
}

?>
