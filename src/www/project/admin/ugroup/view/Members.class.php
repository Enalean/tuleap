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

class Project_Admin_UGroup_View_Members extends Project_Admin_UGroup_View {
    const IDENTIFIER = 'members';
    
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var UGroupManager
     */
    private $user_manager;

    /**
     * @var Codendi_Request
     */
    private $request;

    public function __construct(UGroup $ugroup, Codendi_Request $request, UGroupManager $ugroup_manager, UserManager $user_manager) {
        parent::__construct($ugroup);
        $this->request = $request;
        $this->user_manager = $user_manager;
        $this->ugroup_manager = $ugroup_manager;
    }

    public function getContent() {
        $this->processEditMembersAction($this->ugroup->getProjectId(), $this->ugroup->getId(), $this->request);
        return $this->displayUgroupMembers($this->ugroup->getProjectId(), $this->ugroup->getId(), $this->request);
    }

        /**
     * Process the members pane action
     *
     * @param Integer     $groupId  Id of the project
     * @param Integer     $ugroupId Id of the user group
     * @param HTTPRequest $request  HTTP request
     *
     * @return Void
     */
    private function processEditMembersAction($groupId, $ugroupId, $request) {
        $ugroupUpdateUsersAllowed = !$this->ugroup->isBound();
        if ($ugroupUpdateUsersAllowed) {
            $validRequest = $this->validateRequest($groupId, $request);
            $user = $validRequest['user'];
            if ($user && is_array($user)) {
                $this->editMembershipByUserId($groupId, $ugroupId, $user, $validRequest);
            }
            $add_user_name = $validRequest['add_user_name'];
            if ($add_user_name) {
                $this->addUserByName($groupId, $ugroupId, $add_user_name);
            }
        }
    }

    /**
     * Add a user by his name to an ugroup
     *
     * @param int $groupId
     * @param int $ugroupId
     * @param String $add_user_name
     */
    private function addUserByName($groupId, $ugroupId, $add_user_name) {
        $user = $this->user_manager->findUser($add_user_name);
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
     * @param array $validRequest
     */
    private function editMembershipByUserId($groupId, $ugroupId, array $user, array $validRequest) {
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

    /**
     * Display the content of the members pane
     *
     * @param Integer     $groupId  Id of the project
     * @param Integer     $ugroupId Id of the user group
     * @param HTTPRequest $request  HTTP request
     *
     * @return String
     */
    private function displayUgroupMembers($groupId, $ugroupId, $request) {
        $hp                       = Codendi_HTMLPurifier::instance();
        $ugroupUpdateUsersAllowed = !$this->ugroup->isBound();
        $em                       = EventManager::instance();
        $em->processEvent(Event::UGROUP_UPDATE_USERS_ALLOWED, array('ugroup_id' => $ugroupId, 'allowed' => &$ugroupUpdateUsersAllowed));

        $content = '<h2>'. $GLOBALS['Language']->getText('project_admin_editugroup','add_users_to').' '.  $hp->purify($this->ugroup->getName(), CODENDI_PURIFIER_CONVERT_HTML)  .'</h2>';

        $content .= '
            <form method="post" action="">
                <input type="hidden" name="func" value="edit" />
                <input type="hidden" name="ugroup_id" value="'.$this->ugroup->getId().'" />
                <input type="hidden" name="group_id" value="'.$this->ugroup->getProjectId().'" />
                <label>Type username <input type="text" name="add_user_name" id="ugroup_add_user" value="" /></label>
                <input type="submit" value="'.$GLOBALS['Language']->getText('global', 'add').'" />
            </form>
        ';
        $GLOBALS['HTML']->addUserAutocompleteOn('ugroup_add_user', true);

        //ugroup binding link
        //$content .= '<P> You can also choose to <a href="editugroup.php?group_id='.$groupId.'&ugroup_id='.$ugroupId.'&func=edit&pane=bind"><b>bind to another group</b></a></p>';

        $content .= '<h2>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'group_members').'</h2>';
        $content .= '<div style="padding-left:10px">';
        $content .= '<table><tr valign="top"><td>';

        // Get existing members from group
        $content .= '<div class="admin_group_members">';
        $content .= $GLOBALS['Language']->getText('project_admin_editugroup','members');
        $members = $this->ugroup->getMembers();
        if (count($members) > 0) {
            $i = 0;
            $userHelper = UserHelper::instance();
            $content .= '<ul>';
            foreach ($members as $user) {
                $content .= '<li>';
                if ($ugroupUpdateUsersAllowed) {
                    $content .= project_admin_display_bullet_user($user->getId(), 'remove');
                }
                $content .= ' '.$hp->purify($userHelper->getDisplayNameFromUser($user));
                $content .= '</li>';
            }
            $content .= '</ul>';
        } else {
            $content .= $GLOBALS['Language']->getText('project_admin_editugroup', 'nobody_yet');
        }
        $content .= '</div>';
        //$content .= '</fieldset>';

        if ($ugroupUpdateUsersAllowed) {
            $validRequest = $this->validateRequest($groupId, $request);

            //Display the form
            $selected = 'selected="selected"';
            $content .= '<form action="" method="GET">';

            $content .= '</td><td style="padding-left: 1em;">';

            $content .= '<input type="hidden" name="group_id" value="'. (int)$groupId .'" />';
            $content .= '<input type="hidden" name="ugroup_id" value="'. (int)$ugroupId .'" />';
            $content .= '<input type="hidden" name="func" value="edit" />';
            $content .= '<input type="hidden" name="pane" value="members" />';
            $content .= '<input type="hidden" name="offset" value="'. (int)$validRequest['offset'] .'" />';

            //Filter
            //$content .= '<fieldset><legend>'.$GLOBALS['Language']->getText('project_admin_editugroup','users').'</legend>';
            $content .= $GLOBALS['Language']->getText('project_admin_editugroup','search_in').' ';
            $content .= '<select name="in_project">';
            $content .= '<option value="0" '. ( !$validRequest['in_project'] ? $selected : '') .'>'. $GLOBALS['Language']->getText('project_admin_editugroup','any_project') .'</option>';
            $content .= '<option value="'. (int)$groupId .'" '. ($validRequest['in_project'] == $groupId ? $selected : '') .'>'. $GLOBALS['Language']->getText('project_admin_editugroup','this_project') .'</option>';
            $content .= '</select>';
            $content .= $GLOBALS['Language']->getText('project_admin_editugroup','name_contains').' ';

            //contains
            $content .= '<input type="text" name="search" value="'.  $hp->purify($validRequest['search'], CODENDI_PURIFIER_CONVERT_HTML) .'" class="textfield_medium" /> ';
            //begin
            $content .= $GLOBALS['Language']->getText('project_admin_editugroup','begins').' ';
            $content .= '<select name="begin">';
            $content .= '<option value="" '. (in_array($validRequest['begin'], $validRequest['allowed_begin_values']) ? $selected : '') .'></option>';
            foreach($validRequest['allowed_begin_values'] as $b) {
                $content .= '<option value="'. $b .'" '. ($b == $validRequest['begin'] ? $selected : '') .'>'. $b .'</option>';
            }
            $content .= '</select>. ';

            //Display
            $content .= '<span style="white-space:nowrap;">'.$GLOBALS['Language']->getText('project_admin_editugroup','show').' ';
            //number per page
            $content .= '<select name="number_per_page">';
            $content .= '<option '. ($validRequest['number_per_page'] == 15 ? $selected : '') .'>15</option>';
            $content .= '<option '. ($validRequest['number_per_page'] == 30 ? $selected : '') .'>30</option>';
            $content .= '<option '. ($validRequest['number_per_page'] == 60 ? $selected : '') .'>60</option>';
            if (!in_array($validRequest['number_per_page'], array(15, 30, 60))) {
                $content .= '<option '. $selected .'>'. (int)$validRequest['number_per_page'] .'</option>';
            }
            $content .= '</select> ';
            $content .= $GLOBALS['Language']->getText('project_admin_editugroup','users_per_page').' ';

            $content .= '<input type="submit" name="browse" value="Browse" /></span>';
            $content .= '</p>';

            $dao          = new UGroupUserDao();
            $result       = $dao->searchUsersToAdd($ugroupId, $validRequest);
            $res          = $result['result'];
            $res          = $result['result'];
            $numTotalRows = $result['num_total_rows'];

            $content .= $this->displayUserResultTable($res);

            //Jump to page
            $nbOfPages = ceil($numTotalRows / $validRequest['number_per_page']);
            $currentPage = round($validRequest['offset'] / $validRequest['number_per_page']);
            $content .= '<div style="font-family:Verdana">Page: ';
            $width = 10;
            for ($i = 0 ; $i < $nbOfPages ; ++$i) {
                if ($i == 0 || $i == $nbOfPages - 1 || ($currentPage - $width / 2 <= $i && $i <= $width / 2 + $currentPage)) {
                    $content .= '<a href="?'.
                        'group_id='. (int)$groupId .
                        '&amp;ugroup_id='. (int)$ugroupId .
                        '&amp;func=edit'.
                        '&amp;pane=members'.
                        '&amp;offset='. (int)($i * $validRequest['number_per_page']) .
                        '&amp;number_per_page='. (int)$validRequest['number_per_page'] .
                        '&amp;search='. urlencode($validRequest['search']) .
                        '&amp;begin='. urlencode($validRequest['begin']) .
                        '&amp;in_project='. (int)$validRequest['in_project'] .
                        '">';
                    if ($i == $currentPage) {
                        $content .= '<b>'. ($i + 1) .'</b>';
                    } else {
                        $content .= $i + 1;
                    }
                    $content .= '</a>&nbsp;';
                } else if ($currentPage - $width / 2 - 1 == $i || $currentPage + $width / 2 + 1 == $i) {
                    $content .= '...&nbsp;';
                }
            }
            $content .= '</div>';

            //$content .= '</fieldset>';

            $content .= '</form>';
        }
        $content .= '</td></tr></table>';
        $content .= '</div>';

        return $content;
    }

    /**
     * Display the user search result
     *
     * @param DataAccessResult $res result of the user search
     *
     * @return String
     */
    private function displayUserResultTable($res) {
        $userHelper = UserHelper::instance();
        $hp         = Codendi_HTMLPurifier::instance();
        $nbCols     = 3;
        if ($res->rowCount()) {
            $output = '<table><tr>';
            $i      = 0;
            foreach ($res as $data) {
                if ($i++ % $nbCols == 0) {
                    $output .= '</tr><tr>';
                }
                $action     = 'add';
                $background = 'eee';
                if ($data['is_on']) {
                    $action     = 'remove';
                    $background = 'dcf7c4';
                }
                $output .= '<td width="'. round(100/$nbCols) .'%">';
                $output .= '<div style="border:1px solid #CCC; background: #'. $background .'; padding:10px 5px; position:relative">';
                $output .= '<table width="100%"><tr><td><a href="/users/'. $hp->purify($data['user_name']) .'/">'. $hp->purify($userHelper->getDisplayName($data['user_name'], $data['realname'])) .'</a></td>';
                $output .= '<td style="text-align:right;">';
                $output .= project_admin_display_bullet_user($data['user_id'], $action);
                $output .= '</td></tr></table>';
                $output .= '<div style="color:#666; ">'. $data['email'] .'</div>';
                $output .= '</div>';
                $output .= '</td>';
            }
            while($i++ % $nbCols != 0) {
                $output .= '<td width="'. round(100/$nbCols) .'%"></td>';
            }
            $output .= '</tr></table>';
        } else {
            $output .= 'No user match';
        }
        return $output;
    }

    public function getIdentifier() {
        return self::IDENTIFIER;
    }
}

?>
