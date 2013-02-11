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

class Project_Admin_UGroup_View_Binding extends Project_Admin_UGroup_View {
    const IDENTIFIER = 'binding';

    /**
     * @var UGroupBinding
     */
    protected $ugroup_binding;

    /**
     * @var ProjectManager
     */
    protected $project_manager;

    public function __construct(UGroup $ugroup, UGroupBinding $ugroup_binding) {
        parent::__construct($ugroup);
        $this->ugroup_binding = $ugroup_binding;
        $this->project_manager = ProjectManager::instance();
    }


    public function getContent() {
        return '';
    }

    public function getIdentifier() {
        return self::IDENTIFIER;
    }

    public function getTitle() {
        return $GLOBALS['Language']->getText('project_admin_utils', 'ugroup_binding');
    }

    public function getUrl() {
        return '/project/admin/editugroup.php?group_id='.$this->ugroup->getProjectId().'&ugroup_id='.$this->ugroup->getId().'&func=edit&pane='.self::IDENTIFIER;
    }
}

?>
