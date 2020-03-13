<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
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


require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../forum/forum_utils.php';

if (!user_isloggedin()) {
    exit_not_logged_in();
    return;
}

$request = HTTPRequest::instance();

$vFrm = new Valid_UInt('forum_id');
$vFrm->required();

$vUid = new Valid_UInt('user_id');
$vUid->required();

$vMthr = new Valid_UInt('mthread');
$vMthr->required();

if ($request->isPost() && $request->exist('submit')) {
    if ($request->valid($vUid)) {
        $uid = $request->get('user_id');
    }
    if ($request->validArray($vMthr)) {
        $mthread = $request->get('mthread');
    }
    if ($request->valid($vFrm)) {
        $forum_id = $request->get('forum_id');
    }

    //set user-specific thread monitoring preferences
    if (forum_thread_monitor($mthread, $uid, $forum_id)) {
        $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('forum_monitor_thread', 'monitor_success');
    } else {
        $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('forum_monitor_thread', 'monitor_fail');
    }
}

if ($request->valid($vFrm)) {
    //Cast forum_id in order to prevent from XSS attacks
    $forum_id = $request->get('forum_id');

    // Check permissions
    if (!forum_utils_access_allowed($forum_id)) {
        exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('forum_forum', 'forum_restricted'));
    }

    $qry = sprintf(
        'SELECT group_id,forum_name,is_public'
            . ' FROM forum_group_list'
            . ' WHERE group_forum_id=%d',
        db_ei($forum_id)
    );
    $result = db_query($qry);
    $group_id = db_result($result, 0, 'group_id');
    $forum_name = db_result($result, 0, 'forum_name');

    $pm = ProjectManager::instance();
    $params = array('title' => $pm->getProject($group_id)->getPublicName() . ' forum: ' . $forum_name,
                      'pv'   => isset($pv) ? $pv : false);
    forum_header($params);

    $sql = sprintf(
        'SELECT user.user_name,user.realname,forum.has_followups,user.user_id,forum.msg_id,forum.group_forum_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to, forum_group_list.group_id'
            . ' FROM forum,user,forum_group_list'
            . ' WHERE forum.group_forum_id=%d'
            . ' AND user.user_id=forum.posted_by'
            . ' AND forum.is_followup_to=0'
            . ' AND forum_group_list.group_forum_id = forum.group_forum_id',
        db_ei($forum_id)
    );
    $result = db_query($sql);
    $rows = db_numrows($result);

    if (!$result || $rows < 1) {
        //empty forum
        $ret_val = $GLOBALS['Language']->getText('forum_forum', 'no_msg', $forum_name) . '<P>' . db_error();
    } else {
        $title_arr = array();
        $title_arr[] = $GLOBALS['Language']->getText('forum_monitor_thread', 'tmonitor');
        $title_arr[] = $GLOBALS['Language']->getText('forum_forum', 'thread');
        $title_arr[] = $GLOBALS['Language']->getText('forum_forum', 'author');
        $title_arr[] = $GLOBALS['Language']->getText('forum_forum', 'date');

        $ret_val = html_build_list_table_top($title_arr);

        $user_id = UserManager::instance()->getCurrentUser()->getId();

        if (user_monitor_forum($forum_id, $user_id)) {
            $disabled = "disabled";
        } else {
            $disabled = "";
        }

        $i = 0;
        while ($i < $rows) {
            $thr_id = db_result($result, $i, 'thread_id');
            if (user_monitor_forum_thread($thr_id, $user_id)) {
                $monitored = "CHECKED";
            } else {
                $monitored = "";
            }

            $ret_val .= '<script language="JavaScript">
		    	<!--
				function checkAll(val) {
			      al=document.thread_monitor;
			      len = al.elements.length;
                  var i=0;
                  for(i=0 ; i<len ; i++) {
                      if (al.elements[i].name==\'mthread[]\') {al.elements[i].checked=val;}
                  }
			    }
		       //-->
		       </script>';

            $ret_val .= '
		    	    <TR class="' . util_get_alt_row_color($i) . '">' .
            '<TD align="center"><FORM NAME="thread_monitor" action="?" METHOD="POST">' .
            '<INPUT TYPE="hidden" NAME="thread_id" VALUE="' . $thr_id . '">' .
            '<INPUT TYPE="hidden" NAME="user_id" VALUE="' . Codendi_HTMLPurifier::instance()->purify($user_id) . '">' .
            '<INPUT TYPE="hidden" NAME="forum_id" VALUE="' . $forum_id . '">' .
            '<INPUT TYPE="checkbox" ' . $disabled . ' NAME="mthread[]" VALUE="' . $thr_id . '" ' . $monitored . '></TD>' .
            '<TD><A HREF="/forum/message.php?msg_id=' .
            db_result($result, $i, 'msg_id') . '">' .
            '<IMG SRC="' . util_get_image_theme("msg.png") . '" BORDER=0 HEIGHT=12 WIDTH=10> ';
            $monitorer = UserManager::instance()->getUserByUserName(db_result($result, $i, 'user_name'));
            $ret_val .= db_result($result, $i, 'subject') . '</A></TD>' .
            '<TD>' . UserHelper::instance()->getLinkOnUser($monitorer) . '</TD>' .
            '<TD>' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, $i, 'date')) . '</TD></TR>';
            $i++;
        }
        $ret_val .= '</TABLE><a href="javascript:checkAll(1)">' . $GLOBALS['Language']->getText('tracker_include_report', 'check_all_items') . '</a>' .
                       ' - <a href="javascript:checkAll(0)">' . $GLOBALS['Language']->getText('tracker_include_report', 'clear_all_items') . ' </a>' .
                    '<P><INPUT TYPE="submit" ' . $disabled . ' NAME="submit"></FORM>';
    }

    echo $ret_val;

    if (user_monitor_forum($forum_id, $user_id)) {
        $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('forum_monitor_thread', 'notice');
    }

    forum_footer($params);
} else {
    forum_header(array('title' => $GLOBALS['Language']->getText('global', 'error')));
    $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('forum_forum', 'choose_forum_first');
    forum_footer(array());
}
