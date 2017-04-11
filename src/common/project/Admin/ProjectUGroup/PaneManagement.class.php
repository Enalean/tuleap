<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

/**
 * Class to create panes and display menu with them
 */

class Project_Admin_UGroup_PaneManagement {

    /**
     * @var array
     */
    private $panes = array();

    /**
     * @var Project_Admin_UGroup_View
     */
    private $view;

    /**
     * @var ProjectUGroup
     */
    private $ugroup;

    public function __construct(ProjectUGroup $ugroup, Project_Admin_UGroup_View $view = null) {
        $this->ugroup       = $ugroup;
        $this->view         = $view;
        $this->panes = array(
            Project_Admin_UGroup_View_Settings::IDENTIFIER => new Project_Admin_UGroup_PaneInfo(
                $ugroup,
                Project_Admin_UGroup_View_Settings::IDENTIFIER,
                $GLOBALS['Language']->getText('global', 'settings')
            ),
            Project_Admin_UGroup_View_Members::IDENTIFIER => new Project_Admin_UGroup_PaneInfo(
                $ugroup,
                Project_Admin_UGroup_View_Members::IDENTIFIER,
                $GLOBALS['Language']->getText('admin_grouplist', 'members')
            ),
            Project_Admin_UGroup_View_Permissions::IDENTIFIER => new Project_Admin_UGroup_PaneInfo(
                $ugroup,
                Project_Admin_UGroup_View_Permissions::IDENTIFIER,
                $GLOBALS['Language']->getText('project_admin_utils', 'event_permission')
            ),
            Project_Admin_UGroup_View_Binding::IDENTIFIER => new Project_Admin_UGroup_PaneInfo(
                $ugroup,
                Project_Admin_UGroup_View_Binding::IDENTIFIER,
                $GLOBALS['Language']->getText('project_admin_utils', 'ugroup_binding')
            ),
        );
    }

    /**
     * Output repo management sub screen to the browser
     */
    public function display() {
        project_admin_header(array(
            'title' => $GLOBALS['Language']->getText('project_admin_editugroup', 'edit_ug'),
            'group' => $this->ugroup->getProjectId(), 'help' => 'project-admin.html#creating-a-user-group'));
        echo '<h1><a href="/project/admin/ugroup.php?group_id='.$this->ugroup->getProjectId().'">'.
                $GLOBALS['Language']->getText('project_admin_utils','ug_admin').
                '</a> - '.$this->ugroup->getName().'</h1>';
        echo '<div class="tabbable">';
        echo '<ul class="nav nav-tabs">';
        foreach ($this->panes as $key => $pane) {
            $this->displayTab($pane);
        }
        echo '</ul>';
        echo '<div class="tab-content">';
        echo '<div class="tab-pane active">';
        echo $this->view->getContent();
        echo '</div>';
        echo '</div>';
        $GLOBALS['HTML']->footer(array());
    }

    /**
     *
     * @param type $id
     * @return Project_Admin_UGroup_PaneInfo object if exists, false if not
     */
    public function getPaneById($id) {
        if ($this->panes[$id]) {
            return $this->panes[$id];
        } else {
            return false;
        }
    }

    private function displayTab($pane) {
        echo '<li class="'. ($this->view->getIdentifier() == $pane->getIdentifier() ? 'active' : '') .'">';
        echo '<a href="'. $pane->getUrl() .'">'. $pane->getTitle() .'</a></li>';
    }
}

?>
