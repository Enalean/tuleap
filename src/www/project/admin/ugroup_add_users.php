<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
//
// Originally written by Nicolas Terray 2008, Codendi Team, Xerox
//

require_once('pre.php');
require_once('www/project/admin/project_admin_utils.php');
require_once 'common/project/UGroupManager.class.php';

$request =& HTTPRequest::instance();

$group_id = $request->getValidated('group_id', 'GroupId', 0);

//Only project admin
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

$user_helper = UserHelper::instance();

function display_user_result_table($res) {
    $user_helper = UserHelper::instance();
    $hp = Codendi_HTMLPurifier::instance();
    $nb_cols = 3;
    if (db_numrows($res)) {
        echo '<table><tr>';
        $i = 0;
        while($data = db_fetch_array($res)) {
            if ($i++ % $nb_cols == 0) {
                echo '</tr><tr>';
            }
            $action     = 'add';
            $background = 'eee';
            if ($data['is_on']) {
                $action     = 'remove';
                $background = 'dcf7c4';
            }
            echo '<td width="'. round(100/$nb_cols) .'%">';
            echo '<div style="border:1px solid #CCC; background: #'. $background .'; padding:10px 5px; position:relative">';
            echo '<table width="100%"><tr><td><a href="/users/'. $hp->purify($data['user_name']) .'/">'. $hp->purify($user_helper->getDisplayName($data['user_name'], $data['realname'])) .'</a></td>';
            echo '<td style="text-align:right;">';
            echo project_admin_display_bullet_user($data['user_id'], $action);
            echo '</td></tr></table>';
            echo '<div style="color:#666; ">'. $data['email'] .'</div>';
            echo '</div>';
            echo '</td>';
        }
        while($i++ % $nb_cols != 0) {
            echo '<td width="'. round(100/$nb_cols) .'%"></td>';
        }
        echo '</tr></table>';
    } else {
        echo 'No user match';
        echo db_error();
    }
}

$ugroup_id = $request->getValidated('ugroup_id', 'uint', 0);
if ($ugroup_id) {
    $uGroupMgr = new UGroupManager();
    $uGroup    = new UGroup(array('ugroup_id' => $ugroup_id));
    $ugroupUpdateUsersAllowed = !$uGroup->isBound();
    $em->processEvent(Event::UGROUP_UPDATE_USERS_ALLOWED, array('ugroup_id' => $ugroup_id, 'allowed' => &$ugroupUpdateUsersAllowed));
    if ($ugroupUpdateUsersAllowed) {
        $res = ugroup_db_get_ugroup($ugroup_id);
        if ($res) {
            $ugroup_name = db_result($res, 0, 'name');
            $hp = Codendi_HTMLPurifier::instance();
            
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
            $in_project       = $request->getValidated('in_project', $valid_in_project, $group_id);
            
            $user = $request->get('user');
            if ($user && is_array($user)) {
                list($user_id, $action) = each($user);
                $user_id = (int)$user_id;
                if ($user_id) {
                    switch($action) {
                    case 'add':
                        ugroup_add_user_to_ugroup($group_id, $ugroup_id, $user_id);
                        break;
                    case 'remove':
                        ugroup_remove_user_from_ugroup($group_id, $ugroup_id, $user_id);
                        break;
                    default:
                        break;
                    }
                    $GLOBALS['Response']->redirect('?group_id='. (int)$group_id .
                        '&ugroup_id='. (int)$ugroup_id .
                        '&offset='. (int)$offset .
                        '&number_per_page='. (int)$number_per_page .
                        '&search='. urlencode($search) .
                        '&begin='. urlencode($begin) .
                        '&in_project='. (int)$in_project
                    );
                }
            }
            //Display the page
            project_admin_header(array(
                'title'=> $Language->getText('project_admin_editugroup','edit_ug'),
                'group'=>$group_id,
                'help' => 'UserGroups.html#UGroupCreation')
            );
            echo '<P><h2>'. $Language->getText('project_admin_editugroup','add_users_to').' '.  $hp->purify($ugroup_name, CODENDI_PURIFIER_CONVERT_HTML)  .'</h2>';
            
            //Display the form
            $selected = 'selected="selected"';
            echo '<form action="" method="GET">';
            echo '<table><tr valign="top"><td>';
            
            //Display existing members
            echo '<fieldset><legend>'. $Language->getText('project_admin_editugroup','members').'</legend>';
            $uGroup    = $uGroupMgr->getById($request->get('ugroup_id'));
            $members   = $uGroup->getMembers();
            if (count($members) > 0) {
                echo '<table border="0" cellspacing="0" cellpadding="0" width="100%"><tbody>';
                $i = 0;
                $hp = Codendi_HTMLPurifier::instance();
                foreach ($members as $user) {
                    echo '<tr class="'. html_get_alt_row_color(++$i) .'">';
                    echo '<td style="white-space:nowrap">'. $hp->purify($user_helper->getDisplayNameFromUser($user)) .'</td>';
                    echo '<td>';
                    echo project_admin_display_bullet_user($user->getId(), 'remove');
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                echo '</fieldset>';
            } else {
                echo $Language->getText('project_admin_editugroup','group_empty');
            }
            
            echo '</td><td>';

            echo '<input type="hidden" name="group_id" value="'. (int)$group_id .'" />';
            echo '<input type="hidden" name="ugroup_id" value="'. (int)$ugroup_id .'" />';
            echo '<input type="hidden" name="offset" value="'. (int)$offset .'" />';

            //Filter
            echo '<fieldset><legend>'.$Language->getText('project_admin_editugroup','users').'</legend>';
            echo '<p>'. $Language->getText('project_admin_editugroup','search_in').' ';
            echo '<select name="in_project">';
            echo '<option value="0" '. ( !$in_project ? $selected : '') .'>'. $Language->getText('project_admin_editugroup','any_project') .'</option>';
            echo '<option value="'. (int)$group_id .'" '. ($in_project == $group_id ? $selected : '') .'>'. $Language->getText('project_admin_editugroup','this_project') .'</option>';
            echo '</select>';
            echo $Language->getText('project_admin_editugroup','name_contains').' ';
            
            //contains
            echo '<input type="text" name="search" value="'.  $hp->purify($search, CODENDI_PURIFIER_CONVERT_HTML) .'" class="textfield_medium" /> ';
            //begin
            echo $Language->getText('project_admin_editugroup','begins').' ';
            echo '<select name="begin">';
            echo '<option value="" '. (in_array($begin, $allowed_begin_values) ? $selected : '') .'></option>';
            foreach($allowed_begin_values as $b) {
                echo '<option value="'. $b .'" '. ($b == $begin ? $selected : '') .'>'. $b .'</option>';
            }
            echo '</select>. ';
            
            //Display
            echo '<span style="white-space:nowrap;">'.$Language->getText('project_admin_editugroup','show').' ';
            //number per page
            echo '<select name="number_per_page">';
            echo '<option '. ($number_per_page == 15 ? $selected : '') .'>15</option>';
            echo '<option '. ($number_per_page == 30 ? $selected : '') .'>30</option>';
            echo '<option '. ($number_per_page == 60 ? $selected : '') .'>60</option>';
            if (!in_array($number_per_page, array(15, 30, 60))) {
                echo '<option '. $selected .'>'. (int)$number_per_page .'</option>';
            }
            echo '</select> ';
            echo $Language->getText('project_admin_editugroup','users_per_page').' ';
            
            
            echo '<input type="submit" name="browse" value="Browse" /></span>';
            echo '</p>';
            
            $sql = "SELECT SQL_CALC_FOUND_ROWS user.user_id, user_name, realname, email, IF(R.user_id = user.user_id, 1, 0) AS is_on
                    FROM user NATURAL LEFT JOIN (SELECT user_id FROM ugroup_user WHERE ugroup_id=". db_ei($ugroup_id) .") AS R
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
            //echo $sql;
            $res = db_query($sql);
            $res2 = db_query('SELECT FOUND_ROWS() as nb');
            $num_total_rows = db_result($res2, 0, 'nb');
            display_user_result_table($res);
            
            //Jump to page
            $nb_of_pages = ceil($num_total_rows / $number_per_page);
            $current_page = round($offset / $number_per_page);
            echo '<div style="font-family:Verdana">Page: ';
            $width = 10;
            for ($i = 0 ; $i < $nb_of_pages ; ++$i) {
                if ($i == 0 || $i == $nb_of_pages - 1 || ($current_page - $width / 2 <= $i && $i <= $width / 2 + $current_page)) {
                    echo '<a href="?'.
                        'group_id='. (int)$group_id .
                        '&amp;ugroup_id='. (int)$ugroup_id .
                        '&amp;offset='. (int)($i * $number_per_page) .
                        '&amp;number_per_page='. (int)$number_per_page .
                        '&amp;search='. urlencode($search) .
                        '&amp;begin='. urlencode($begin) .
                        '&amp;in_project='. (int)$in_project .
                        '">';
                    if ($i == $current_page) {
                        echo '<b>'. ($i + 1) .'</b>';
                    } else {
                        echo $i + 1;
                    }
                    echo '</a>&nbsp;';
                } else if ($current_page - $width / 2 - 1 == $i || $current_page + $width / 2 + 1 == $i) {
                    echo '...&nbsp;';
                }
            }
            echo '</div>';
            
            echo '</fieldset>';

            echo '</td></tr></table>';
            
            echo '</form>';
            echo '<p><a href="/project/admin/editugroup.php?group_id='. $group_id .'&amp;ugroup_id='. $ugroup_id .'&amp;func=edit">&laquo;'.$Language->getText('project_admin_editugroup','go_back').'</a></p>';
            $GLOBALS['HTML']->footer(array());
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_editugroup','ug_not_found',array($ugroup_id,db_error())));
            $GLOBALS['Response']->redirect('/project/admin/ugroup.php?group_id='. $group_id);
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'operation_not_allowed'));
        $GLOBALS['Response']->redirect('/project/admin/editugroup.php?group_id='. $group_id .'&ugroup_id='. $ugroup_id .'&func=edit');
    }
} else {
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'missing_parameters'));
    $GLOBALS['Response']->redirect('/project/admin/ugroup.php?group_id='. $group_id);
}

?>