<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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
 */

require_once('common/dao/UGroupDao.class.php');
require_once('common/project/UGroupManager.class.php');

/**
 * UGroup object
 */
class UGroupBinding {

    protected $_ugroupdao;
    protected $_ugroupUserDao;

    /**
     * Obtain UGroupDao
     *
     * @return UGroupDao
     */
    protected function getUGroupDao() {
        if (!$this->_ugroupdao) {
            $this->_ugroupdao = new UGroupDao();
        }
        return $this->_ugroupdao;
    }

    /**
     * Obtain UGroupUserDao
     *
     * @return UGroupUserDao
     */
    protected function getUGroupUserDao() {
        if (!$this->_ugroupUserDao) {
            $this->_ugroupUserDao = new UGroupUserDao();
        }
        return $this->_ugroupUserDao;
    }

    /**
     * Check if the user group is binded
     *
     * @param Integer $ugroupId Id of the user goup
     *
     * @return Boolean
     */
    public function isBinded($ugroupId) {
        $dar = $this->getUGroupDao()->getUgroupBindingSource($ugroupId);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return  true;
        } else {
            return false;
        }
    }

    /**
     * Retrieve All Ugroups binded to a given Ugroup
     *
     * @param Integer $ugroupId Id of the source user goup
     *
     * @return Array
     */
    public function getUGroupsByBindingSource($ugroupId) {
        $dar = $this->getUGroupDao()->searchUGroupByBindingSource($ugroupId);
        $ugroups = array();
        if ($dar && !empty($dar) && !$dar->isError()) {
            foreach ($dar as $row) {
                $cloneId = $row['ugroup_id'];
                $ugroups[$cloneId]['cloneName'] = $row['name'];
                $ugroups[$cloneId]['group_id']  = $row['group_id'];
            }
        }
        return $ugroups;
    }

    /**
     * Remove all Ugroups binding to a given Ugroup
     *
     * @param Integer $ugroupId Id of the source user group
     *
     * @return boolean
     */
    public function removeAllUGroupsBinding($ugroupId) {
        $bindedUgroups  = $this->getUGroupsByBindingSource($ugroupId);
        $bindingRemoved = true;
        if (!empty($bindedUgroups)) {
            foreach ($bindedUgroups as $ugroupKey => $ugroupData) {
                if (!$this->getUGroupDao()->updateUgroupBinding($ugroupKey)) {
                    $bindingRemoved = false;
                }
            }
        }
        return $bindingRemoved;
    }

    /**
     * Remove binding to all ugroups of a given project
     *
     * @param Integer $groupId Id of the project
     *
     * @return boolean
     */
    public function removeProjectUGroupsBinding($groupId) {
        $ugroups        = ugroup_db_get_existing_ugroups($groupId);
        $bindingRemoved = true;
        while ($ugroup = db_fetch_array($ugroups)) {
            if (!$this->removeAllUGroupsBinding($ugroup['ugroup_id'])) {
                $bindingRemoved = false;
            }
        }
        return $bindingRemoved;
    }

    /**
     * Get title of the link to binding interface
     *
     * @param Integer $ugroupId Id of the user group
     *
     * @return String
     */
    public function getLinkTitle($ugroupId) {
        if ($this->isBinded($ugroupId)) {
            return '- '.$GLOBALS['Language']->getText('project_ugroup_binding', 'update_binding');
        } else {
            return '- '.$GLOBALS['Language']->getText('project_ugroup_binding', 'add_binding');
        }
    }

    /**
     * Perform actions on user group binding
     *
     * @param Integer         $ugroupId Id of the user group
     * @param Codendi_Request $request  the HTTP request
     *
     * @return Void
     */
    public function processRequest($ugroupId, Codendi_Request $request) {
        $func        = $request->getValidated('action', new Valid_WhiteList('add_binding', 'remove_binding'), null);
        $validUgroup = $this->getUGroupDao()->checkUGroupValidityByGroupId($request->get('group_id'), $ugroupId);
        if ($validUgroup) {
            switch($func) {
                case 'add_binding':
                    $sourceId = $request->get('source_ugroup');
                    if ($this->getUGroupDao()->updateUgroupBinding($ugroupId, $sourceId)) {
                        // @TODO: Clean up bind users, flash ugroup before cloning...
                        $bindUsers = $this->getUGroupUserDao()->cloneUgroup($sourceId, $ugroupId);

                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_ugroup_binding', 'binding_added'));
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_ugroup_binding', 'add_error'));
                    }
                    break;
                case 'remove_binding':
                    if ($this->getUGroupDao()->updateUgroupBinding($ugroupId)) {
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_ugroup_binding', 'binding_removed'));
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_ugroup_binding', 'remove_error'));
                    }
                    break;
                default:
                    break;
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_ugroup_binding', 'add_error'));
        }
    }

    /**
     * Get ProjectManager instance
     *
     * @return ProjectManager
     */
     private function _getProjectManager() {
         return ProjectManager::instance();
     }

    /**
     * The form that will be displayed to add/edit user group binding
     *
     * @param Integer $groupId       Id of the current project
     * @param Integer $ugroupId      Id of the user group
     * @param Integer $sourceProject Id of the project from which the source ugroup may be taken
     *
     * @return String
     */
    public function getHTMLContent($groupId, $ugroupId, $sourceProject = null) {
        $dar = $this->getUGroupDao()->getUgroupBindingSource($ugroupId);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $ugroupManager  = new UGroupManager();
            $row            = $dar->getRow();
            $currentSource  = $ugroupManager->getById($row['source_id']);
            $currentProject = $this->_getProjectManager()->getProject($row['group_id']);
            if ($currentProject && $currentProject->userIsAdmin()) {
                if (!$sourceProject) {
                    $sourceProject = $currentProject->getID();
                }
            }
        }
        $clones = $this->getUGroupsByBindingSource($ugroupId);
        $html = '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'current_binding').'</h3>';
        $html .= $this->getCurrentBindingHTML($currentProject, $currentSource);
        $html .= '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'binding_sources').'</h3>';
        $html .= $this->getClonesHTML($clones);
        $html .= '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding').'</h3>';
        $html .= '<table>';
        $html .= '<tr><td>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'source_project').'</td><td><form action="" method="post">'.$this->getProjectsSelect($groupId, $sourceProject).'</td>';
        $html .= '<td><noscript><input type="submit" value="Select Project"/></noscript></form></td></tr>';
        if ($sourceProject) {
            $html .= '<tr><td>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'source_ugroup').'</td>';
            $html .= '<td><form action="" method="post"><input type="hidden" name="action" value="add_binding" />'.$this->getUgroupSelect($sourceProject, $currentSource).'</td>';
            $html .= '<td><input type="submit" value="'.$GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding').'"/></form></td></tr>';
        }
        $html .= '</table>';
        return $html;
    }

    /**
     * Get the HTML output for current binding
     *
     * @param Project $currentProject Project of the currently binded ugroup
     * @param UGroup  $currentSource  Currently binded ugroup
     *
     * @return String
     */
    private function getCurrentBindingHTML(Project $currentProject = null, UGroup $currentSource = null) {
        if ($currentSource) {
            if ($currentSource && $currentProject->userIsAdmin()) {
                $currentBindHTML = $GLOBALS['Language']->getText('project_ugroup_binding', 'current_binded', array('<a href="/project/admin/ugroup.php?group_id='.$currentProject->getID().'" ><b>'.$currentSource->getName().'</b></a>', '<a href="/projects/'.$currentProject->getUnixName().'" ><b>'.$currentProject->getPublicName().'</b></a>'));
            }
            $currentBindHTML .= '<form action="" method="post"><input type="hidden" name="action" value="remove_binding" /><input type="submit" value="'.$GLOBALS['Language']->getText('project_ugroup_binding', 'remove_binding').'"/></form>';
        } else {
            $currentBindHTML .= $GLOBALS['Language']->getText('project_ugroup_binding', 'no_binding');
        }
        return $currentBindHTML;
    }

    /**
     * Get the HTML output for ugroups binded to the current one
     *
     * @param Array $clones List of ugroups binded to this one
     *
     * @return String
     */
    private function getClonesHTML($clones) {
        $clonesHTML = '<table>';
        if (!empty($clones)) {
            $clonesHTML .= html_build_list_table_top(array($GLOBALS['Language']->getText('project_reference', 'ref_scope_P'), 'UGroup'), false, false , false);
            $count      = 0;
            $i          = 0;
            foreach ($clones as $cloneId => $clone) {
                $project = $this->_getProjectManager()->getProject($clone['group_id']);
                if ($project->userIsAdmin()) {
                    $clonesHTML .= '<tr class="'. html_get_alt_row_color(++$i) .'"><td><a href="/projects/'.$project->getUnixName().'" >'.$project->getPublicName().'</a></td><td><a href="/project/admin/ugroup.php?group_id='.$project->getID().'" >'.$clone['cloneName'].'</a></td></tr>';
                } else {
                    $count ++;
                }
            }
            if ($count) {
                $clonesHTML .= '<tr class="'. html_get_alt_row_color(++$i) .'" colspan="2" ><td>and '.$count.' other ugroups you\'re not allowed to administrate</td></tr>';
            }
        } else {
            $clonesHTML .= '<tr><td>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'not_source').'</td></tr>';
        }
        $clonesHTML .= '</table>';
        return $clonesHTML;
    }

    /**
     * Get the HTML select listing the source projects
     *
     * @param Integer $groupId       Id of the project
     * @param Integer $sourceProject Id of the current soucrce project
     *
     * @return String
     */
    private function getProjectsSelect($groupId, $sourceProject) {
        $projects       = UserManager::instance()->getCurrentUser()->getProjects(true);
        $projectSelect  = '<select name="source_project" onchange="this.form.submit()" >';
        $projectSelect .= '<option value="" >'.$GLOBALS['Language']->getText('global', 'none').'</option>';
        foreach ($projects as $project) {
            if ($groupId != $project['group_id']) {
                $project = $this->_getProjectManager()->getProject($project['group_id']);
                if ($project->userIsAdmin()) {
                    $selected = '';
                    if ($sourceProject == $project->getID()) {
                        $selected = 'selected="selected"';
                    }
                    $projectSelect .= '<option value="'.$project->getID().'" '.$selected.' >'.$project->getPublicName().'</option>';
                }
            }
        }
        $projectSelect .= '</select>';
        return $projectSelect;
    }

    /**
     * Get the HTML select listing the source ugroups by project
     *
     * @param Integer $sourceProject Id of the current soucrce project
     * @param UGroup  $currentSource Currently binded ugroup
     *
     * @return String
     */
    private function getUgroupSelect($sourceProject, UGroup $currentSource = null) {
        $ugroups       = ugroup_db_get_existing_ugroups($sourceProject);
        $ugroupSelect  = '<select name="source_ugroup" >';
        $ugroupSelect .= '<option value="" >'.$GLOBALS['Language']->getText('global', 'none').'</option>';
        while ($ugroup = db_fetch_array($ugroups)) {
            if (!$this->isBinded($ugroup['ugroup_id'])) {
                $selected = '';
                if ($currentSource && $currentSource->getId() == $ugroup['ugroup_id']) {
                    $selected = 'selected="selected"';
                }
                $ugroupSelect .= '<option value="'.$ugroup['ugroup_id'].'" '.$selected.' >'.$ugroup['name'].'</option>';
            }
        }
        $ugroupSelect .= '</select>';
        return $ugroupSelect;
    }

}

?>
