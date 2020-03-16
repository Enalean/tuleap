<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

require_once 'TrackerDateReminder_ArtifactField.class.php';

class TrackerDateReminder_ArtifactFieldHtml
{
    /**
     *  Display Date Field Notification Settings form
     *
     * @return void
     */
    public function displayDateFieldNotificationSettings(ArtifactType $at, ArtifactField $field)
    {
        //get date field reminder settings from database
        $tdrArtifactField = new TrackerDateReminder_ArtifactField();
        $res = $tdrArtifactField->getDateFieldReminderSettings($field->getID(), $at->getID());
        $enabled = (db_numrows($res) == 1);
        $start = db_result($res, 0, 'notification_start');
        $frequency = db_result($res, 0, 'frequency');
        $recurse = db_result($res, 0, 'recurse');
        $notified_people = db_result($res, 0, 'notified_people');
        $notified_groups = array();
        $notified_users = array();
        if (trim($notified_people) != "") {
            $notif = explode(",", $notified_people);
            foreach ($notif as $value) {
                if (preg_match("/^g/", $value)) {
                    array_push($notified_groups, $value);
                } else {
                    array_push($notified_users, $value);
                }
            }
        }
        if (count($notified_groups) == 0) {
            $notified_groups[] = '100';
        }
        if (count($notified_users) == 0) {
            $notified_users[] = '100';
        }
        $notif_type = db_result($res, 0, 'notification_type');
        if ($notif_type == 1) {
            $after = "selected";
            $before = "";
        } else {
            $after = "";
            $before = "selected";
        }

        $out = '';

        $baseActionUrl = '/tracker/admin/index.php?func=date_field_notification&group_id=' . $at->Group->getID() . '&atid=' . $at->getID() . '&field_id=' . $field->getID();

        if ($enabled) {
            $out .= '<H3>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'notif_settings_del_title') . '</H3>';
            $out .= '<FORM ACTION="' . $baseActionUrl . '&delete_reminder=true" METHOD="POST">';
            $out .= '<P>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'notif_settings_del_desc') . '</P>';
            $out .= '<INPUT TYPE="SUBMIT" NAME="reminder" VALUE="' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'notif_settings_del_button') . '" />';
            $out .= '</FORM>';
        }

        $out .= '<FORM ACTION="' . $baseActionUrl . '" METHOD="POST" name="date_field_notification_settings_form">
            <INPUT TYPE="HIDDEN" NAME="field_id" VALUE="' . $field->getID() . '">
            <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $at->Group->getID() . '">
            <INPUT TYPE="HIDDEN" NAME="atid" VALUE="' . $at->getID() . '">';

        $out .= '<h3>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'notif_settings_field', array($field->getLabel())) . '</h3>';

        $out .= '<div class="row-fluid" id="tv3-mail-reminders">
            <div class="span12 tv3-mail-reminder">'
                . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_form_part1', array($field->getLabel())) .
                '<INPUT TYPE="TEXT" NAME="start" SIZE="5" VALUE="' . $start . '"> ' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'days') . '
                <SELECT NAME="notif_type">
                    <OPTION VALUE="0" ' . $before . '>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'notify_before') . '
                    <OPTION VALUE="1" ' . $after . '>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'notify_after') . '
                </SELECT> ' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_form_part2') .
            '</div>
            <div class="span12 tv3-mail-reminder">'
                . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_form_part3') .
                '<INPUT TYPE="TEXT" NAME="recurse" SIZE="5" VALUE="' . $recurse . '"> ' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_form_part4');

        $artRoleNames = array(array('value' => '1', 'text' => $GLOBALS['Language']->getText('tracker_common_types', 'role_SUBMITTER_short_desc')),
                              array('value' => '2', 'text' => $GLOBALS['Language']->getText('tracker_common_types', 'role_ASSIGNEE_short_desc')),
                              array('value' => '3', 'text' => $GLOBALS['Language']->getText('tracker_common_types', 'role_CC_short_desc')),
                              array('value' => '4', 'text' => $GLOBALS['Language']->getText('tracker_common_types', 'role_COMMENTER_short_desc')));
        $out .= html_build_multiple_select_box_from_array($artRoleNames, 'notified_users[]', $notified_users, 4, true, '', false, '', false, '', false);

        $GLOBALS['Language']->getText('global', 'and');

        $qry = sprintf(
            'SELECT ugroup_id, name FROM ugroup' .
                        ' WHERE (group_id = %d || group_id = 100)' .
                        ' AND ugroup_id <> 1' .
                        ' AND ugroup_id <> 2' .
                        ' AND ugroup_id <> 100',
            db_ei($at->Group->getID())
        );
        $res = db_query($qry);
        while ($rows = db_fetch_array($res)) {
            $groupNames[] = array('value' => 'g' . $rows['ugroup_id'], 'text' => util_translate_name_ugroup($rows['name']));
        }
        $out .= html_build_multiple_select_box_from_array($groupNames, 'notified_groups[]', $notified_groups, 8, true, '', false, '', false, '', false);

        $out .= '</SELECT>' .
        $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_form_part5') .
            ' <INPUT TYPE="TEXT" NAME="frequency" SIZE="5" VALUE="' . $frequency . '"> ' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'days') . '</div>' .
            '<INPUT TYPE="SUBMIT" NAME="submit_notif_settings" value="' . $GLOBALS['Language']->getText('global', 'btn_update') . '"></P></FORM><P>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_form_part6', array($field->getLabel())) .
            '<P>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_form_part7', array($field->getLabel())) . '</P>' .
            '<P>';

        echo $out;
    }
}
