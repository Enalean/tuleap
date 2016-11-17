<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuealp\News\Admin\AdminNewsBuilder;
use Tuealp\News\Admin\AdminApprovedNewsPresenter;
use Tuealp\News\Admin\AdminApprovalQueuePresenter;
use Tuealp\News\Admin\AdminRejectedNewsPresenter;
use Tuealp\News\Admin\AdminNewsDao;
use Tuleap\Admin\AdminPageRenderer;

require_once('pre.php');

//common forum tools which are used during the creation/editing of news items
require_once('www/forum/forum_utils.php');
require_once('www/project/admin/ugroup_utils.php');


$request =& HTTPRequest::instance();

if ($request->valid(new Valid_GroupId())) {
    $group_id = $request->get('group_id');
} else {
    $group_id = null;
}

if ($request->valid(new Valid_Uint('id'))) {
    $id = $request->get('id');
} else {
    $id = null;
}

$pm = ProjectManager::instance();
if (user_ismember($GLOBALS['sys_news_group'], 'A')) {
    /*

        News uber-user admin pages
        Show all waiting news items except those already rejected.
        Admin members of project #$sys_news_group (news project)
                can edit/change/approve news items

    */
    if ($request->get('post_changes') && $request->get('approve')) {
        $validStatus = new Valid_WhiteList('status', array(0, 1, 2));
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
            if ($status == 1) {
                /*
                    Update the db so the item shows on the home page
                */
                $sql = "UPDATE news_bytes SET is_approved='1', date='" . time() . "', " .
                    "summary='" . db_es(htmlspecialchars($request->get('summary'))) . "', details='" . db_es(htmlspecialchars($request->get('details'))) . "' WHERE id=" . db_ei($id);
                $result = db_query($sql);
                if (!$result || db_affected_rows($result) < 1) {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('news_admin_index', 'update_err'));
                } else {
                    $GLOBALS['Response']->addFeedback('info',
                        $Language->getText('news_admin_index', 'newsbyte_updated'));
                }
            } else {
                if ($status == 2) {
                    /*
                        Move msg to deleted status
                    */
                    $sql = "UPDATE news_bytes SET is_approved='2' WHERE id=" . db_ei($id);
                    $result = db_query($sql);
                    if (!$result || db_affected_rows($result) < 1) {
                        $GLOBALS['Response']->addFeedback('error',
                            $Language->getText('news_admin_index', 'update_err') . ' ' . db_error());
                    } else {
                        $GLOBALS['Response']->addFeedback('info',
                            $Language->getText('news_admin_index', 'newsbyte_deleted'));
                    }
                }
            }
            $GLOBALS['Response']->redirect('/news/admin');
        }
    }

    if ($request->get('approve')) {
        /*
            Show the submit form
        */

        $sql = "SELECT groups.unix_group_name,news_bytes.* " .
            "FROM news_bytes,groups WHERE id=" . db_ei($id) . " " .
            "AND news_bytes.group_id=groups.group_id ";
        $result = db_query($sql);
        if (db_numrows($result) < 1) {
            exit_error($Language->getText('global', 'error'), $Language->getText('news_admin_index', 'not_found_err'));
        }

        $username = user_getname(db_result($result, 0, 'submitted_by'));
        $news_date = util_timestamp_to_userdateformat(db_result($result, 0, 'date'), true);

        echo '
		<H3>' . $Language->getText('news_admin_index', 'approve') . '</H3>
		<P>
		<FORM ACTION="" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="for_group" VALUE="' . db_result($result, 0, 'group_id') . '">
		<INPUT TYPE="HIDDEN" NAME="id" VALUE="' . db_result($result, 0, 'id') . '">
		<B>' . $Language->getText('news_admin_index',
                'submitted_for_group') . ':</B> <a href="/projects/' . strtolower(db_result($result, 0,
                'unix_group_name')) . '/">' . $pm->getProject(db_result($result, 0, 'group_id'))->getPublicName() . '</a><BR>
		<B>' . $Language->getText('news_admin_index',
                'submitted_by') . ':</B> <a href="/users/' . $username . '">' . $username . '</a><BR>
        <B>' . $Language->getText('news_admin_index', 'submitted_on') . ':</B> ' . $news_date . '<BR>
		<INPUT TYPE="HIDDEN" NAME="approve" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<INPUT TYPE="RADIO" NAME="status" VALUE="1"> ' . $Language->getText('news_admin_index', 'approve_for_front') . '<BR>
		<INPUT TYPE="RADIO" NAME="status" VALUE="0"> ' . $Language->getText('news_admin_index', 'do_nothing') . '<BR>
		<INPUT TYPE="RADIO" NAME="status" VALUE="2" CHECKED> ' . $Language->getText('news_admin_index', 'reject') . '<BR>
		<B>' . $Language->getText('news_admin_index', 'subject') . ':</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" VALUE="' . db_result($result, 0, 'summary') . '"><BR>
		<B>' . $Language->getText('news_admin_index', 'details') . ':</B><BR>
		<TEXTAREA NAME="details" ROWS="8" COLS="50" WRAP="SOFT">' . db_result($result, 0, 'details') . '</TEXTAREA><BR>
		<INPUT TYPE="SUBMIT" VALUE="' . $Language->getText('global', 'btn_submit') . '">
		</FORM>';

    } else {
        /*
            Show list of waiting news items
        */
        if ($request->get('approve_all')) {
            $sql = "UPDATE news_bytes SET is_approved='1' WHERE is_approved='3'";
            $res = db_query($sql);
            if (!$res) {
                $feedback .= ' ' . $Language->getText('news_admin_index', 'update_err') . ' ';
            } else {
                $feedback .= ' ' . $Language->getText('news_admin_index', 'newsbyte_updated') . ' ';
            }
        }

        $news_list = array();
        $renderer = null;
        $presenter = null;
        $title = $Language->getText('news_admin_index', 'title');
        $renderer = new AdminPageRenderer();
        $admin_news_builder = new AdminNewsBuilder(
            new AdminNewsDao()
        );

        if (!$request->get('pane') || $request->get('pane') === 'approval_queue') {
            $presenter = new AdminApprovalQueuePresenter(
                $title,
                $admin_news_builder->getApprovalQueueNews()
            );
        } else {
            if ($request->get('pane') === 'rejected_news') {
                /*
                 * Show list of deleted news items for this week
                 */
                $old_date = (time() - (86400 * 7));

                $presenter = new AdminRejectedNewsPresenter(
                    $title,
                    $admin_news_builder->getRejectedNews($old_date)
                );
            } else {

                /*
                 * Show list of approved news items for this week
                 */
                $old_date = (time() - (86400 * 7));

                $presenter = new AdminApprovedNewsPresenter(
                    $title,
                    $admin_news_builder->getApprovedNews($old_date)
                );
            }
        }

        $renderer->renderANoFramedPresenter(
            $title,
            ForgeConfig::get('codendi_dir') . '/src/templates/admin/news/',
            'admin-news',
            $presenter
        );
    }
} else {
    exit_error($Language->getText('news_admin_index', 'permission_denied'),
        $Language->getText('news_admin_index', 'need_to_be_admin'));
}
