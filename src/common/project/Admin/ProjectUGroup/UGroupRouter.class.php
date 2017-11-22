<?php
/**
 * Copyright Enalean (c) 2011 - 2017. All rights reserved.
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

class Project_Admin_UGroup_UGroupRouter {

    private $ugroup_manager;

    public function __construct() {
        $this->ugroup_manager = new UGroupManager();
    }

    public function process(Codendi_Request $request) {
        $ugroup = $this->getUGroup($request);

        $controller = new Project_Admin_UGroup_UGroupController($request, $ugroup);
        $vAction = new Valid_WhiteList(
            'action',
            array('remove_binding', 'add_binding', 'edit_ugroup_members', 'ldap_remove_binding', 'ldap_add_binding')
        );
        $vAction->required();
        $action = $request->getValidated('action', $vAction, Project_Admin_UGroup_View_Settings::IDENTIFIER);

        $controller->$action();
    }

    private function getUGroup(Codendi_Request $request) {
        $ugroup_id = $request->getValidated('ugroup_id', 'uint', 0);
        if (!$ugroup_id) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), 'The ugroup ID is missing');
        }
        return $this->ugroup_manager->getById($ugroup_id);
    }
}
