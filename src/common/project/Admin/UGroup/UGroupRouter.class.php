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

class Project_Admin_UGroup_UGroupRouter {

    const DEFAULT_ACTION = 'settings';

    private $ugroup_manager;

    public function __construct() {
        $this->ugroup_manager = new UGroupManager();
    }

    public function process(Codendi_Request $request) {
        $action          = self::DEFAULT_ACTION;
        $current_pane    = $this->getPane($request);
        $ugroup          = $this->getUGroup($request);
        $pane_management = new Project_Admin_UGroup_PaneManagement($ugroup);
        $pane            = $pane_management->getPaneById($current_pane);
        $controller      = null;
        EventManager::instance()->processEvent(Event::PROJECT_ADMIN_UGROUP_ROUTER, array('request' => $request, 'pane' => $pane, 'ugroup' => $ugroup));
        switch ($current_pane) {
            case Project_Admin_UGroup_View_Binding::IDENTIFIER:
                $pane = $pane_management->getPaneById(Project_Admin_UGroup_View_Binding::IDENTIFIER);
                $controller   = new Project_Admin_UGroup_UGroupController_Binding($request, $ugroup, $pane);
                $action = $this->getBindingAction($request);
                break;
            case Project_Admin_UGroup_View_Members::IDENTIFIER:
                $pane = $pane_management->getPaneById(Project_Admin_UGroup_View_Members::IDENTIFIER);
                $controller = new Project_Admin_UGroup_UGroupController_Members($request, $ugroup, $pane);
                $action = $this->getMembersAction($request);
                break;
            default:
                $controller   = new Project_Admin_UGroup_UGroupController($request, $ugroup, $pane);
                $action = $current_pane;
                break;
        }
        $controller->$action();
    }

    private function getBindingAction($request) {
        $vAction = new Valid_WhiteList('action', array('add_binding', 'remove_binding', 'edit_binding'));
        $vAction->required();
        return $request->getValidated('action', $vAction, Project_Admin_UGroup_View_ShowBinding::IDENTIFIER);
    }

    private function getMembersAction($request) {
        $vAction = new Valid_WhiteList('action', array('edit_ugroup_members'));
        $vAction->required();
        return $request->getValidated('action', $vAction, Project_Admin_UGroup_View_Members::IDENTIFIER);
    }

    private function getPane($request) {
        $vPane = new Valid_WhiteList(
            'pane',
            array(
                Project_Admin_UGroup_View_Settings::IDENTIFIER,
                Project_Admin_UGroup_View_Members::IDENTIFIER,
                Project_Admin_UGroup_View_Permissions::IDENTIFIER,
                Project_Admin_UGroup_View_ShowBinding::IDENTIFIER,
                Project_Admin_UGroup_View_EditBinding::IDENTIFIER
            )
        );
        $vPane->required();
        return $request->getValidated('pane', $vPane, Project_Admin_UGroup_View_Settings::IDENTIFIER);
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
