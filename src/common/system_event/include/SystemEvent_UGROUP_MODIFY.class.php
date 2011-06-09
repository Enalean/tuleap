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
* UGROUP_MODIFY = one static ugroup of the project has been modified
*/
class SystemEvent_UGROUP_MODIFY extends SystemEvent {
    
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
        list($group_id, $ugroup_id) = $this->getParametersAsArray();
        $txt .= 'project: '. $this->verbalizeProjectId($group_id, $with_link) .', ugroup: #'. $ugroup_id;
        return $txt;
    }
    
    /** 
     * Process stored event
     */
    function process() {
        // Check parameters
        if(count($this->getParametersAsArray()) == 4) {
            list($group_id,$ugroup_id,$ugroup_name,$ugroup_old_name) = $this->getParametersAsArray();
        } else {
            list($group_id, $ugroup_id) = $this->getParametersAsArray();
        }
        if ($project = $this->getProject($group_id)) {
            // Update SVN access file
            if ($project->usesSVN()) {
                $backendSVN = Backend::instance('SVN');
                if (!empty($ugroup_name)){
                    if (!$backendSVN->updateSVNAccess($group_id, $ugroup_name, $ugroup_old_name)) {
                        $this->error("Could not update SVN access file ($group_id)");
                        return false;
                    }
                } else {
                    if (!$backendSVN->updateSVNAccess($group_id)) {
                        $this->error("Could not update SVN access file ($group_id)");
                        return false;
                    }
                }
            }
        }

        $this->done();
        return true;
    }

}

?>
