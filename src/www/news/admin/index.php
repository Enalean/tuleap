<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

require_once __DIR__ . '/../../include/pre.php';

//common forum tools which are used during the creation/editing of news items
require_once __DIR__ . '/../../forum/forum_utils.php';
require_once __DIR__ . '/../../project/admin/ugroup_utils.php';


$request = HTTPRequest::instance();

if ($request->valid(new Valid_GroupId())) {
    $group_id = $request->get('group_id');
} else {
    $group_id = null;
}

if ($request->valid(new Valid_UInt('id'))) {
    $id = $request->get('id');
} else {
    $id = null;
}

$pm = ProjectManager::instance();
// admin pages can be reached by news admin (N2) or project admin (A)
if ($group_id && $group_id != ForgeConfig::get('sys_news_group') && (user_ismember($group_id, 'A') || user_ismember($group_id, 'N2'))) {
    /*
        Per-project admin pages.
        Shows their own news items so they can edit/update.
        If their news is on the homepage, and they edit, it is removed from
            homepage.
    */
    if ($request->get('post_changes') && $request->get('approve')) {
        $validIsPrivate = new Valid_WhiteList('is_private', [0, 1]);
        if ($request->valid($validIsPrivate)) {
            $is_private = $request->get('is_private');
        } else {
            $is_private = 0;
        }
        $validStatus = new Valid_WhiteList('status', [0, 4]);
        if ($request->valid($validStatus)) {
            $status = $request->get('status');
        } else {
            $status = 0;
        }
        $validSummary = new Valid_String('summary');
        $validSummary->setErrorMessage('Summary is required');
        $validSummary->required();

        $validDetails = new Valid_Text('details');

        if ($request->valid($validSummary) && $request->valid($validDetails)) {
            $sql    = "UPDATE news_bytes SET is_approved=" . db_ei($status) . ", summary='" . db_es($request->get('summary')) . "', " .
                "details='" . db_es($request->get('details')) . "' WHERE id=" . db_ei($id) . " AND group_id=" . db_ei($group_id);
            $result = db_query($sql);

            if (! $result) {
                $GLOBALS['Response']->addFeedback('error', $Language->getText('news_admin_index', 'group_update_err'));
            } else {
                $GLOBALS['Response']->addFeedback('info', $Language->getText('news_admin_index', 'project_newsbyte_updated'));

                // update/create  news permissions
                $qry1     = "SELECT * FROM news_bytes WHERE id=" . db_ei($id);
                $res1     = db_query($qry1);
                $forum_id = db_result($res1, 0, 'forum_id');
                $res2     = news_read_permissions($forum_id);
                if (db_numrows($res2) > 0) {
                    //permission on this news is already defined, have to be updated
                    news_update_permissions($forum_id, $is_private, $group_id);
                } else {
                    //permission of this news not yet defined
                    if ($is_private) {
                        news_insert_permissions($forum_id, $group_id);
                    }
                }

                // extract cross references
                $reference_manager = ReferenceManager::instance();
                $reference_manager->extractCrossRef($request->get('summary'), $forum_id, ReferenceManager::REFERENCE_NATURE_NEWS, $group_id);
                $reference_manager->extractCrossRef($request->get('details'), $forum_id, ReferenceManager::REFERENCE_NATURE_NEWS, $group_id);
            }
        }
    }

    $project = $pm->getProject($group_id);
    news_header(
        \Tuleap\Layout\HeaderConfigurationBuilder::get($GLOBALS['Language']->getText('news_admin_index', 'title'))
            ->inProject($project, Service::NEWS)
            ->build()
    );

    $purifier = Codendi_HTMLPurifier::instance();

    echo '<H3>' . $Language->getText('news_admin_index', 'news_admin') . '</H3>';
    echo '<a href="/news/admin/choose_items.php?project_id=' . $purifier->purify(urlencode($group_id)) . '">' . $Language->getText('news_admin_index', 'choose_display') . '</a>';

    if (! $request->get('post_changes') && $request->get('approve')) {
     /*
      Show the submit form
     */

        $sql    = "SELECT * FROM news_bytes WHERE id=" . db_ei($id) . " AND group_id=" . db_ei($group_id);
        $result = db_query($sql);
        if (db_numrows($result) < 1) {
            exit_error($Language->getText('global', 'error'), $Language->getText('news_admin_index', 'not_found_err'));
        }
        $username = user_getname(db_result($result, 0, 'submitted_by'));
        $forum_id = db_result($result, 0, 'forum_id');
        $res      = news_read_permissions($forum_id);
     // check on db_result($res,0,'ugroup_id') == $UGROUP_ANONYMOUS only to be consistent
     // with ST DB state
        if (db_numrows($res) < 1 || (db_result($res, 0, 'ugroup_id') == $UGROUP_ANONYMOUS)) {
            $check_private = "";
            $check_public  = "CHECKED";
        } else {
            $check_private = "CHECKED";
            $check_public  = "";
        }

        echo '
        <H3>' . $purifier->purify($Language->getText('news_admin_index', 'approve_for', $project->getPublicName())) . '</H3>
		<P>
		<FORM ACTION="" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $purifier->purify(db_result($result, 0, 'group_id')) . '">
		<INPUT TYPE="HIDDEN" NAME="id" VALUE="' . $purifier->purify(db_result($result, 0, 'id')) . '">

		<B>' . $Language->getText('news_admin_index', 'submitted_by') . ':</B> <a href="/users/' . $purifier->purify($username) . '">' . $purifier->purify($username) . '</a><BR>
		<INPUT TYPE="HIDDEN" NAME="approve" VALUE="1">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="1">

 		<B>' . $Language->getText('global', 'status') . ':</B><BR>
                <INPUT TYPE="RADIO" NAME="status" VALUE="0" CHECKED> ' . $Language->getText('news_admin_index', 'displayed') . '<BR>
                <INPUT TYPE="RADIO" NAME="status" VALUE="4"> ' . $Language->getText('news_admin_index', 'delete') . '<BR>

		<B>' . $Language->getText('news_submit', 'news_privacy') . ':</B><BR>
		<INPUT TYPE="RADIO" NAME="is_private" VALUE="0" ' . $check_public . '> ' . $Language->getText('news_submit', 'public_news') . '<BR>
		<INPUT TYPE="RADIO" NAME="is_private" VALUE="1" ' . $check_private . '> ' . $Language->getText('news_submit', 'private_news') . '<BR>

		<B>' . $Language->getText('news_admin_index', 'subject') . ':</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" VALUE="' . $purifier->purify(db_result($result, 0, 'summary')) . '"><BR>
		<B>' . $Language->getText('news_admin_index', 'details') . ':</B><BR>
		<TEXTAREA NAME="details" ROWS="8" COLS="50" WRAP="SOFT">' . $purifier->purify(db_result($result, 0, 'details')) . '</TEXTAREA><P>
		<B>' . $purifier->purify($Language->getText('news_admin_index', 'if_edit_delete', ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME))) . '</B><BR>
		<INPUT TYPE="SUBMIT" VALUE="' . $Language->getText('global', 'btn_submit') . '">
		</FORM>';
    } else {
     /*
      Show list of waiting news items
     */

        $sql    = "SELECT * FROM news_bytes WHERE is_approved <> 4 AND group_id=" . db_ei($group_id) . " ORDER BY date DESC";
        $result = db_query($sql);
        $rows   = db_numrows($result);
        if ($rows < 1) {
            echo '
                <H4>' . $purifier->purify($Language->getText('news_admin_index', 'no_queued_item_found_for', $project->getPublicName())) . '</H1>';
        } else {
            echo '
                <H4>' . $purifier->purify($Language->getText('news_admin_index', 'new_items', $project->getPublicName())) . '</H4>
				<P>';
            for ($i = 0; $i < $rows; $i++) {
                echo '
				<A HREF="/news/admin/?approve=1&id=' . $purifier->purify(db_result($result, $i, 'id')) . '&group_id=' .
                    $purifier->purify(db_result($result, $i, 'group_id')) . '">' .
                    $purifier->purify(db_result($result, $i, 'summary')) . '</A><BR>';
            }
        }
    }
    news_footer([]);
} else {
    exit_error($Language->getText('news_admin_index', 'permission_denied'), $Language->getText('news_admin_index', 'need_to_be_admin'));
}
