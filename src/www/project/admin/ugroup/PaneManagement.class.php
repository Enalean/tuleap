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

require_once 'Pane.class.php';
require_once 'pane/Settings.class.php';
require_once 'pane/Members.class.php';
require_once 'pane/Permissions.class.php';
require_once 'pane/Binding.class.php';
require_once 'pane/UGroupBinding.class.php';

class Project_Admin_UGroup_PaneManagement {
    /**
     * @var type 
     */
    private $panes = array();

    private $current_pane;
    
    /**
     * @var UGroup
     */
    private $ugroup;

    public function __construct(UGroup $ugroup, array $panes, $current_pane) {
        foreach ($panes as $pane) {
            $this->panes[$pane->getIdentifier()] = $pane;
        }
        $this->current_pane = $current_pane;
        $this->ugroup = $ugroup;
    }

    /**
     * Output repo management sub screen to the browser
     */
    public function display() {
        echo '<h1><a href="/project/admin/ugroup.php?group_id='.$this->ugroup->getProjectId().'">'.$GLOBALS['Language']->getText('project_admin_utils','ug_admin').'</a> - '.$this->ugroup->getName().' - '.$this->panes[$this->current_pane]->getTitle().'</h1>';
        echo '<div class="tabbable tabs-left">';
        echo '<ul class="nav nav-tabs">';
        foreach ($this->panes as $pane) {
            $this->displayTab($pane);
        }
        echo '</ul>';
        echo '<div class="tab-content">';
        echo '<div class="tab-pane active">';
        echo $this->panes[$this->current_pane]->getContent();
        echo '</div>';
        echo '</div>';
    }

    private function displayTab($pane) {
        echo '<li class="'. ($this->current_pane == $pane->getIdentifier() ? 'active' : '') .'">';
        echo '<a href="'. $pane->getUrl() .'">'. $pane->getTitle() .'</a></li>';
    }
}

?>
