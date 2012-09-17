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

    /**
     * Obtain UGroupDao
     *
     * @raturn UGroupDao
     */
    protected function getUGroupDao() {
        if (!$this->_ugroupdao) {
            $this->_ugroupdao = new UGroupDao();
        }
        return $this->_ugroupdao;
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
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
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
     * Get title of the link to binding interface
     *
     * @param Integer $ugroupId Id of the user group
     *
     * @return String
     */
    public function getLinkTitle($ugroupId) {
        if ($this->isBinded($ugroupId)) {
            // @TODO: i18n
            return '- Update binding';
        } else {
            // @TODO: i18n
            return '- Add binding';
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
        $func = $request->getValidated('action', new Valid_WhiteList('add_binding', 'remove_binding'), null);
        // @TODO: i18n, data validation
        switch($func) {
            case 'add_binding':
                $sourceId = $request->get('source_ugroup');
                if($this->getUGroupDao()->updateUgroupBinding($ugroupId, $sourceId)) {
                    $GLOBALS['Response']->addFeedback('info', 'Action performed');
                } else {
                    $GLOBALS['Response']->addFeedback('error', 'Something went wrong when binding user group');
                }
            break;
            case 'remove_binding':
                if($this->getUGroupDao()->updateUgroupBinding($ugroupId)) {
                    $GLOBALS['Response']->addFeedback('info', 'User group binding successfully removed');
                } else {
                    $GLOBALS['Response']->addFeedback('error', 'User group binding not removed');
                }
            break;
            default:
            break;
        }
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
        $pm = ProjectManager::instance();
        $dar = $this->getUGroupDao()->getUgroupBindingSource($ugroupId);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $ugroupManager  = new UGroupManager();
            $row            = $dar->getRow();
            $currentSource  = $ugroupManager->getById($row['source_id']);
            $currentProject = $pm->getProject($row['group_id']);
            if ($currentSource && $currentProject->userIsAdmin()) {
                // @TODO: i18n
                // @TODO: add links to ugroup & project
                $currentBindHTML = 'Binding is to '.$currentSource->getName().' in project '.$currentProject->getPublicName();
                if (!$sourceProject) {
                    $sourceProject = $currentProject->getID();
                }
            }
            // @TODO: i18n
            $currentBindHTML .= '<form action="" method="post"><input type="hidden" name="action" value="remove_binding" /><input type="submit" value="Remove current binding"/></form>';
        }
        $clones     = $this->getUGroupsByBindingSource($ugroupId);
        $clonesHTML = '<table>';
        if (!empty($clones)) {
            $count = 0;
            foreach ($clones as $cloneId => $clone) {
                $project = $pm->getProject($clone['group_id']);
                if ($project->userIsAdmin()) {
                    $clonesHTML .= '<tr><td>'.$clone['cloneName'].' in project '.$project->getPublicName().' is binded to this ugroup</td></tr>';
                } else {
                    $count ++;
                }
            }
            if ($count) {
                $clonesHTML .= '<tr><td>and '.$count.' other ugroups you\'re not allowed to administrate</td></tr>';
            }
        } else {
            $clonesHTML .= '<tr><td>This ugroup is not the source of any other ugroup</td></tr>';
        }
        $clonesHTML .= '</table>';
        $projects = UserManager::instance()->getCurrentUser()->getProjects(true);
        $projectSelect = '<select name="source_project" onchange="this.form.submit()" >';
        $projectSelect .= '<option value="" >'.$GLOBALS['Language']->getText('global', 'none').'</option>';
        foreach ($projects as $project) {
            if ($groupId != $project['group_id']) {
                $project = $pm->getProject($project['group_id']);
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
        if ($sourceProject) {
            $ugroups = ugroup_db_get_existing_ugroups($sourceProject);
            $ugroupSelect = '<select name="source_ugroup" >';
            $ugroupSelect .= '<option value="" >'.$GLOBALS['Language']->getText('global', 'none').'</option>';
            while ($ugroup = db_fetch_array($ugroups)) {
                $selected = '';
                if ($currentSource && $currentSource->getId() == $ugroup['ugroup_id']) {
                    $selected = 'selected="selected"';
                }
                $ugroupSelect .= '<option value="'.$ugroup['ugroup_id'].'" '.$selected.' >'.$ugroup['name'].'</option>';
            }
            $ugroupSelect .= '</select>';
        }
        // @TODO: i18n
        $html = '<h3>Current binding</h3>';
        $html .= $currentBindHTML;
        // @TODO: i18n
        $html .= '<h3>Ugroups binded to this one</h3>';
        $html .= $clonesHTML;
        // @TODO: i18n
        $html .= '<h3>Edit binding</h3>';
        $html .= '<table>';
        // @TODO: i18n
        $html .= '<tr><td>Source project</td><td><form action="" method="post">'.$projectSelect.'</td>';
        $html .= '<td><noscript><input type="submit" value="Select Project"/></noscript></form></td></tr>';
        if ($sourceProject) {
            // @TODO: i18n
            $html .= '<tr><td>Source user group</td>';
            $html .= '<td><form action="" method="post"><input type="hidden" name="action" value="add_binding" />'.$ugroupSelect.'</td>';
            // @TODO: i18n
            $html .= '<td><input type="submit" value="Edit binding"/></form></td></tr>';
        }
        $html .= '</table>';
        return $html;
    }

}

?>
