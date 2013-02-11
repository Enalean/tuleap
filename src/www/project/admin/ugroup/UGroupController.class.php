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
        $this->ugroup = $ugroup;
        $this->ugroup_manager = new UGroupManager();
        $this->ugroup_binding = new UGroupBinding(new UGroupUserDao(), $this->ugroup_manager);
    }

    private function render($view) {
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

    public function edit_binding() {
        $source_project_id = $this->request->getValidated('source_project', 'GroupId', 0);
        $view = new Project_Admin_UGroup_View_EditBinding($this->ugroup, $this->ugroup_binding, $source_project_id);
        $this->render($view);
    }

    public function binding() {
        if ($binding = $this->displayUgroupBinding()) {
            $view = new Project_Admin_UGroup_View_ShowBinding($this->ugroup, $this->ugroup_binding, $binding);
            $this->render($view);
        } else {
            $this->edit_binding();
        }
    }

    /**
     * Display the binding pane content
     *
     * @return String
     */
    private function displayUgroupBinding() {
        $html = '';
        $ugroupUpdateUsersAllowed = !$this->ugroup->isBound();
        if ($ugroupUpdateUsersAllowed) {
            $em = EventManager::instance();
            $em->processEvent('ugroup_table_row', array('row' => array('group_id' => $this->ugroup->getProjectId(), 'ugroup_id' => $this->ugroup->getId()), 'html' => &$html));
        }
        return $html;
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
        $GLOBALS['Response']->redirect($this->panes[Project_Admin_UGroup_View_ShowBinding::IDENTIFIER]->getUrl());
    }

    public function remove_binding() {
        $historyDao        = new ProjectHistoryDao();
        if ($this->ugroup_binding->removeBinding($this->ugroup->getId())) {
            $historyDao->groupAddHistory("ugroup_remove_binding", $this->ugroup->getId(), $this->ugroup->getProjectId());
        }
        $GLOBALS['Response']->redirect($this->panes[Project_Admin_UGroup_View_ShowBinding::IDENTIFIER]->getUrl());
    }

    public function edit_ugroup_members() {
        $ugroupUpdateUsersAllowed = !$this->ugroup->isBound();
        $groupId  = $this->ugroup->getProjectId();
        $ugroupId = $this->ugroup->getId();
        if ($ugroupUpdateUsersAllowed) {
            $validRequest = $this->validateRequest($groupId, $this->request);
            $user = $validRequest['user'];
            if ($user && is_array($user)) {
                $this->editMembershipByUserId($groupId, $ugroupId, $user);
            }
            $add_user_name = $validRequest['add_user_name'];
            if ($add_user_name) {
                $this->addUserByName($groupId, $ugroupId, $add_user_name);
            }
        }
        //$GLOBALS['Response']->redirect($this->panes[Project_Admin_UGroup_View_Members::IDENTIFIER]->getUrl());
        $GLOBALS['Response']->redirect('?group_id='. (int)$groupId .
                '&ugroup_id='. (int)$ugroupId .
                '&func=edit'.
                '&pane=members'.
                '&offset='. (int)$validRequest['offset'] .
                '&number_per_page='. (int)$validRequest['number_per_page'] .
                '&search='. urlencode($validRequest['search']) .
                '&begin='. urlencode($validRequest['begin']) .
                '&in_project='. (int)$validRequest['in_project']
        );
    }

    /**
     * Add a user by his name to an ugroup
     *
     * @param int $groupId
     * @param int $ugroupId
     * @param String $add_user_name
     */
    private function addUserByName($groupId, $ugroupId, $add_user_name) {
        $user = UserManager::instance()->findUser($add_user_name);
        if ($user) {
            ugroup_add_user_to_ugroup($groupId, $ugroupId, $user->getId());
        } else {
            //user doesn't exist
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_account','user_not_exist'));
        }
    }

    /**
     * Add or remove user from an ugroup
     *
     * @param int $groupId
     * @param int $ugroupId
     * @param array $user
     */
    private function editMembershipByUserId($groupId, $ugroupId, array $user) {
        list($userId, $action) = each($user);
        $userId = (int)$userId;
        if ($userId) {
            switch($action) {
            case 'add':
                ugroup_add_user_to_ugroup($groupId, $ugroupId, $userId);
                break;
            case 'remove':
                ugroup_remove_user_from_ugroup($groupId, $ugroupId, $userId);
                break;
            default:
                break;
            }
        }
    }

    /**
     * Validate the HTTP request for the user members pane
     *
     * @param Integer     $groupId Id of the project
     * @param HTTPRequest $request HTTP request
     *
     * @return Array
     */
    private function validateRequest($groupId, $request) {
        $userDao            = new UserDao();
        $res                = $userDao->firstUsernamesLetters();
        $allowedBeginValues = array();
        foreach ($res as $data) {
            $allowedBeginValues[] = $data['capital'];
        }
        $result['allowed_begin_values'] = $allowedBeginValues;

        $validBegin = new Valid_WhiteList('begin', $allowedBeginValues);
        $validBegin->required();

        $validInProject = new Valid_UInt('in_project');
        $validInProject->required();

        $result['offset']          = $request->exist('browse') ? 0 : $request->getValidated('offset', 'uint', 0);
        $result['number_per_page'] = $request->exist('number_per_page') ? $request->getValidated('number_per_page', 'uint', 0) : 15;
        $result['search']          = $request->getValidated('search', 'string', '');
        $result['begin']           = $request->getValidated('begin', $validBegin, '');
        $result['in_project']      = $request->getValidated('in_project', $validInProject, $groupId);
        $result['user']            = $request->get('user');
        $result['add_user_name']   = $request->get('add_user_name');
        return $result;
    }
}

?>
