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
    protected $request;
    protected $ugroup_manager;
    protected $ugroup;
    protected $ugroup_binding;

    public function __construct(Codendi_Request $request, UGroup $ugroup) {
        $this->request = $request;
        $this->ugroup = $ugroup;
        $this->ugroup_manager = new UGroupManager();
        $this->ugroup_binding = new UGroupBinding(new UGroupUserDao(), $this->ugroup_manager);
    }

    protected function render($view) {
        $pane_management = new Project_Admin_UGroup_PaneManagement(
            $this->ugroup,
            $view
        );
        $pane_management->display();
    }

    public function settings() {
        $view = new Project_Admin_UGroup_View_Settings($this->ugroup);
        $this->render($view);
    }

    public function members() {
        $view = new Project_Admin_UGroup_View_Members($this->ugroup, $this->request, $this->ugroup_manager);
        $this->render($view);
    }

    public function permissions() {
        $view = new Project_Admin_UGroup_View_Permissions($this->ugroup);
        $this->render($view);
    }

    public function binding() {
        $controller_binding = new Project_Admin_UGroup_UGroupController_Binding($this->request, $this->ugroup);
        if ($binding = $controller_binding->displayUgroupBinding()) {
            $view = new Project_Admin_UGroup_View_ShowBinding($this->ugroup, $this->ugroup_binding, $binding, $controller_binding->getLdapPlugin());
            $this->render($view);
        } else {
            $controller_binding->edit_binding();
        }
    }
}

?>
