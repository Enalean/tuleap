<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

if (user_isloggedin()) {
    /*
        User obviously has to be logged in to save place
    */
    $vForumId = new Valid_UInt('forum_id');
    $vForumId->required();
    if ($request->valid($vForumId)) {
        $forum_id = $request->get('forum_id');
                // Check permissions
        if (!forum_utils_access_allowed($forum_id)) {
            exit_error($Language->getText('global', 'error'), $Language->getText('forum_forum', 'forum_restricted'));
        }

     //If the forum is associated to a private news, non-allowed users shouldn't be able to save their places in this forum
        $qry = "SELECT * FROM news_bytes WHERE forum_id=" . db_ei($forum_id);
        $res = db_query($qry);
        if (db_numrows($res) > 0) {
            if (!forum_utils_news_access($forum_id)) {
                exit_error($Language->getText('global', 'error'), $Language->getText('news_admin_index', 'permission_denied'));
            }
        }

     /*
      First check to see if they already saved their place
      If they have NOT, then insert a row into the db

      ELSE update the time()
     */

     /*
      Set up navigation vars
     */
        $result = db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id=" . db_ei($forum_id));

        $group_id = db_result($result, 0, 'group_id');
        $forum_name = db_result($result, 0, 'forum_name');


        forum_header(array('title' => $Language->getText('forum_save', 'save_place')));

        echo '
			<H2>' . $Language->getText('forum_save', 'save_your_place') . '</H2>';

        $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());

        $sql = "SELECT * FROM forum_saved_place WHERE user_id='" . $db_escaped_user_id . "' AND forum_id=" . db_ei($forum_id);

        $result = db_query($sql);

        if (!$result || db_numrows($result) < 1) {
         /*
          User is not already monitoring thread, so
          insert a row so monitoring can begin
         */
            $sql = "INSERT INTO forum_saved_place (forum_id,user_id,save_date) VALUES (" . db_ei($forum_id) . ",'" . $db_escaped_user_id . "','" . time() . "')";

            $result = db_query($sql);

            if (!$result) {
                echo "<span class=\"highlight\">" . $Language->getText('forum_save', 'insert_err') . "</span>";
                echo db_error();
            } else {
                echo "<span class=\"highlight\"><H3>" . $Language->getText('forum_save', 'place_saved') . "</H3></span>";
                echo '<P>' . $Language->getText('forum_save', 'msg_highlighted');
            }
        } else {
            $sql = "UPDATE forum_saved_place SET save_date='" . time() . "' WHERE user_id='" . $db_escaped_user_id . "' AND forum_id=" . db_ei($forum_id);
            $result = db_query($sql);

            if (!$result) {
                echo "<span class=\"highlight\">" . $Language->getText('forum_save', 'update_err') . "</span>";
                echo db_error();
            } else {
                echo "<span class=\"highlight\"><H3>" . $Language->getText('forum_save', 'place_saved') . "</H3></span>";
                echo "<P>" . $Language->getText('forum_save', 'msg_highlighted');
            }
        }
        forum_footer(array());
    } else {
        forum_header(array('title' => $Language->getText('forum_monitor', 'choose_forum_first')));
        echo '
			<H1>' . $Language->getText('forum_forum', 'choose_forum_first') . '</H1>';
        forum_footer(array());
    }
} else {
    exit_not_logged_in();
}
