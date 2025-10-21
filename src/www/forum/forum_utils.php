<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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
 *
 */

/*

    Message Forums
    By Tim Perdue, Sourceforge, 11/99

    Massive rewrite by Tim Perdue 7/2000 (nested/views/save)

*/

use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;
use Tuleap\Layout\HeaderConfiguration;

function forum_header(HeaderConfiguration $params)
{
    global $HTML,$group_id,$forum_name,$thread_id,$msg_id,$forum_id,$et,$et_cookie,$Language;

    \Tuleap\Project\ServiceInstrumentation::increment('forums');

    $project = ProjectManager::instance()->getProjectById((int) $group_id);

    //this is just a regular forum, not a news item
    if (! $project->isError()) {
        $service_forum = $project->getService(Service::FORUM);
        if ($service_forum !== null) {
            $breadcrumb = new BreadCrumb(
                new BreadCrumbLink($service_forum->getInternationalizedName(), $service_forum->getUrl())
            );
            if (user_ismember($group_id, 'F2')) {
                $admin_link = new BreadCrumbLink(
                    _('Administration'),
                    '/forum/admin/?group_id=' . urlencode((string) $group_id),
                );

                $sub_items = new BreadCrumbSubItems();
                $sub_items->addSection(new SubItemsUnlabelledSection(new BreadCrumbLinkCollection([$admin_link])));

                $breadcrumb->setSubItems($sub_items);
            }
            $breadcrumb_collection = new BreadCrumbCollection();
            $breadcrumb_collection->addBreadCrumb($breadcrumb);
            $GLOBALS['HTML']->addBreadcrumbs($breadcrumb_collection);
        }
    }
    site_project_header($project, $params);

    /*
        Show horizontal forum links
    */
    if ($forum_id && $forum_name) {
        echo '<P><H3>' . _('Discussion Forums') . ': <A HREF="/forum/forum.php?forum_id=' . $forum_id . '">' . $forum_name . '</A></H3>';
    }

    if ($params->printer_version) {
        $request = HTTPRequest::instance();
        if ($forum_id && user_isloggedin() && ! $request->exist('delete')) {
            echo '<A HREF="/forum/save.php?forum_id=' . $forum_id . '">';
            echo html_image('ic/save.png', []) . ' ' . _('Save Place') . '</A> | ';
            print ' <a href="forum.php?forum_id=' . $forum_id . '#start_new_thread">';
            echo html_image('ic/thread.png', []) . ' ' . _('Start New Thread') . '</A> | ';
            if (isset($msg_id) && $msg_id) {
                echo "<A HREF='?msg_id=$msg_id&pv=1'><img src='" . util_get_image_theme('msg.png') . "' border='0'>&nbsp;" . $Language->getText('global', 'printer_version') . '</A>';
            } else {
                echo "<A HREF='?forum_id=$forum_id&pv=1'><img src='" . util_get_image_theme('msg.png') . "' border='0'>&nbsp;" . $Language->getText('global', 'printer_version') . '</A>';
            }
            echo '</B><P>';
        }
    }
}

function forum_footer()
{
    site_project_footer([]);
}

function show_thread($thread_id, $et = 0)
{
    global $Language;
    /*
        Takes a thread_id and fetches it, then invokes show_submessages to nest the threads

        $et is whether or not the forum is "expanded" or in flat mode
    */
    global $total_rows,$is_followup_to,$subject,$forum_id,$current_message;

    $ret_val = '';
    $sql     = 'SELECT user.user_name,forum.has_followups,forum.msg_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to ' .
    "FROM forum,user WHERE forum.thread_id='" . db_ei($thread_id) . "' AND user.user_id=forum.posted_by AND forum.is_followup_to='0' " .
    'ORDER BY forum.msg_id DESC;';

    $result = db_query($sql);

    $total_rows = 0;

    if (! $result || db_numrows($result) < 1) {
        return _('Broken Thread');
    } else {
        $title_arr   = [];
        $title_arr[] = _('Thread');
        $title_arr[] = _('Author');
        $title_arr[] = _('Date');

        $ret_val .= html_build_list_table_top($title_arr);

        $rows           = db_numrows($result);
        $is_followup_to = db_result($result, ($rows - 1), 'msg_id');
        $subject        = db_result($result, ($rows - 1), 'subject');
   /*
    Short - term compatibility fix. Leaving the iteration in for now -
    will remove in the future. If we remove now, some messages will become hidden

    No longer iterating here. There should only be one root message per thread now.
    Messages posted at the thread level are shown as followups to the first message
   */
        for ($i = 0; $i < $rows; $i++) {
            $total_rows++;
            $ret_val .= '<TR class="' . util_get_alt_row_color($total_rows) . '"><TD>' .
            (($current_message != db_result($result, $i, 'msg_id')) ? '<A HREF="/forum/message.php?msg_id=' . db_result($result, $i, 'msg_id') . '">' : '') .
            '<IMG SRC="' . util_get_image_theme('msg.png') . '" BORDER=0 HEIGHT=12 WIDTH=10> ';
         /*
          See if this message is new or not
         */
            if (get_forum_saved_date($forum_id) < db_result($result, $i, 'date')) {
                $ret_val .= '<B>';
            }

            $poster   = UserManager::instance()->getUserByUserName(db_result($result, $i, 'user_name'));
            $ret_val .= db_result($result, $i, 'subject') . '</A></TD>' .
            '<TD>' . UserHelper::instance()->getLinkOnUser($poster) . '</TD>' .
            '<TD>' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, $i, 'date')) . '</TD></TR>';
         /*
          Show the body/message if requested
         */
            if ($et == 1) {
                $ret_val .= '
				<TR class="' . util_get_alt_row_color($total_rows) . '"><TD>&nbsp;</TD><TD COLSPAN=2>' .
                nl2br(db_result($result, $i, 'body')) . '</TD><TR>';
            }

            if (db_result($result, $i, 'has_followups') > 0) {
                $ret_val .= show_submessages($thread_id, db_result($result, $i, 'msg_id'), 1, $et);
            }
        }
        $ret_val .= '</TABLE>';
    }
    return $ret_val;
}

function show_submessages($thread_id, $msg_id, $level, $et = 0)
{
    /*
        Recursive. Selects this message's id in this thread,
        then checks if any messages are nested underneath it.
        If there are, it calls itself, incrementing $level
        $level is used for indentation of the threads.
    */
    global $total_rows,$forum_id,$current_message;

    $sql = 'SELECT user.user_name,forum.has_followups,forum.msg_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to ' .
    'FROM forum,user WHERE forum.thread_id=' . db_ei($thread_id) . ' AND user.user_id=forum.posted_by AND forum.is_followup_to=' . db_ei($msg_id) . ' ' .
    'ORDER BY forum.msg_id ASC;';

    $result  = db_query($sql);
    $rows    = db_numrows($result);
    $ret_val = '';
    if ($result && $rows > 0) {
        for ($i = 0; $i < $rows; $i++) {
         /*
          Is this row's background shaded or not?
         */
            $total_rows++;

            $ret_val .= '
				<TR class="' . util_get_alt_row_color($total_rows) . '"><TD NOWRAP>';
         /*
          How far should it indent?
         */
            for ($i2 = 0; $i2 < $level; $i2++) {
                $ret_val .= ' &nbsp; &nbsp; &nbsp; ';
            }

         /*
          If it this is the message being displayed, don't show a link to it
         */
            $ret_val .= (($current_message != db_result($result, $i, 'msg_id')) ?
            '<A HREF="/forum/message.php?msg_id=' . db_result($result, $i, 'msg_id') . '">' : '') .
            '<IMG SRC="' . util_get_image_theme('msg.png') . '" BORDER=0 HEIGHT=12 WIDTH=10> ';
         /*
          See if this message is new or not
         */
            if (get_forum_saved_date($forum_id) < db_result($result, $i, 'date')) {
                $ret_val .= '<B>';
            }

            $poster   = UserManager::instance()->getUserByUserName(db_result($result, $i, 'user_name'));
            $ret_val .= db_result($result, $i, 'subject') . '</A></TD>' .
            '<TD>' . UserHelper::instance()->getLinkOnUser($poster) . '</TD>' .
            '<TD>' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, $i, 'date')) . '</TD></TR>';

         /*
          Show the body/message if requested
         */
            if ($et == 1) {
                   $ret_val .= '
					<TR class="' . util_get_alt_row_color($total_rows) . '"><TD>&nbsp;</TD><TD COLSPAN=2>' .
                    nl2br(db_result($result, $i, 'body')) . '</TD><TR>';
            }

            if (db_result($result, $i, 'has_followups') > 0) {
                /*
                 Call yourself, incrementing the level
                */
                $ret_val .= show_submessages($thread_id, db_result($result, $i, 'msg_id'), ($level + 1), $et);
            }
        }
    }
    return $ret_val;
}

function get_next_thread_id()
{
    global $Language;
    /*
        Get around limitation in MySQL - Must use a separate table with an auto-increment
    */
    $result = db_query("INSERT INTO forum_thread_id VALUES ('')");

    if (! $result) {
        echo '<H1>' . $Language->getText('global', 'error') . '!</H1>';
        echo db_error();
        exit;
    } else {
        return db_insertid($result);
    }
}

function get_forum_saved_date($forum_id)
{
    /*
        return the save_date for this user
    */
    global $forum_saved_date;

    if ($forum_saved_date) {
        return $forum_saved_date;
    } else {
        $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
        $sql                = "SELECT save_date FROM forum_saved_place WHERE user_id='" . $db_escaped_user_id . "' AND forum_id=" . db_ei($forum_id);
        $result             = db_query($sql);
        if ($result && db_numrows($result) > 0) {
            $forum_saved_date = db_result($result, 0, 'save_date');
            return $forum_saved_date;
        } else {
         //highlight new messages from the past week only
            $forum_saved_date = (time() - 604800);
            return $forum_saved_date;
        }
    }
}

function forum_utils_access_allowed($forum_id)
{
    $result = db_query('SELECT group_id,is_public FROM forum_group_list WHERE group_forum_id=' . db_ei($forum_id));

    if (db_result($result, 0, 'is_public') != '1') {
        $forum_group_id = db_result($result, 0, 'group_id');
        if (! user_isloggedin() || ! user_ismember($forum_group_id)) {
            // If this is a private forum, kick 'em out
            return false;
        }
    }
    return true;
}

function forum_utils_get_styles()
{
    return ['nested', 'flat', 'threaded', 'nocomments'];
}

function forum_can_be_public(Project $project)
{
    return $project->getAccess() == Project::ACCESS_PUBLIC ||
        $project->getAccess() == Project::ACCESS_PUBLIC_UNRESTRICTED;
}

function forum_is_public_value_allowed(Project $project, $forum_status)
{
    return (
            in_array($project->getAccess(), [Project::ACCESS_PRIVATE, Project::ACCESS_PRIVATE_WO_RESTRICTED], true)
            && ($forum_status == 0 || $forum_status == 9)
        ) || forum_can_be_public($project);
}
