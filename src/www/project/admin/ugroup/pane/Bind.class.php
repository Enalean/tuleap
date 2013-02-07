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


class Project_Admin_UGroup_Pane_Bind extends Project_Admin_UGroup_Pane {
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(UGroup $ugroup, UGroupManager $ugroup_manager) {
        parent::__construct($ugroup);
        $this->ugroup_manager = $ugroup_manager;
    }

    public function getContent() {
        $content = '';
        $urlAdd     = '/project/admin/editugroup.php?group_id='.$this->ugroup->getProjectId().'&ugroup_id='.$this->ugroup->getId().'&func=edit&pane=ugroup_binding';
        $linkAdd    = '<br/><a href="'.$urlAdd.'">- '.$GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding_title').'</a><br/>';
        if ($binding = $this->ugroup_manager->displayUgroupBinding($this->ugroup->getProjectId(), $this->ugroup->getId())) {
            $content .= $binding;
        } else {
            $GLOBALS['Response']->redirect('/project/admin/editugroup.php?group_id='.$this->ugroup->getProjectId().'&ugroup_id='.$this->ugroup->getId().'&func=edit&pane=ugroup_binding');
        }
        $content .= $linkAdd;
        return $content;
    }
}

?>
