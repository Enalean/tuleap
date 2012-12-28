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


    public function displayUgroupMembers($groupId, $ugroupId, $request) {
        $hp                       = Codendi_HTMLPurifier::instance();
        $uGroup                   = $this->getById($ugroupId);
        $ugroupUpdateUsersAllowed = !$uGroup->isBound();
        $em                       = EventManager::instance();
        $em->processEvent(Event::UGROUP_UPDATE_USERS_ALLOWED, array('ugroup_id' => $ugroupId, 'allowed' => &$ugroupUpdateUsersAllowed));

        $content .= '<P><h2>'. $GLOBALS['Language']->getText('project_admin_editugroup','add_users_to').' '.  $hp->purify($ugroup_name, CODENDI_PURIFIER_CONVERT_HTML)  .'</h2>';

        $content .= '<p><b>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'group_members').'</b></p>';
        $content .= '<div style="padding-left:10px">';
        $content .= '<table><tr valign="top"><td>';

        // Get existing members from group
        $members = $uGroup->getMembers();
        if (count($members) > 0) {
            $content .= '<form action="ugroup_remove_user.php" method="POST">';
            $content .= '<input type="hidden" name="group_id" value="'.$groupId.'">';
            $content .= '<input type="hidden" name="ugroup_id" value="'.$ugroupId.'">';
            $content .= '<fieldset><legend>'. $GLOBALS['Language']->getText('project_admin_editugroup','members').'</legend>';
            $content .= '<table>';
            $i = 0;
            $userHelper = UserHelper::instance();
            foreach ($members as $user) {
                $content .= '<tr class="'. html_get_alt_row_color(++$i) .'">';
                $content .= '<td>'. $hp->purify($userHelper->getDisplayNameFromUser($user)) .'</td>';
                if ($ugroupUpdateUsersAllowed) {
                    $content .= '<td>';
                    $content .= project_admin_display_bullet_user($user->getId(), 'remove', 'ugroup_remove_user.php?group_id='. $groupId. '&ugroup_id='. $ugroupId .'&user_id='. $user->getId());
                    $content .= '</td>';
                }
                $content .= '</tr>';
            }
            $content .= '</table>';
            $content .= '</fieldset>';
            $content .= '</form>';
        } else {
            $content .= $GLOBALS['Language']->getText('project_admin_editugroup', 'nobody_yet');
        }

        if ($ugroupUpdateUsersAllowed) {
            $res = ugroup_db_get_ugroup($ugroupId);
            if ($res) {
                $ugroup_name = db_result($res, 0, 'name');

                //define capitals
                $sql = "SELECT DISTINCT UPPER(LEFT(user.email,1)) as capital
                    FROM user
                    WHERE status in ('A', 'R')
                    UNION
                    SELECT DISTINCT UPPER(LEFT(user.realname,1)) as capital
                    FROM user
                    WHERE status in ('A', 'R')
                    UNION
                    SELECT DISTINCT UPPER(LEFT(user.user_name,1)) as capital
                    FROM user
                    WHERE status in ('A', 'R')
                    ORDER BY capital";
                $res = db_query($sql);
                $allowed_begin_values = array();
                while($data = db_fetch_array($res)) {
                    $allowed_begin_values[] = $data['capital'];
                }

                $valid_begin = new Valid_WhiteList('begin', $allowed_begin_values);
                $valid_begin->required();
                
                $valid_in_project = new Valid_UInt('in_project');
                $valid_in_project->required();
                
                $offset           = $request->exist('browse') ? 0 : $request->getValidated('offset', 'uint', 0);
                $number_per_page  = $request->exist('number_per_page') ? $request->getValidated('number_per_page', 'uint', 0) : 15;
                $search           = $request->getValidated('search', 'string', '');
                $begin            = $request->getValidated('begin', $valid_begin, '');
                $in_project       = $request->getValidated('in_project', $valid_in_project, $groupId);
                
                $user = $request->get('user');
                if ($user && is_array($user)) {
                    list($user_id, $action) = each($user);
                    $user_id = (int)$user_id;
                    if ($user_id) {
                        switch($action) {
                        case 'add':
                            ugroup_add_user_to_ugroup($groupId, $ugroupId, $user_id);
                            break;
                        case 'remove':
                            ugroup_remove_user_from_ugroup($groupId, $ugroupId, $user_id);
                            break;
                        default:
                            break;
                        }
                        $GLOBALS['Response']->redirect('?group_id='. (int)$groupId .
                            '&ugroup_id='. (int)$ugroupId .
                            '&offset='. (int)$offset .
                            '&number_per_page='. (int)$number_per_page .
                            '&search='. urlencode($search) .
                            '&begin='. urlencode($begin) .
                            '&in_project='. (int)$in_project
                        );
                    }
                }

                //Display the form
                $selected = 'selected="selected"';
                $content .= '<form action="" method="GET">';

                $content .= '</td><td>';

                $content .= '<input type="hidden" name="group_id" value="'. (int)$groupId .'" />';
                $content .= '<input type="hidden" name="ugroup_id" value="'. (int)$ugroupId .'" />';
                $content .= '<input type="hidden" name="func" value="edit" />';
                $content .= '<input type="hidden" name="pane" value="members" />';
                $content .= '<input type="hidden" name="offset" value="'. (int)$offset .'" />';

                //Filter
                $content .= '<fieldset><legend>'.$GLOBALS['Language']->getText('project_admin_editugroup','users').'</legend>';
                $content .= '<p>'. $GLOBALS['Language']->getText('project_admin_editugroup','search_in').' ';
                $content .= '<select name="in_project">';
                $content .= '<option value="0" '. ( !$in_project ? $selected : '') .'>'. $GLOBALS['Language']->getText('project_admin_editugroup','any_project') .'</option>';
                $content .= '<option value="'. (int)$groupId .'" '. ($in_project == $groupId ? $selected : '') .'>'. $GLOBALS['Language']->getText('project_admin_editugroup','this_project') .'</option>';
                $content .= '</select>';
                $content .= $GLOBALS['Language']->getText('project_admin_editugroup','name_contains').' ';
                
                //contains
                $content .= '<input type="text" name="search" value="'.  $hp->purify($search, CODENDI_PURIFIER_CONVERT_HTML) .'" class="textfield_medium" /> ';
                //begin
                $content .= $GLOBALS['Language']->getText('project_admin_editugroup','begins').' ';
                $content .= '<select name="begin">';
                $content .= '<option value="" '. (in_array($begin, $allowed_begin_values) ? $selected : '') .'></option>';
                foreach($allowed_begin_values as $b) {
                    $content .= '<option value="'. $b .'" '. ($b == $begin ? $selected : '') .'>'. $b .'</option>';
                }
                $content .= '</select>. ';
                
                //Display
                $content .= '<span style="white-space:nowrap;">'.$GLOBALS['Language']->getText('project_admin_editugroup','show').' ';
                //number per page
                $content .= '<select name="number_per_page">';
                $content .= '<option '. ($number_per_page == 15 ? $selected : '') .'>15</option>';
                $content .= '<option '. ($number_per_page == 30 ? $selected : '') .'>30</option>';
                $content .= '<option '. ($number_per_page == 60 ? $selected : '') .'>60</option>';
                if (!in_array($number_per_page, array(15, 30, 60))) {
                    $content .= '<option '. $selected .'>'. (int)$number_per_page .'</option>';
                }
                $content .= '</select> ';
                $content .= $GLOBALS['Language']->getText('project_admin_editugroup','users_per_page').' ';
                
                
                $content .= '<input type="submit" name="browse" value="Browse" /></span>';
                $content .= '</p>';
                
                $sql = "SELECT SQL_CALC_FOUND_ROWS user.user_id, user_name, realname, email, IF(R.user_id = user.user_id, 1, 0) AS is_on
                        FROM user NATURAL LEFT JOIN (SELECT user_id FROM ugroup_user WHERE ugroup_id=". db_ei($ugroupId) .") AS R
                        ";
                if ($in_project) {
                    $sql .= " INNER JOIN user_group USING ( user_id ) ";
                }
                $sql .= "
                        WHERE status in ('A', 'R') ";
                if ($in_project) {
                    $sql .= " AND user_group.group_id = ". db_ei($in_project) ." ";
                }
                if ($search || $begin) {
                    $sql .= ' AND ( ';
                    if ($search) {
                        $sql .= " user.realname LIKE '%". db_es($search) ."%' OR user.user_name LIKE '%". db_es($search) ."%' OR user.email LIKE '%". db_es($search) ."%' ";
                        if ($begin) {
                            $sql .= " OR ";
                        }
                    }
                    if ($begin) {
                        $sql .= " user.realname LIKE '". db_es($begin) ."%' OR user.user_name LIKE '". db_es($begin) ."%' OR user.email LIKE '". db_es($begin) ."%' ";
                    }
                    $sql .= " ) ";
                }
                $sql .= "ORDER BY ". (user_get_preference("username_display") > 1 ? 'realname' : 'user_name') ."
                        LIMIT ". db_ei($offset) .", ". db_ei($number_per_page);
                $res = db_query($sql);
                $res2 = db_query('SELECT FOUND_ROWS() as nb');
                $num_total_rows = db_result($res2, 0, 'nb');
                $content .= $this->displayUserResultTable($res);
                
                //Jump to page
                $nb_of_pages = ceil($num_total_rows / $number_per_page);
                $current_page = round($offset / $number_per_page);
                $content .= '<div style="font-family:Verdana">Page: ';
                $width = 10;
                for ($i = 0 ; $i < $nb_of_pages ; ++$i) {
                    if ($i == 0 || $i == $nb_of_pages - 1 || ($current_page - $width / 2 <= $i && $i <= $width / 2 + $current_page)) {
                        $content .= '<a href="?'.
                            'group_id='. (int)$groupId .
                            '&amp;ugroup_id='. (int)$ugroupId .
                            '&amp;func=edit'.
                            '&amp;pane=members'.
                            '&amp;offset='. (int)($i * $number_per_page) .
                            '&amp;number_per_page='. (int)$number_per_page .
                            '&amp;search='. urlencode($search) .
                            '&amp;begin='. urlencode($begin) .
                            '&amp;in_project='. (int)$in_project .
                            '">';
                        if ($i == $current_page) {
                            $content .= '<b>'. ($i + 1) .'</b>';
                        } else {
                            $content .= $i + 1;
                        }
                        $content .= '</a>&nbsp;';
                    } else if ($current_page - $width / 2 - 1 == $i || $current_page + $width / 2 + 1 == $i) {
                        $content .= '...&nbsp;';
                    }
                }
                $content .= '</div>';
                
                $content .= '</fieldset>';

                $content .= '</td></tr></table>';
                
                $content .= '</form>';
                $content .= '<p><a href="/project/admin/editugroup.php?group_id='. $groupId .'&amp;ugroup_id='. $ugroupId .'&amp;func=edit">&laquo;'.$GLOBALS['Language']->getText('project_admin_editugroup','go_back').'</a></p>';
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_admin_editugroup','ug_not_found',array($ugroupId,db_error())));
                $GLOBALS['Response']->redirect('/project/admin/ugroup.php?group_id='. $groupId);
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'operation_not_allowed'));
            $GLOBALS['Response']->redirect('/project/admin/editugroup.php?group_id='. $groupId .'&ugroup_id='. $ugroupId .'&func=edit');
        }

        return $content;
    }

    public function displayUserResultTable($res) {
        $userHelper = UserHelper::instance();
        $hp = Codendi_HTMLPurifier::instance();
        $nb_cols = 3;
        if (db_numrows($res)) {
            $output = '<table><tr>';
            $i = 0;
            while($data = db_fetch_array($res)) {
                if ($i++ % $nb_cols == 0) {
                    $output .= '</tr><tr>';
                }
                $action     = 'add';
                $background = 'eee';
                if ($data['is_on']) {
                    $action     = 'remove';
                    $background = 'dcf7c4';
                }
                $output .= '<td width="'. round(100/$nb_cols) .'%">';
                $output .= '<div style="border:1px solid #CCC; background: #'. $background .'; padding:10px 5px; position:relative">';
                $output .= '<table width="100%"><tr><td><a href="/users/'. $hp->purify($data['user_name']) .'/">'. $hp->purify($userHelper->getDisplayName($data['user_name'], $data['realname'])) .'</a></td>';
                $output .= '<td style="text-align:right;">';
                $output .= project_admin_display_bullet_user($data['user_id'], $action, 'ugroup_add_users.php?'.$_SERVER['QUERY_STRING'].'&user['. $data['user_id'] .']='. $action);
                $output .= '</td></tr></table>';
                $output .= '<div style="color:#666; ">'. $data['email'] .'</div>';
                $output .= '</div>';
                $output .= '</td>';
            }
            while($i++ % $nb_cols != 0) {
                $output .= '<td width="'. round(100/$nb_cols) .'%"></td>';
            }
            $output .= '</tr></table>';
        } else {
            $output .= 'No user match';
            $output .= db_error();
        }
        return $output;
    }

}

?>