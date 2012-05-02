<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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
require_once('www/project/admin/project_admin_utils.php');

/**
 * Change project short name (unix_group_name)
 *
 */
class SystemEvent_PROJECT_RENAME extends SystemEvent {

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
        list($group_id, $new_name) = $this->getParametersAsArray();
        $txt .= 'project: '. $this->verbalizeProjectId($group_id, $with_link).' new name: '.$new_name;
        return $txt;
    }

    /** 
     * Process stored event
     * 
     * @return Boolean
     */
    public function process() {
        list($group_id, $new_name) = $this->getParametersAsArray();

        $renameState = true;

        if (($project = $this->getProject($group_id))) {
            // Rename SVN   
            $backendSVN = $this->getBackend('SVN');
            if ($backendSVN->repositoryExists($project)) {
                if ($backendSVN->isNameAvailable($new_name)) {
                    if (!$backendSVN->renameSVNRepository($project, $new_name)) {
                        $this->error('Could not rename SVN repository (id:'.$project->getId().') from "'.$project->getUnixName().'" to "'.$new_name.'"');
                        $renameState = $renameState & false;
                    } else {
                        $backendSVN->setSVNApacheConfNeedUpdate();
                    }
                } else {
                    $this->error('Could not rename SVN repository: Name '.$new_name.' not available');
                    $renameState = $renameState & false;
                }
            }

            // Rename CVS
            $backendCVS = $this->getBackend('CVS');
            if ($backendCVS->repositoryExists($project)) {
                if ($backendCVS->isNameAvailable($new_name)) {
                    if (!$backendCVS->renameCVSRepository($project, $new_name)) {
                        $this->error('Could not rename CVS repository (id:'.$project->getId().') from "'.$project->getUnixName().'" to "'.$new_name.'"');
                        $renameState = $renameState & false;
                    } else {
                        $backendCVS->setCVSRootListNeedUpdate();
                    }
                } else {
                    $this->error('Could not rename CVS repository: Name '.$new_name.' not available');
                    $renameState = $renameState & false;
                }
            }

            // Rename system home/groups
            $backendSystem = $this->getBackend('System');
            if ($backendSystem->projectHomeExists($project)) {
                if ($backendSystem->isProjectNameAvailable($new_name)) {
                    if (!$backendSystem->renameProjectHomeDirectory($project, $new_name)) {
                        $this->error("Could not rename project home");
                        $renameState = $renameState & false;
                    } else {
                        // Need to update system group cache
                        $backendSystem->setNeedRefreshGroupCache();
                    }
                } else {
                    $this->error('Could not rename project home: Name '.$new_name.' not available');
                    $renameState = $renameState & false;
                }
            }

            // Rename system FRS
            if (!$backendSystem->renameFileReleasedDirectory($project, $new_name)) {
                $this->error('Could not rename FRS repository (id:'.$project->getId().') from "'.$project->getUnixName().'" to "'.$new_name.'"');
                $renameState = $renameState & false;
            }

            // Rename system FTP pub
            if (!$backendSystem->renameAnonFtpDirectory($project, $new_name)) {
                $this->error('Could not rename FTP repository (id:'.$project->getId().') from "'.$project->getUnixName().'" to "'.$new_name.'"');
                $renameState = $renameState & false;
            }

            // Update DB
            if (!$this->updateDB($project, $new_name)) {
                $this->error('Could not update Project (id:'.$project->getId().') from "'.$project->getUnixName().'" to "'.$new_name.'"');
                $renameState = $renameState & false;
            }

            // Add Hook for plugins
            $this->getEventManager()->processEvent(
                __CLASS__,
                array('project'   => $project,
                      'new_name'  => $new_name)
            );
        } else {
            $renameState = false;
        }

        if ($renameState) {
            $this->addProjectHistory('rename_done', $project->getUnixName(false).' :: '.$new_name, $project->getId());
            $this->done();
        } else {
            $this->addProjectHistory('rename_with_error', $project->getUnixName(false).' :: '.$new_name.' (event n°'.$this->getId().')', $project->getId());
        }

        return $renameState;
    }

    /**
     * Update database
     * 
     * @param Project $project  Project to update
     * @param String  $new_name New name
     * 
     * @return Boolean
     */
    protected function updateDB($project, $new_name) {
        $pm = ProjectManager::instance();
        return $pm->renameProject($project, $new_name);
    }

    /**
     * Wrapper for group_add_history
     * 
     * @param String  $field_name Event name
     * @param String  $old_value  Event value
     * @param Integer $group_id   Project id of the vent
     * 
     * @return Boolean
     */
    protected function addProjectHistory($field_name, $old_value, $group_id) {
        return group_add_history($field_name, $old_value, $group_id);
    }
}

?>
