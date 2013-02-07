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

class Project_Admin_UGroup_Pane_BindUsage extends Project_Admin_UGroup_Pane {
    const IDENTIFIER = 'usage';
    
    public function getContent() {
        $ugroupBinding = get_ugroup_binding();
        $bindingiewer  = new UGroupBindingViewer($ugroupBinding, ProjectManager::instance());
        return $bindingiewer->getUsagePaneContent($this->ugroup->getProjectId(), $this->ugroup->getId());
    }

    public function getIdentifier() {
        return self::IDENTIFIER;
    }

    public function getTitle() {
        return $GLOBALS['Language']->getText('global', 'usage');
    }

    public function getUrl() {
        return '/project/admin/editugroup.php?group_id='.$this->ugroup->getProjectId().'&ugroup_id='.$this->ugroup->getId().'&func=edit&pane=usage';
    }
}

?>
