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

use Tuleap\Project\Admin\ProjectUGroup\UGroupEditProcessAction;

class Project_Admin_UGroup_UGroupController {

    /**
     *
     * @var Codendi_Request
     */
    protected $request;

    /**
     *
     * @var UGroupManager
     */
    protected $ugroup_manager;

    /**
     *
     * @var ProjectUGroup
     */
    protected $ugroup;

    /**
     *
     * @var UGroupBinding
     */
    protected $ugroup_binding;

    /**
     *
     * @var type Project_Admin_UGroup_PaneInfo
     */
    protected $pane;

    /**
     *
     * @var Project_Admin_UGroup_PaneManagement
     */
    private $pane_management;

    public function __construct(Codendi_Request $request, ProjectUGroup $ugroup) {
        $this->request = $request;
        $this->ugroup = $ugroup;
        $this->ugroup_manager = new UGroupManager();
        $this->ugroup_binding = new UGroupBinding(new UGroupUserDao(), $this->ugroup_manager);
        $this->pane_management = new Project_Admin_UGroup_PaneManagement($this->ugroup, null);
        $this->pane = $this->pane_management->getPaneById(Project_Admin_UGroup_View_Settings::IDENTIFIER);
    }

    protected function render(Project_Admin_UGroup_View $view) {
        $pane_management = new Project_Admin_UGroup_PaneManagement(
            $this->ugroup,
            $view
        );
        $pane_management->display();
    }

    public function settings() {
        $view = new Project_Admin_UGroup_View_Settings($this->ugroup, $this->ugroup_binding);
        $this->render($view);
    }

    public function ldap_remove_binding()
    {
        $this->ldap();
    }

    public function ldap_add_binding()
    {
        $this->ldap();
    }

    public function ldap()
    {
        $event = new UGroupEditProcessAction($this->request, $this->ugroup);
        EventManager::instance()->processEvent($event);
        $this->redirect();
    }

    public function remove_binding()
    {
        $csrf = new CSRFSynchronizerToken(
            'project/admin/editugroup.php&' . http_build_query(
                array(
                    'group_id'  => $this->ugroup->getProjectId(),
                    'ugroup_id' => $this->ugroup->getId(),
                    'func'      => 'edit',
                    'pane'      => 'settings',
                )
            )
        );
        $csrf->check();

        $history_dao = new ProjectHistoryDao();
        if ($this->ugroup_binding->removeBinding($this->ugroup->getId())) {
            $history_dao->groupAddHistory("ugroup_remove_binding", $this->ugroup->getId(), $this->ugroup->getProjectId());
            $this->launchEditBindingUgroupEvent();
        }
        $this->redirect();
    }

    public function add_binding()
    {
        $history_dao       = new ProjectHistoryDao();
        $project_source_id = $this->request->getValidated('source_project', 'GroupId');
        $ugroup_source_id  = $this->request->get('source_ugroup');
        $is_valid          = $this->ugroup_manager->checkUGroupValidityByGroupId($project_source_id, $ugroup_source_id);
        $project           = ProjectManager::instance()->getProject($project_source_id);
        if ($is_valid && $project->userIsAdmin()) {
            if ($this->ugroup_binding->addBinding($this->ugroup->getId(), $ugroup_source_id)) {
                $history_dao->groupAddHistory(
                    "ugroup_add_binding",
                    $this->ugroup->getId().":".$ugroup_source_id,
                    $this->ugroup->getProjectId()
                );
                $this->launchEditBindingUgroupEvent();
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_ugroup_binding', 'add_error'));
        }
        $this->redirect();
    }

    private function launchEditBindingUgroupEvent() {
        $event_manager = EventManager::instance();
        $event_manager->processEvent('project_admin_ugroup_bind_modified',
            array(
                'group_id'  => $this->ugroup->getProjectId(),
                'ugroup_id' => $this->ugroup->getId()
            )
        );
    }

    public function edit_ugroup_members()
    {
        $csrf = new CSRFSynchronizerToken(
            '/project/admin/editugroup.php?group_id=' . $this->ugroup->getProjectId()
            . '&ugroup_id=' . $this->ugroup->getId() . '&func=edit&pane=settings'
        );
        $csrf->check();

        $ugroupUpdateUsersAllowed = !$this->ugroup->isBound();
        $groupId  = $this->ugroup->getProjectId();
        $ugroupId = $this->ugroup->getId();
        $validRequest = $this->validateRequest($groupId, $this->request);

        $url_additional_params = array(
            'offset'          => (int)$validRequest['offset'],
            'number_per_page' => (int)$validRequest['number_per_page'],
            'search'          => urlencode($validRequest['search']),
            'begin'           => urlencode($validRequest['begin']),
            'in_project'      => (int)$validRequest['in_project'],
            'pane'            => $validRequest['pane']
        );

        if ($ugroupUpdateUsersAllowed) {
            $user = $validRequest['user'];
            if ($user && is_array($user)) {
                $this->editMembershipByUserId($groupId, $ugroupId, $user);
            }
            $add_user_name = $validRequest['add_user_name'];
            if ($add_user_name) {
                $this->addUserByName($groupId, $ugroupId, $add_user_name);
            }
        }
        $this->redirect($url_additional_params);
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
        $result['pane']            = $request->get('pane');

        return $result;
    }

    /**
     *
     * @param array $additional_params must be http_build_query friendly :
     * option => value
     */
    protected function redirect(array $additional_params = array()){
        $url = $this->pane->getUrl();
        if (! empty($additional_params)) {
            $url = $url . '&' . http_build_query($additional_params);
        }

        return $GLOBALS['Response']->redirect($url);
    }
}
