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
class SystemEvent_MEMBERSHIP_CREATE extends SystemEvent {
    
    /** 
     * Process stored event
     */
    function process() {
        list($group_id,$user_id) = $this->getParametersAsArray();
        
        if ($project = $this->getProject($group_id)) {
            if ($user_id == 0) {
                return $this->setErrorBadParam();
            }
            
            // CVS writers
            if ($project->usesCVS()) {
                if (!BackendCVS::instance()->updateCVSwriters($group_id)) {
                    $this->error("Could not update CVS writers for group $group_id");
                    return false;
                }
            }
            
            // SVN access file
            if ($project->usesSVN()) {
                $backendSVN = BackendSVN::instance();
                if (!$backendSVN->updateSVNAccess($group_id)) {
                    $this->error("Could not update SVN access file ($group_id)");
                    return false;
                }
            }
            
            $this->done();
            return true;
        }
        return false;
    }
}

?>
