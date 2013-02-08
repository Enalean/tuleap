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
require_once 'UGroupController.class.php';

class Project_Admin_UGroup_UGroupRouter {
    const DEFAULT_ACTION = 'index';
    
    private $ugroup_manager;

    public function __construct() {
        $this->ugroup_manager = new UGroupManager();
    }

    public function process(Codendi_Request $request) {
        $action       = self::DEFAULT_ACTION;
        $current_pane = $this->getPane($request);
        switch($current_pane) {
            case Project_Admin_UGroup_Pane_Binding::IDENTIFIER:
                $vAction = new Valid_WhiteList('action', array('add_binding', 'remove_binding', 'edit_binding'));
                $vAction->required();
                $action = $request->getValidated('action', $vAction, Project_Admin_UGroup_Pane_Binding::IDENTIFIER);
                break;
        }
        if (!$action) {
            $action = self::DEFAULT_ACTION;
        }
        $ugroup       = $this->getUGroup($request);
        $controller   = new Project_Admin_UGroup_UGroupController($request, $ugroup, $current_pane);
        $controller->$action();
    }

    private function getPane($request) {
        $vPane = new Valid_WhiteList(
            'pane',
            array(
                Project_Admin_UGroup_Pane_Settings::IDENTIFIER,
                Project_Admin_UGroup_Pane_Members::IDENTIFIER,
                Project_Admin_UGroup_Pane_Permissions::IDENTIFIER,
                Project_Admin_UGroup_Pane_Binding::IDENTIFIER,
                Project_Admin_UGroup_Pane_UGroupBinding::IDENTIFIER
            )
        );
        $vPane->required();
        return $request->getValidated('pane', $vPane, Project_Admin_UGroup_Pane_Settings::IDENTIFIER);
    }

    private function getUGroup(Codendi_Request $request) {
        $ugroup_id = $request->getValidated('ugroup_id', 'uint', 0);
        if (!$ugroup_id) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), 'The ugroup ID is missing');
        }
        return $this->ugroup_manager->getById($ugroup_id);
    }
}

?>
