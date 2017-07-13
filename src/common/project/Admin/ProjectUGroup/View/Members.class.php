<?php
/**
 * Copyright Enalean (c) 2011-2017. All rights reserved.
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
     * @var Codendi_Request
     */
    private $request;

    /**
     *
     * @var Array
     */
    private $validated_request;

    public function __construct(ProjectUGroup $ugroup, Codendi_Request $request, UGroupManager $ugroup_manager, array $validated_request) {
        parent::__construct($ugroup);
        $this->request = $request;
        $this->ugroup_manager = $ugroup_manager;
        $this->validated_request = $validated_request;
    }

    public function getContent() {
        return $this->displayUgroupMembers($this->ugroup->getProjectId(), $this->ugroup->getId(), $this->request);
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
        $content = '';

        $content .= '<div style="padding-left:10px">';
        $content .= '<table><tr valign="top"><td>';

        // Get existing members from group
        $content .= '<h2>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'group_members').'</h2>';
        $content .= '<div class="admin_group_members">';
        $members = $this->ugroup->getMembers();
        if (count($members) > 0) {
            $i = 0;
            $userHelper = UserHelper::instance();
            $content .= '<ul>';
            foreach ($members as $user) {
                $content .= '<li>';
                if ($ugroupUpdateUsersAllowed) {
                    $content .= $this->project_admin_bullet_user_content($user->getId(), 'remove');
                }
                $content .= ' '.$hp->purify($userHelper->getDisplayNameFromUser($user));
                $content .= '</li>';
            }
            $content .= '</ul>';
        } else {
            $content .= $GLOBALS['Language']->getText('project_admin_editugroup', 'nobody_yet');
        }
        $content .= '</div>';

        if ($ugroupUpdateUsersAllowed) {
            //Display the form
            $selected = 'selected="selected"';

            $content .= '</td><td style="padding-left: 1em;">';
            $content .= '<h2>'. $GLOBALS['Language']->getText('project_admin_editugroup','add_users_to').' '.  $hp->purify($this->ugroup->getName(), CODENDI_PURIFIER_CONVERT_HTML)  .'</h2>';

            $content .= '
                <form method="post" class="form-inline" action="">
                    <input type="hidden" name="func" value="edit" />
                    <input type="hidden" name="action" value="edit_ugroup_members" />
                    <input type="hidden" name="ugroup_id" value="'.$this->ugroup->getId().'" />
                    <input type="hidden" name="group_id" value="'.$this->ugroup->getProjectId().'" />
                    <label> ' . $GLOBALS['Language']->getText('project_ugroup_user', 'add_username') . ' <input type="text" name="add_user_name" id="ugroup_add_user" value="" /></label>
                    <input class="btn" type="submit" value="'.$GLOBALS['Language']->getText('global', 'add').'" />
                </form>
            ';
            $GLOBALS['HTML']->addUserAutocompleteOn('ugroup_add_user', true);

            $content .= '<form action="" method="GET">';
            $content .= '<input type="hidden" name="group_id" value="'. (int)$groupId .'" />';
            $content .= '<input type="hidden" name="ugroup_id" value="'. (int)$ugroupId .'" />';
            $content .= '<input type="hidden" name="func" value="edit" />';
            $content .= '<input type="hidden" name="pane" value="members" />';
            $content .= '<input type="hidden" name="action" value="filter_users" />';
            $content .= '<input type="hidden" name="offset" value="'. (int)$this->validated_request['offset'] .'" />';

            //Filter
            $content .= '<p>';
            $content .= $GLOBALS['Language']->getText('project_admin_editugroup','search_in').' ';
            $content .= '<select name="in_project">';
            $content .= '<option value="0" '. ( !$this->validated_request['in_project'] ? $selected : '') .'>'. $GLOBALS['Language']->getText('project_admin_editugroup','any_project') .'</option>';
            $content .= '<option value="'. (int)$groupId .'" '. ($this->validated_request['in_project'] == $groupId ? $selected : '') .'>'. $GLOBALS['Language']->getText('project_admin_editugroup','this_project') .'</option>';
            $content .= '</select>';
            $content .= $GLOBALS['Language']->getText('project_admin_editugroup','name_contains').' ';

            //contains
            $content .= '<input type="text" name="search" value="'.  $hp->purify($this->validated_request['search'], CODENDI_PURIFIER_CONVERT_HTML) .'" class="textfield_medium" /> ';
            //begin
            $content .= $GLOBALS['Language']->getText('project_admin_editugroup','begins').' ';
            $content .= '<select name="begin">';
            $content .= '<option value="" '. (in_array($this->validated_request['begin'], $this->validated_request['allowed_begin_values']) ? $selected : '') .'></option>';
            foreach($this->validated_request['allowed_begin_values'] as $b) {
                $content .= '<option value="'. $hp->purify($b) .'" '. ($b == $this->validated_request['begin'] ? $selected : '') .'>'. $hp->purify($b) .'</option>';
            }
            $content .= '</select>. ';

            //Display
            $content .= '<span style="white-space:nowrap;">'.$GLOBALS['Language']->getText('project_admin_editugroup','show').' ';
            //number per page
            $content .= '<select name="number_per_page">';
            $content .= '<option '. ($this->validated_request['number_per_page'] == 15 ? $selected : '') .'>15</option>';
            $content .= '<option '. ($this->validated_request['number_per_page'] == 30 ? $selected : '') .'>30</option>';
            $content .= '<option '. ($this->validated_request['number_per_page'] == 60 ? $selected : '') .'>60</option>';
            if (!in_array($this->validated_request['number_per_page'], array(15, 30, 60))) {
                $content .= '<option '. $selected .'>'. (int)$this->validated_request['number_per_page'] .'</option>';
            }
            $content .= '</select> ';
            $content .= $GLOBALS['Language']->getText('project_admin_editugroup','users_per_page').' ';

            $content .= '<input class="btn" type="submit" name="browse" value="Browse" /></span>';
            $content .= '</p>';

            $dao          = new UGroupUserDao();
            $result       = $dao->searchUsersToAdd($ugroupId, $this->validated_request);
            $res          = $result['result'];
            $res          = $result['result'];
            $numTotalRows = $result['num_total_rows'];

            $content .= $this->displayUserResultTable($res);

            //Jump to page
            $nbOfPages = ceil($numTotalRows / $this->validated_request['number_per_page']);
            $currentPage = round($this->validated_request['offset'] / $this->validated_request['number_per_page']);
            $content .= '<div style="font-family:Verdana">Page: ';
            $width = 10;
            for ($i = 0 ; $i < $nbOfPages ; ++$i) {
                if ($i == 0 || $i == $nbOfPages - 1 || ($currentPage - $width / 2 <= $i && $i <= $width / 2 + $currentPage)) {
                    $content .= '<a href="?'.
                        'group_id='. (int)$groupId .
                        '&amp;ugroup_id='. (int)$ugroupId .
                        '&amp;func=edit'.
                        '&amp;pane=members'.
                        '&amp;offset='. (int)($i * $this->validated_request['number_per_page']) .
                        '&amp;number_per_page='. (int)$this->validated_request['number_per_page'] .
                        '&amp;search='. urlencode($this->validated_request['search']) .
                        '&amp;begin='. urlencode($this->validated_request['begin']) .
                        '&amp;in_project='. (int)$this->validated_request['in_project'] .
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
            $output = '<table style="width: 100%"><tr>';
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
                $output .= $this->project_admin_bullet_user_content($data['user_id'], $action);
                $output .= '</td></tr></table>';
                $output .= '<div style="color:#666; ">'. $hp->purify($data['email']) .'</div>';
                $output .= '</div>';
                $output .= '</td>';
            }
            while($i++ % $nbCols != 0) {
                $output .= '<td width="'. round(100/$nbCols) .'%"></td>';
            }
            $output .= '</tr></table>';
        } else {
            $output = 'No user match';
        }
        return $output;
    }

    public function getIdentifier() {
        return self::IDENTIFIER;
    }

    private function project_admin_bullet_user_content($user_id, $action, $url = null) {
        if ($action == 'add') {
            $icon       = '/ic/add.png';
        } else {
            $icon       = '/ic/cross.png';
        }
        if (!$url) {
            $url = $_SERVER['REQUEST_URI'] . '&' . http_build_query( array(
                'action' => 'edit_ugroup_members',
                'user['. $user_id .']' => $action,
                )
            );
        }
        $html = '<a href="'. $url .'">';
        $html .= '<img alt="'. $action .'" src="'. util_get_dir_image_theme() . $icon .'" />';
        $html .= '</a>';
        return $html;
    }
}

?>