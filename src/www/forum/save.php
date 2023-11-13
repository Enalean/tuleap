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
        if (! forum_utils_access_allowed($forum_id)) {
            exit_error($Language->getText('global', 'error'), _('Forum is restricted'));
        }

     //If the forum is associated to a private news, non-allowed users shouldn't be able to save their places in this forum
        $qry = "SELECT * FROM news_bytes WHERE forum_id=" . db_ei($forum_id);
        $res = db_query($qry);
        if (db_numrows($res) > 0) {
            if (! forum_utils_news_access($forum_id)) {
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

        $group_id   = db_result($result, 0, 'group_id');
        $forum_name = db_result($result, 0, 'forum_name');


        forum_header(\Tuleap\Layout\HeaderConfigurationBuilder::get(_('Save your place'))
            ->build());

        echo '
			<H2>' . _('Save Your Place') . '</H2>';

        $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());

        $sql = "SELECT * FROM forum_saved_place WHERE user_id='" . $db_escaped_user_id . "' AND forum_id=" . db_ei($forum_id);

        $result = db_query($sql);

        if (! $result || db_numrows($result) < 1) {
         /*
          User is not already monitoring thread, so
          insert a row so monitoring can begin
         */
            $sql = "INSERT INTO forum_saved_place (forum_id,user_id,save_date) VALUES (" . db_ei($forum_id) . ",'" . $db_escaped_user_id . "','" . time() . "')";

            $result = db_query($sql);

            if (! $result) {
                echo "<span class=\"highlight\">" . _('Error inserting into forum_saved_place') . "</span>";
                echo db_error();
            } else {
                echo "<span class=\"highlight\"><H3>" . _('Your place was saved') . "</H3></span>";
                echo '<P>' . _('New messages will be highlighted when you return.');
            }
        } else {
            $sql    = "UPDATE forum_saved_place SET save_date='" . time() . "' WHERE user_id='" . $db_escaped_user_id . "' AND forum_id=" . db_ei($forum_id);
            $result = db_query($sql);

            if (! $result) {
                echo "<span class=\"highlight\">" . _('Error updating time in forum_saved_place') . "</span>";
                echo db_error();
            } else {
                echo "<span class=\"highlight\"><H3>" . _('Your place was saved') . "</H3></span>";
                echo "<P>" . _('New messages will be highlighted when you return.');
            }
        }
        forum_footer();
    } else {
        forum_header(\Tuleap\Layout\HeaderConfigurationBuilder::get(_('Choose a forum First'))
            ->build());
        echo '
			<H1>' . _('Error - choose a forum first') . '</H1>';
        forum_footer();
    }
} else {
    exit_not_logged_in();
}
