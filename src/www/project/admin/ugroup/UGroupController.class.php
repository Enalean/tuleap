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
    private $ugroup;
    private $ugroup_binding;

    public function __construct(Codendi_Request $request, UGroup $ugroup) {
        $this->request = $request;
        $this->ugroup_manager = new UGroupManager();
        $this->ugroup = $ugroup;
        $this->ugroup_binding = new UGroupBinding(new UGroupUserDao(), $this->ugroup_manager);
    }

    public function index() {
        $pane_management = new Project_Admin_UGroup_PaneManagement(
            $this->ugroup,
            $this->request,
            $this->ugroup_manager
        );
        $pane_management->display();
    }

    public function add_binding() {
        $historyDao        = new ProjectHistoryDao();
        $projectSourceId   = $this->request->getValidated('source_project', 'GroupId');
        $sourceId          = $this->request->get('source_ugroup');
        $validSourceUgroup = $this->ugroup_manager->checkUGroupValidityByGroupId($projectSourceId, $sourceId);
        $projectSource     = ProjectManager::instance()->getProject($projectSourceId);
        if ($validSourceUgroup && $projectSource->userIsAdmin()) {
            if ($this->ugroup_binding->addBinding($this->ugroup->getId(), $sourceId)) {
                $historyDao->groupAddHistory("ugroup_add_binding", $this->ugroup->getId().":".$sourceId, $this->ugroup->getProjectId());
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_ugroup_binding', 'add_error'));
        }
        $this->index();
    }

    public function remove_binding() {
        $historyDao        = new ProjectHistoryDao();
        if ($this->ugroup_binding->removeBinding($this->ugroup->getId())) {
            $historyDao->groupAddHistory("ugroup_remove_binding", $this->ugroup->getId(), $this->ugroup->getProjectId());
        }
        $this->index();
    }
}

?>
