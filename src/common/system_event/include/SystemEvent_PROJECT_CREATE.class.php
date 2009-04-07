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
class SystemEvent_PROJECT_CREATE extends SystemEvent {
    
    /** 
     * Process stored event
     */
    function process() {
        // Check parameters
        $group_id=$this->getIdFromParam($this->parameters);
        
        if ($project = $this->getProject($group_id)) {
            $backendSystem = BackendSystem::instance();
            if (!$backendSystem->createProjectHome($group_id)) {
                $this->error("Could not create project home");
                return false;
            }
            
            
            if ($project->usesCVS()) {
                $backendCVS    = BackendCVS::instance();
                if (!$backendCVS->createProjectCVS($group_id)) {
                    $this->error("Could not create/initialize project CVS repository");
                    return false;
                }
                $backendCVS->setCVSRootListNeedUpdate();
                $backendCVS->setCVSPrivacy($project, !$project->isPublic() || $project->isCVSPrivate());
            }
            
            if ($project->usesSVN()) {
                $backendSVN    = BackendSVN::instance();
                if (!$backendSVN->createProjectSVN($group_id)) {
                    $this->error("Could not create/initialize project SVN repository");
                    return false;
                }
                $backendSVN->setSVNApacheConfNeedUpdate();
                $backendSVN->setSVNPrivacy($project, !$project->isPublic());
            }

            $backendSystem->log("Project ".$project->getUnixName()." created");            
            $this->done();
            return true;
        }
        return false;
    }

}

?>
