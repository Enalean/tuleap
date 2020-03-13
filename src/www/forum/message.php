<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
 *
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
 */

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../forum/forum_utils.php';

$request = HTTPRequest::instance();

$params = array();

$vMsg = new Valid_UInt('msg_id');
$vMsg->required();
if ($request->valid($vMsg)) {
    $msg_id = $request->get('msg_id');

    if ($request->valid(new Valid_Pv())) {
        $pv = $request->get('pv');
    } else {
        $pv = 0;
    }

    /*
        Figure out which group this message is in, for the sake of the admin links
    */
    $result = db_query("SELECT forum_group_list.group_id,forum_group_list.forum_name,forum.group_forum_id,forum.thread_id " .
    "FROM forum_group_list,forum WHERE forum_group_list.group_forum_id=forum.group_forum_id AND forum.msg_id=" . db_ei($msg_id));

    $group_id = db_result($result, 0, 'group_id');
    $forum_id = db_result($result, 0, 'group_forum_id');
    $thread_id = db_result($result, 0, 'thread_id');
    $forum_name = db_result($result, 0, 'forum_name');

        // Check permissions
    if (!forum_utils_access_allowed($forum_id)) {
        exit_error($Language->getText('global', 'error'), $Language->getText('forum_forum', 'forum_restricted'));
    }

    //check if the message is a comment on a piece of news.  If so, check permissions on this news
    $qry = "SELECT * FROM news_bytes WHERE forum_id=" . db_ei($forum_id);
    $res = db_query($qry);
    if (db_numrows($res) > 0) {
        if (!forum_utils_news_access($forum_id)) {
            exit_error($Language->getText('global', 'error'), $Language->getText('news_admin_index', 'permission_denied'));
        }
    }

    $params = array('title' => db_result($result, 0, 'subject'),
                      'pv'   => isset($pv) ? $pv : false);
    forum_header($params);

    echo "<P>";

    $sql = "SELECT user.user_name,forum.group_forum_id,forum.thread_id,forum.subject,forum.date,forum.body " .
    "FROM forum,user WHERE user.user_id=forum.posted_by AND forum.msg_id=" . db_ei($msg_id);

    $result = db_query($sql);

    if (!$result || db_numrows($result) < 1) {
     /*
      Message not found
     */
        return 'message not found.\n';
    }

    $title_arr = array();
    $title_arr[] = 'Message: ' . $msg_id;

    echo html_build_list_table_top($title_arr);

    $purifier = Codendi_HTMLPurifier::instance();
    $poster   = UserManager::instance()->getUserByUserName(db_result($result, 0, "user_name"));
    echo "<TR><TD class=\"threadmsg\">\n";
    echo $Language->getText('forum_message', 'by') . ": " . UserHelper::instance()->getLinkOnUser($poster) . "<BR>";
    echo $Language->getText('forum_message', 'date') . ": " . format_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, 0, "date")) . "<BR>";
    echo $Language->getText('forum_message', 'subject') . ": " . db_result($result, 0, "subject") . "<P>";
    echo $purifier->purify(db_result($result, 0, 'body'), CODENDI_PURIFIER_BASIC, $group_id);
    echo "</TD></TR>";

    $crossref_fact = new CrossReferenceFactory($msg_id, ReferenceManager::REFERENCE_NATURE_FORUMMESSAGE, $group_id);
    $crossref_fact->fetchDatas();
    if ($crossref_fact->getNbReferences() > 0) {
        echo '<tr>';
        echo ' <td class="forum_reference_separator">';
        echo '  <b> ' . $Language->getText('cross_ref_fact_include', 'references') . '</b>';
        echo $crossref_fact->getHTMLDisplayCrossRefs();
        echo ' </td>';
        echo '</tr>';
    }

    echo "</TABLE>";

    if ($pv == 0) {
    /*
     Show entire thread
    */
        echo '<BR>&nbsp;<P><H3>' . $Language->getText('forum_message', 'thread_view') . '</H3>';

        //highlight the current message in the thread list
        $current_message = $msg_id;
        echo show_thread(db_result($result, 0, 'thread_id'));

    /*
     Show post followup form
    */

        echo '<P>&nbsp;<P>';
        echo '<CENTER><h3>' . $Language->getText('forum_message', 'post_followup') . '</h3></CENTER>';

        show_post_form(db_result($result, 0, 'group_forum_id'), db_result($result, 0, 'thread_id'), $msg_id, db_result($result, 0, 'subject'));
    }
} else {
    exit_error($Language->getText('global', 'error'), $Language->getText('forum_message', 'choose_msg_first'));
}

forum_footer($params);
