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
        if (strpos($this->parameters,',' === FALSE)) {
            // Only one Group ID
            $txt .= 'project: '. $this->verbalizeProjectId($this->getIdFromParam($this->parameters), $with_link);
        } else {
            $txt .= 'projects: '. $this->parameters;
        }
        return $txt;
    }
    
    /** 
     * Process stored event
     */
    function process() {

        $groups=explode(',',$this->parameters);
        
        $backendSystem = Backend::instance('System');
        
        // Force NSCD flush (otherwise uid & gid will not exist)
        $backendSystem->flushNscdAndFsCache();
        
        foreach ($groups as $group_id) {
        
            if ($project = $this->getProject($group_id)) {
                
                if (!$backendSystem->createProjectHome($group_id)) {
                    $this->error("Could not create project home");
                    return false;
                }
            
            
                if ($project->usesCVS()) {
                    $backendCVS    = Backend::instance('CVS');
                    if (!$backendCVS->createProjectCVS($group_id)) {
                        $this->error("Could not create/initialize project CVS repository");
                        return false;
                    }
                    $backendCVS->setCVSRootListNeedUpdate();
                    $backendCVS->setCVSPrivacy($project, !$project->isPublic() || $project->isCVSPrivate());
                }
            
                if ($project->usesSVN()) {
                    $backendSVN    = Backend::instance('SVN');
                    if (!$backendSVN->createProjectSVN($group_id)) {
                        $this->error("Could not create/initialize project SVN repository");
                        return false;
                    }
                    $backendSVN->setSVNApacheConfNeedUpdate();
                    $backendSVN->setSVNPrivacy($project, !$project->isPublic());
                }
                $backendSystem->log("Project ".$project->getUnixName()." created");            
            }
        }


        $this->done();
        return true;
    }
}

?>
