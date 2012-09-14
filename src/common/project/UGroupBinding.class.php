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
         return false;
     }

    /**
     * Get title of the link to binding interface
     *
     * @param Integer $ugroupId Id of the user group
     *
     * @return String
     */
    public function getLinkTitle($ugroupId) {
        if ($this->isBinded($groupId)) {
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
        if ($func) {
            // @TODO: i18n
            $GLOBALS['Response']->addFeedback('info', 'Action performed');
        }
    }

    /**
     * The form that will be displayed to add/edit user group binding
     *
     * @param Integer $ugroupId      Id of the user group
     * @param Integer $sourceProject Id of the project from which the source ugroup may be taken
     *
     * @return String
     */
    public function getHTMLContent($ugroupId, $sourceProject = null) {
        $projects = UserManager::instance()->getCurrentUser()->getProjects(true);
        $projectSelect = '<select name="source_project" onchange="this.form.submit()" >';
        $projectSelect .= '<option value="" >'.$GLOBALS['Language']->getText('global', 'none').'</option>';
        foreach ($projects as $project) {
            $project = ProjectManager::instance()->getProject($project['group_id']);
            if ($project->userIsAdmin()) {
                $selected = '';
                if ($sourceProject == $project->getID()) {
                    $selected = 'selected="selected"';
                }
                $projectSelect .= '<option value="'.$project->getID().'" '.$selected.' >'.$project->getPublicName().'</option>';
            }
        }
        $projectSelect .= '</select>';
        if ($sourceProject) {
            $ugroups = ugroup_db_get_existing_ugroups($sourceProject);
            $ugroupSelect = '<select name="source_ugroup" >';
            while ($ugroup = db_fetch_array($ugroups)) {
                $ugroupSelect .= '<option value="'.$ugroup['ugroup_id'].'" >'.$ugroup['name'].'</option>';
            }
            $ugroupSelect .= '</select>';
        }
        $html = '<table>';
        // @TODO: i18n
        $html .= '<tr><td>Source project</td><td><form action="" method="post">'.$projectSelect.'</td>';
        $html .= '<td><noscript><input type="submit" value="Select Project"/></noscript></form></td></tr>';
        if ($sourceProject) {
            // @TODO: i18n
            $html .= '<tr><td>Source user group</td>';
            $html .= '<td><form action="" method="post"><input type="hidden" name="action" value="add_binding" />'.$ugroupSelect.'</td>';
            // @TODO: i18n
            $html .= '<td><input type="submit" value="Add binding"/></form</td></tr>';
        }
        $html .= '</table>';
        return $html;
    }

}

?>