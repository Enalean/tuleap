<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once 'UGroup.class.php';
require_once 'Project.class.php';
require_once 'common/dao/UGroupDao.class.php';
require_once 'common/dao/UGroupUserDao.class.php';

class UGroupManager {
    
    /**
     * @var UGroupDao
     */
    private $dao;

    public function __construct(UGroupDao $dao = null) {
        $this->dao = $dao;
    }

    /**
     * @return UGroup of the given project or null if not found
     */
    public function getUGroup(Project $project, $ugroup_id) {
        $project_id = $project->getID();
        if ($ugroup_id <= 100) {
            $project_id = 100;
        }

        $row = $this->getDao()->searchByGroupIdAndUGroupId($project_id, $ugroup_id)->getRow();
        if ($row) {
            return new UGroup($row);
        }
    }

    public function getUGroups(Project $project, array $exclude = array()) {
        $ugroups = array();
        foreach ($this->getDao()->searchDynamicAndStaticByGroupId($project->getId()) as $row) {
            if (in_array($row['ugroup_id'], $exclude)) {
                continue;
            }
            $ugroups[] = new UGroup($row);
        }
        return $ugroups;
    }

    public function getUGroupByName(Project $project, $name) {
        $row = $this->getDao()->searchByGroupIdAndName($project->getID(), $name)->getRow();
        if (!$row && preg_match('/^ugroup_.*_key$/', $name)) {
            $row = $this->getDao()->searchByGroupIdAndName(100, $name)->getRow();
        }
        if ($row) {
            return new UGroup($row);
        }
        return null;
    }

    /**
     * Return all UGroups the user belongs to
     *
     * @param User $user The user
     *
     * @return DataAccessResult
     */
    public function getByUserId($user) {
        return $this->getDao()->searchByUserId($user->getId());
    }

    /**
     * Returns a UGroup from its Id
     *
     * @param Integer $ugroupId The UserGroupId
     * 
     * @return UGroup
     */
    public function getById($ugroupId) {
        $dar = $this->getDao()->searchByUGroupId($ugroupId);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return new UGroup($dar->getRow());
        } else {
            return new UGroup();
        }
    }

    /**
     * Wrapper for UGroupDao
     *
     * @return UGroupDao
     */
    public function getDao() {
        if (!$this->dao) {
            $this->dao = new UGroupDao();
        }
        return $this->dao;
    }

    /**
     * Wrapper for EventManager
     *
     * @return EventManager
     */
    private function getEventManager() {
        return EventManager::instance();
    }

    /**
     * Get Dynamic ugroups members
     *
     * @param Integer $ugroupId Id of the ugroup
     * @param Integer $groupId  Id of the project
     *
     * @return array of User
     */
    public function getDynamicUGroupsMembers($ugroupId, $groupId) {
        if ($ugroupId > 100) {
            return array();
        }
        $um = UserManager::instance();
        $users   = array();
        $dao     = new UGroupUserDao();
        $members = $dao->searchUserByDynamicUGroupId($ugroupId, $groupId);
        if ($members && !$members->isError()) {
            foreach ($members as $member) {
                $users[] = $um->getUserById($member['user_id']);
            }
        }
        return $users;
    }

    /**
     * Check if update users is allowed for a given user group
     *
     * @param Integer $ugroupId Id of the user group
     *
     * @return boolean
     */
    public function isUpdateUsersAllowed($ugroupId) {
        $ugroupUpdateUsersAllowed = true;
        $this->getEventManager()->processEvent(Event::UGROUP_UPDATE_USERS_ALLOWED, array('ugroup_id' => $ugroupId, 'allowed' => &$ugroupUpdateUsersAllowed));
        return $ugroupUpdateUsersAllowed;
    }

    /**
     * Wrapper for dao method that checks if the user group is valid
     *
     * @param Integer $groupId  Id of the project
     * @param Integer $ugroupId Id of the user goup
     *
     * @return boolean
     */
    public function checkUGroupValidityByGroupId($groupId, $ugroupId) {
        return $this->getDao()->checkUGroupValidityByGroupId($groupId, $ugroupId);
    }

    /**
     * Wrapper for dao method that retrieves all Ugroups bound to a given Ugroup
     *
     * @param Integer $ugroupId Id of the user goup
     *
     * @return DataAccessResult
     */
    public function searchUGroupByBindingSource($ugroupId) {
        return $this->getDao()->searchUGroupByBindingSource($ugroupId);
    }

    /**
     * Wrapper for dao method that updates binding option for a given UGroup
     *
     * @param Integer $ugroupId Id of the user goup
     *
     * @return Boolean
     */
    public function updateUgroupBinding($ugroupId, $sourceId = null) {
        return $this->getDao()->updateUgroupBinding($ugroupId, $sourceId);
    }

    /**
     * Wrapper to retrieve the source user group from a given bound ugroup id
     *
     * @param Integer $ugroupId The source ugroup id
     *
     * @return DataAccessResult
     */
    public function getUgroupBindingSource($ugroupId) {
        return $this->getDao()->getUgroupBindingSource($ugroupId);
    }

    /**
     * Wrapper for UserGroupDao
     *
     * @return UserGroupDao
     */
    public function getUserGroupDao() {
        return new UserGroupDao();
    }

    /**
     * Return name and id of all ugroups belonging to a specific project
     *
     * @param Integer $groupId    Id of the project
     * @param Array   $predefined List of predefined ugroup id
     *
     * @return DataAccessResult
     */
    public function getExistingUgroups($groupId, $predefined = null) {
        $dar = $this->getUserGroupDao()->getExistingUgroups($groupId, $predefined);
        if ($dar && !$dar->isError()) {
            return $dar;
        }
        return array();
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
        return $result;
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
    public function processEditMembersAction($groupId, $ugroupId, $request) {
        $uGroup                   = $this->getById($ugroupId);
        $ugroupUpdateUsersAllowed = !$uGroup->isBound();
        if ($ugroupUpdateUsersAllowed) {
            $validRequest = $this->validateRequest($groupId, $request);
            $user = $validRequest['user'];
            if ($user && is_array($user)) {
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
        }
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
    public function displayUgroupMembers($groupId, $ugroupId, $request) {
        $hp                       = Codendi_HTMLPurifier::instance();
        $uGroup                   = $this->getById($ugroupId);
        $ugroupUpdateUsersAllowed = !$uGroup->isBound();
        $em                       = EventManager::instance();
        $em->processEvent(Event::UGROUP_UPDATE_USERS_ALLOWED, array('ugroup_id' => $ugroupId, 'allowed' => &$ugroupUpdateUsersAllowed));

        $content = '<P><h2>'. $GLOBALS['Language']->getText('project_admin_editugroup','add_users_to').' '.  $hp->purify($uGroup->getName(), CODENDI_PURIFIER_CONVERT_HTML)  .'</h2>';

        //ugroup binding link
        $content .= '<P> You can also choose to <a href="editugroup.php?group_id='.$groupId.'&ugroup_id='.$ugroupId.'&func=edit&pane=bind"><b>bind to another group</b></a></p>';

        $content .= '<p><b>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'group_members').'</b></p>';
        $content .= '<div style="padding-left:10px">';
        $content .= '<table><tr valign="top"><td>';

        // Get existing members from group
        $content .= '<fieldset><legend>'. $GLOBALS['Language']->getText('project_admin_editugroup','members').'</legend>';
        $members = $uGroup->getMembers();
        if (count($members) > 0) {
            $content .= '<table>';
            $i = 0;
            $userHelper = UserHelper::instance();
            foreach ($members as $user) {
                $content .= '<tr class="'. html_get_alt_row_color(++$i) .'">';
                $content .= '<td>'. $hp->purify($userHelper->getDisplayNameFromUser($user)) .'</td>';
                if ($ugroupUpdateUsersAllowed) {
                    $content .= '<td>';
                    $content .= project_admin_display_bullet_user($user->getId(), 'remove');
                    $content .= '</td>';
                }
                $content .= '</tr>';
            }
            $content .= '</table>';
        } else {
            $content .= $GLOBALS['Language']->getText('project_admin_editugroup', 'nobody_yet');
        }
        $content .= '</fieldset>';

        if ($ugroupUpdateUsersAllowed) {
            $validRequest = $this->validateRequest($groupId, $request);

            //Display the form
            $selected = 'selected="selected"';
            $content .= '<form action="" method="GET">';

            $content .= '</td><td>';

            $content .= '<input type="hidden" name="group_id" value="'. (int)$groupId .'" />';
            $content .= '<input type="hidden" name="ugroup_id" value="'. (int)$ugroupId .'" />';
            $content .= '<input type="hidden" name="func" value="edit" />';
            $content .= '<input type="hidden" name="pane" value="members" />';
            $content .= '<input type="hidden" name="offset" value="'. (int)$validRequest['offset'] .'" />';

            //Filter
            $content .= '<fieldset><legend>'.$GLOBALS['Language']->getText('project_admin_editugroup','users').'</legend>';
            $content .= '<p>'. $GLOBALS['Language']->getText('project_admin_editugroup','search_in').' ';
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

            $content .= '</fieldset>';

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
    public function displayUserResultTable($res) {
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

    /**
     * Display the binding pane content
     *
     * @param Integer     $groupId  Id of the project
     * @param Integer     $ugroupId Id of the user group
     *
     * @return String
     */
    public function displayUgroupBinding($groupId, $ugroupId) {
        $html = '';
        $uGroup                   = $this->getById($ugroupId);
        $ugroupUpdateUsersAllowed = !$uGroup->isBound();
        if ($ugroupUpdateUsersAllowed) {
            $em   = EventManager::instance();
            $em->processEvent('ugroup_table_row', array('row' => array('group_id' => $groupId, 'ugroup_id' => $ugroupId), 'html' => &$html));
        }
        return $html;
    }

}

?>