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

require_once dirname(__FILE__).'/../UGroupController.class.php';

class Project_Admin_UGroup_UGroupController_Members extends Project_Admin_UGroup_UGroupController {

    public function __construct(Codendi_Request $request, UGroup $ugroup, Project_Admin_UGroup_PaneInfo $pane) {
        parent::__construct($request, $ugroup);
        $this->pane = $pane;
    }

    public function edit_ugroup_members() {
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
    public function validateRequest($groupId, $request) {
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
