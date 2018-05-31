<?php
/**
 * Copyright (c) Enalean, 2012-2018. All rights reserved.
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
 *
 * 
 */


/**
* System Event classes
*
*/
class SystemEvent_USER_CREATE extends SystemEvent {
    
    /**
     * Verbalize the parameters so they are readable and much user friendly in 
     * notifications
     * 
     * @param bool $with_link true if you want links to entities. The returned 
     * string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link) {
        $txt = '';
        $txt .= 'user: '. $this->verbalizeUserId($this->getIdFromParam($this->parameters), $with_link);
        return $txt;
    }
    
    /** 
     * Process stored event
     */
    function process() {
        // Check parameters
        $user_id = $this->getIdFromParam($this->parameters);
        $user    = UserManager::instance()->getUserById($user_id);
        if ($user && !$user->isAnonymous()) {
            if ($this->createUser($user)) {
                $this->done();
                return true;
            } else {
                $this->error("Could not create user home " . $user->getUserName() . " id: " . $user->getId());
                return false;
            }
        } else {
            return $this->setErrorBadParam();
        }
    }
    
    /**
     * Perform user creation on system
     * 
     * @param PFUser $user
     * 
     * @return Boolean
     */
    private function createUser(PFUser $user) {
        $system_backend = Backend::instance('System');
        $system_backend->flushNscdAndFsCache();
        return $system_backend->createUserHome($user);
    }

}

?>
