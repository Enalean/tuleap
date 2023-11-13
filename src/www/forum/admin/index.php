<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
require_once __DIR__ . '/../forum_utils.php';

$is_admin_page = 'y';
$request       = HTTPRequest::instance();

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId) && (user_ismember($request->get('group_id'), 'F2'))) {
    $group_id        = $request->get('group_id');
    $current_project = ProjectManager::instance()->getProject($group_id);
    $vPostChanges    = new Valid_WhiteList('post_changes', ['y']);
    $vPostChanges->required();
    if ($request->isPost() && $request->valid($vPostChanges)) {
        /*
         Update the DB to reflect the changes
        */

        // Prepare validators
        // Forum Name
        $vForumName = new Valid_String('forum_name');
        $vForumName->setErrorMessage(_('Missing forum name or description, please press the "Back" button and complete this information'));
        $vForumName->required();

        // Description
        $vDescription = new Valid_String('description');
        $vDescription->setErrorMessage(_('Missing forum name or description, please press the "Back" button and complete this information'));
        $vDescription->required();

        // Is public
        $vIsPublic = new Valid_WhiteList('is_public', [0, 1, 9]);
        $vIsPublic->required();

        if ($request->existAndNonEmpty('delete')) {
            $vMsg = new Valid_UInt('msg_id');
            $vMsg->required();
            if ($request->valid($vMsg)) {
                $msg_id = $request->get('msg_id');
                    /*
                     Deleting messages or threads
                    */

                    // First, check if the message exists
                    $sql = "SELECT forum_group_list.group_id, forum.group_forum_id FROM forum,forum_group_list " .
                        "WHERE forum.group_forum_id=forum_group_list.group_forum_id AND forum.msg_id=" . db_ei($msg_id);

                    $result = db_query($sql);

                if (db_numrows($result) > 0) {
                    $message_group_id = db_result($result, 0, 'group_id');
                    $forum_id         =  db_result($result, 0, 'group_forum_id');

                    $authorized_to_delete_message = false;

                    // Then, check if the message belongs to a news or a forum
                    if ($message_group_id == ForgeConfig::get('sys_news_group')) {
                        // This message belongs to a news item.
                        // Check that the news belongs to the same project
                        $gr = db_query("SELECT group_id FROM news_bytes WHERE forum_id=" . db_ei($forum_id));
                        if (db_result($gr, 0, 'group_id') == $group_id) {
                            // authorized to delete the message
                            $authorized_to_delete_message = true;
                        }
                    } elseif ($message_group_id == $group_id) {
                        // the message belongs to this group's forums
                        $authorized_to_delete_message = true;
                    }

                    if ($authorized_to_delete_message) {
                        //delete monitor settings on the corresponding thread, before deleting the message
                        forum_thread_delete_monitor($forum_id, $msg_id);
                        $GLOBALS['Response']->addFeedback(Feedback::INFO, sprintf(_('%1$s message(s) deleted'), recursive_delete($msg_id, $forum_id)));
                    } else {
                        $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('Message is not in your group'));
                    }
                } else {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('Message not found'));
                }
            }
        } elseif ($request->existAndNonEmpty('add_forum')) {
            /*
                Adding forums to this group
            */
            $vMonitored = new Valid_WhiteList('is_monitored', [0, 1]);
            $vMonitored->required();

            if (
                $request->valid($vForumName) &&
                $request->valid($vDescription) &&
                $request->valid($vIsPublic) &&
                $request->valid($vMonitored)
            ) {
                $forum_name   = $request->get('forum_name');
                $is_public    = $request->get('is_public');
                $description  = $request->get('description');
                $is_monitored = $request->get('is_monitored');

                if (
                    (in_array($current_project->getAccess(), [Project::ACCESS_PRIVATE, Project::ACCESS_PRIVATE_WO_RESTRICTED], true) && $is_public == 0)
                    || forum_can_be_public($current_project)
                ) {
                    $fid = forum_create_forum($group_id, $forum_name, $is_public, 1, $description);

                    if ($is_monitored) {
                        forum_add_monitor($fid, UserManager::instance()->getCurrentUser()->getId());
                    }
                }
            }
        } elseif ($request->existAndNonEmpty('change_status')) {
            /*
                Change a forum to public/private
            */
            $is_public = $request->get('is_public');
            if (forum_is_public_value_allowed($current_project, $is_public)) {
                $vGrpForum = new Valid_UInt('group_forum_id');
                $vGrpForum->required();

                if (
                    $request->valid($vForumName) &&
                    $request->valid($vDescription) &&
                    $request->valid($vIsPublic) &&
                    $request->valid($vGrpForum)
                ) {
                    $forum_name     = $request->get('forum_name');
                    $description    = $request->get('description');
                    $group_forum_id = $request->get('group_forum_id');

                    $sql    = "UPDATE forum_group_list SET is_public=" . db_ei($is_public) . ",forum_name='" . db_es(htmlspecialchars($forum_name)) . "'," .
                    "description='" . db_es(htmlspecialchars($description)) . "' " .
                    "WHERE group_forum_id=" . db_ei($group_forum_id) . " AND group_id=" . db_ei($group_id);
                    $result = db_query($sql);
                    if (! $result || db_affected_rows($result) < 1) {
                        $GLOBALS['Response']->addFeedback(Feedback::INFO, _('Error Updating Forum Info'));
                    } else {
                        $GLOBALS['Response']->addFeedback(Feedback::INFO, _('Forum Info Updated Successfully'));
                    }
                }
            }
        }
    }

    $purifier = Codendi_HTMLPurifier::instance();

    if ($request->existAndNonEmpty('delete')) {
        /*
            Show page for deleting messages
        */
        forum_header(\Tuleap\Layout\HeaderConfigurationBuilder::get(_('Delete a message'))
            ->inProject($current_project, Service::FORUM)
            ->build());

        echo '
			<H2>' . _('Delete a message') . '</H2>

			<div class="alert">' . _('WARNING! You are about to permanently delete a message and all of its followups!!') . '</div>
			<FORM METHOD="POST" ACTION="?">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="delete" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $purifier->purify($group_id) . '">
			<div class="control-group">
                <label for="msg_id">' . _('Enter the Message ID') . '</label>
			    <div class="controls">
                    <INPUT TYPE="TEXT" NAME="msg_id" id="msg_id" VALUE=""><BR>
                </div>
			<INPUT CLASS="btn" TYPE="SUBMIT" NAME="SUBMIT" VALUE="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '">
			</FORM>';

        forum_footer();
    } elseif ($request->existAndNonEmpty('add_forum')) {
        /*
            Show the form for adding forums
        */
        forum_header(\Tuleap\Layout\HeaderConfigurationBuilder::get(_('Add a Forum'))
            ->inProject($current_project, Service::FORUM)
            ->build());

        $sql    = "SELECT forum_name FROM forum_group_list WHERE group_id=" . db_ei($group_id);
        $result = db_query($sql);
        ShowResultSet($result, _('Existing Forums'), false);

        echo '
			<P>
			<H2>' . _('Add a Forum') . '</H2>

			<FORM METHOD="POST" ACTION="?">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="add_forum" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $purifier->purify($group_id) . '">
			<B>' . _('Forum Name') . ':</B><BR>
			<INPUT TYPE="TEXT" NAME="forum_name" VALUE="" SIZE="30" MAXLENGTH="50"><BR>
			<B>' . _('Description') . ':</B><BR>
                        <INPUT TYPE="TEXT" NAME="description" VALUE="" SIZE="60" MAXLENGTH="255"><BR>';
        if (in_array($current_project->getAccess(), [Project::ACCESS_PRIVATE, Project::ACCESS_PRIVATE_WO_RESTRICTED], true)) {
            echo '<INPUT TYPE="HIDDEN" NAME="is_public" VALUE="0" CHECKED>';
        } else {
            echo '<P><B>' . _('Is Public?') . '</B><BR>
                        <INPUT TYPE="RADIO" NAME="is_public" VALUE="1" CHECKED> ' . $GLOBALS['Language']->getText('global', 'yes') . ' &nbsp;&nbsp;&nbsp;&nbsp;
                        <INPUT TYPE="RADIO" NAME="is_public" VALUE="0"> ' . $GLOBALS['Language']->getText('global', 'no') . '<P>';
        }
                        echo '<P><B>' . _('Want to monitor this forum?') . '</B><BR>
                                                      ' . _('As the Forum creator it is <u>strongly recommend</u> that you monitor this forum to be instantly notified via email of any new message posted to the Forum.') . ' <br>
			<INPUT TYPE="RADIO" NAME="is_monitored" VALUE="1" CHECKED> ' . $GLOBALS['Language']->getText('global', 'yes') . ' &nbsp;&nbsp;&nbsp;&nbsp;
			<INPUT TYPE="RADIO" NAME="is_monitored" VALUE="0"> ' . $GLOBALS['Language']->getText('global', 'no') . '<P>
			<P>
			<B><span class="highlight">' . _('Once you add a forum, it cannot be modified or deleted!') . '</span></B>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="' . _('Add This Forum') . '">
			</FORM>';

        forum_footer();
    } elseif ($request->existAndNonEmpty('change_status')) {
        /*
            Change a forum to public/private
        */
        forum_header(\Tuleap\Layout\HeaderConfigurationBuilder::get(_('Change Forum Status'))
            ->inProject($current_project, Service::FORUM)
            ->build());


        $sql    = "SELECT * FROM forum_group_list WHERE group_id=" . db_ei($group_id);
        $result = db_query($sql);
        $rows   = db_numrows($result);

        if (! $result || $rows < 1) {
            echo '
				<H2>' . _('No Forums Found') . '</H2>
				<P>
				' . _('No forum for this project');
        } else {
            echo '
                        <H2>' . _('Update Forum Status') . '</H2>';
            if (forum_can_be_public($current_project)) {
                echo '<P>
			         ' . _('You can make forums private from here. Please note that private forums can still be viewed by members of your project, not the general public.') . '<P>';
            }
            $title_arr   = [];
            $title_arr[] = _('Forum');
            $title_arr[] = $GLOBALS['Language']->getText('global', 'status');
            $title_arr[] = _('Update');

            echo html_build_list_table_top($title_arr);

            for ($i = 0; $i < $rows; $i++) {
                echo '
					<TR class="' . util_get_alt_row_color($i) . '"><TD>' . $purifier->purify(db_result($result, $i, 'forum_name')) . '</TD>';
                echo '
					<FORM ACTION="?" METHOD="POST">
					<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
					<INPUT TYPE="HIDDEN" NAME="change_status" VALUE="y">
					<INPUT TYPE="HIDDEN" NAME="group_forum_id" VALUE="' . $purifier->purify(db_result($result, $i, 'group_forum_id')) . '">
					<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $purifier->purify($group_id) . '">
					<TD>
						<FONT SIZE="-1">
                                                <B>' . _('Is Public?') . '</B><BR>';
                if (forum_can_be_public($current_project)) {
                    echo '<INPUT TYPE="RADIO" NAME="is_public" VALUE="1"' . ((db_result($result, $i, 'is_public') == '1') ? ' CHECKED' : '') . '> ' . $GLOBALS['Language']->getText('global', 'yes') . '<BR>';
                }
                                            echo '<INPUT TYPE="RADIO" NAME="is_public" VALUE="0"' . ((db_result($result, $i, 'is_public') == '0') ? ' CHECKED' : '') . '> ' . $GLOBALS['Language']->getText('global', 'no') . '<BR>
                                                  <INPUT TYPE="RADIO" NAME="is_public" VALUE="9"' . ((db_result($result, $i, 'is_public') == '9') ? ' CHECKED' : '') . '> ' . _('Deleted') . '<BR>
					</TD><TD>
						<FONT SIZE="-1">
						<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '">
					</TD></TR>
					<TR class="' . util_get_alt_row_color($i) . '"><TD COLSPAN="4">
						<B>' . _('Forum Name') . ':</B><BR>
						<INPUT TYPE="TEXT" NAME="forum_name" VALUE="' . $purifier->purify(html_entity_decode(db_result($result, $i, 'forum_name'))) . '" SIZE="30" MAXLENGTH="50"><BR>
						<B>' . _('Description') . ':</B><BR>
						<INPUT TYPE="TEXT" NAME="description" VALUE="' . $purifier->purify(html_entity_decode(db_result($result, $i, 'description'))) . '" SIZE="60" MAXLENGTH="255"><BR>
					</TD></TR></FORM>';
            }
            echo '</TABLE>';
        }

        forum_footer();
    } else {
     /*
      Show main page for choosing
      either moderotor or delete
     */
        forum_header(\Tuleap\Layout\HeaderConfigurationBuilder::get(_('Forum Administration'))
            ->inProject($current_project, Service::FORUM)
            ->build());

        echo '
			<H2>' . _('Forum Administration') . '</H2>
			<P>
			<A HREF="?group_id=' . $purifier->purify(urlencode($group_id)) . '&add_forum=1">' . _('Add Forum') . '</A><BR>
			<A HREF="?group_id=' . $purifier->purify(urlencode($group_id)) . '&delete=1">' . _('Delete Message') . '</A><BR>
			<A HREF="?group_id=' . $purifier->purify(urlencode($group_id)) . '&change_status=1">' . _('Update Forum Info/Status') . '</A>';

        forum_footer();
    }
} else {
    /*
        Not logged in or insufficient privileges
    */
    if (! $request->valid($vGroupId)) {
        exit_no_group();
    } else {
        exit_permission_denied();
    }
}
