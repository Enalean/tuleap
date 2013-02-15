<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Project_Admin_UGroup_UGroupController_Binding extends Project_Admin_UGroup_UGroupController {

    public function __construct(Codendi_Request $request, UGroup $ugroup, Project_Admin_UGroup_PaneInfo $pane) {
        parent::__construct($request, $ugroup);
        $this->pane       = $pane;
    }

        public function edit_binding() {
        $source_project_id = $this->request->getValidated('source_project', 'GroupId', 0);
        $view = new Project_Admin_UGroup_View_EditBinding($this->ugroup, $this->ugroup_binding, $source_project_id);
        $this->render($view);
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
        $this->redirect();
    }

    public function remove_binding() {
        $historyDao        = new ProjectHistoryDao();
        if ($this->ugroup_binding->removeBinding($this->ugroup->getId())) {
            $historyDao->groupAddHistory("ugroup_remove_binding", $this->ugroup->getId(), $this->ugroup->getProjectId());
        }
        $this->redirect();
    }

    /**
     * Display the binding pane content
     *
     * @return String
     */
    public function displayUgroupBinding() {
        $html = '';
        $ugroupUpdateUsersAllowed = !$this->ugroup->isBound();
        if ($ugroupUpdateUsersAllowed) {
            $em = EventManager::instance();
            $em->processEvent('ugroup_table_row', array('row' => array('group_id' => $this->ugroup->getProjectId(), 'ugroup_id' => $this->ugroup->getId()), 'html' => &$html));
        }
        return $html;
    }
}
?>
