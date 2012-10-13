<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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

require_once 'common/system_event/SystemEvent.class.php';


/**
 * Change User name 
 *
 */
class SystemEvent_USER_RENAME extends SystemEvent {

    /**
     * Set multiple logs
     *  
     * @param String $log Log string
     * 
     * @return void
     */
    public function setLog($log) {
        if (!isset($this->log) || $this->log == '') {
            $this->log = $log;
        } else {
            $this->log .= PHP_EOL.$log;
        }
    }

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
        list($user_id, $new_name) = $this->getParametersAsArray();
        $txt .= 'user: '. $this->verbalizeUserId($user_id, $with_link).' new name: '.$new_name;
        return $txt;
    }

    /** 
     * Process stored event
     * 
     * @return Boolean
     */
    public function process() {
        list($user_id, $new_name) = $this->getParametersAsArray();

        $renameState = true;
        if (($user = $this->getUser($user_id))) {
            //Rename home/users directory
            $backendSystem = $this->getBackend('System');
            if ($backendSystem->userHomeExists($user->getUserName())) {
                if ($backendSystem->isUserNameAvailable($new_name)) {
                    if (!$backendSystem->renameUserHomeDirectory($user, $new_name)) {
                        $this->error("Could not rename user home");
                        $renameState = $renameState & false;
                    }
                } else {
                    $this->error('Could not rename user home: Name '.$new_name.' not available');
                    $renameState = $renameState & false;
                }
            }
        
            // Update DB
            if (!$this->updateDB($user, $new_name)) {
                $this->error('Could not update User (id:'.$user->getId().') from "'.$user->getUserName().'" to "'.$new_name.'"');
                $renameState = $renameState & false;
            }
            
            //Rename CVS files
            $backendCVS = $this->getBackend('CVS');
            if (!$backendCVS->updateCVSWritersForGivenMember($user)) {
                $this->error('Could not update CVS writers for the user (id:'.$user->getId().')');
                $renameState = $renameState & false;
            }
            
            //Rename SVN files
            $backendSVN = $this->getBackend('SVN');
            if (!$backendSVN->updateSVNAccessForGivenMember($user)) {
                $this->error('Could not update SVN access files for the user (id:'.$user->getId().')');
                $renameState = $renameState & false;
            }
       
            $backendSystem->setNeedRefreshGroupCache();
            $backendSystem->setNeedRefreshUserCache();
        }
        
        if ($renameState) {
            $this->done();
        } 
        return $renameState;
    }
    
    
     /**
     * Update database
     * 
     * @param User    $user     User to update
     * @param String  $new_name New name
     * 
     * @return Boolean
     */
    protected function updateDB($user, $new_name) {
        $um = UserManager::instance();
        return $um->renameUser($user, $new_name);
    }
    

    
}
?>