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
require_once 'PaneManagement.class.php';

class Project_Admin_UGroup_UGroupController {
    private $request;
    private $ugroup_manager;

    public function __construct(Codendi_Request $request) {
        $this->request = $request;
        $this->ugroup_manager = new UGroupManager();
    }

    public function index() {
        $ugroup = $this->getUGroup();
        $pane_management = new Project_Admin_UGroup_PaneManagement(
            $ugroup,
            $this->request,
            $this->ugroup_manager
        );
        $pane_management->display();
    }

    private function getUGroup() {
        $ugroup_id = $this->request->getValidated('ugroup_id', 'uint', 0);
        if (!$ugroup_id) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), 'The ugroup ID is missing');
        }
        $ugroup = $this->ugroup_manager->getById($ugroup_id);

        if (!$ugroup) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('project_admin_editugroup', 'ug_not_found', array($ugroup_id, db_error())));
        }
        return $ugroup;
    }
}

?>
