<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 * 
 */


/**
* System Event classes
*
*/
class SystemEvent_USER_DELETE extends SystemEvent {
    
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
        $user_id=$this->getIdFromParam($this->parameters);

        if ($user_id == 0) {
            return $this->setErrorBadParam();
        }

        // Need to remove user alias
        Backend::instance('Aliases')->setNeedUpdateMailAliases();

        // Archive user home directory
        if (!Backend::instance('System')->archiveUserHome($user_id)) {
            $this->error("Could not archive user home");
            return false;
        }

        $this->done();
        return true;
    }

}

?>
