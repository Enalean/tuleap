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
        $notified_groups = [];
        $notified_users = [];
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
            $out .= '<H3>' . dgettext('tuleap-tracker_date_reminder', 'Date based email reminder activated for this field') . '</H3>';
            $out .= '<FORM ACTION="' . $baseActionUrl . '&delete_reminder=true" METHOD="POST">';
            $out .= '<P>' . dgettext('tuleap-tracker_date_reminder', 'You can disable reminder with following button') . '</P>';
            $out .= '<INPUT TYPE="SUBMIT" NAME="reminder" VALUE="' . dgettext('tuleap-tracker_date_reminder', 'Delete reminder on this field') . '" />';
            $out .= '</FORM>';
        }

        $out .= '<FORM ACTION="' . $baseActionUrl . '" METHOD="POST" name="date_field_notification_settings_form">
            <INPUT TYPE="HIDDEN" NAME="field_id" VALUE="' . $field->getID() . '">
            <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $at->Group->getID() . '">
            <INPUT TYPE="HIDDEN" NAME="atid" VALUE="' . $at->getID() . '">';

        $out .= '<h3>' . sprintf(dgettext('tuleap-tracker_date_reminder', 'Reminder Settings For \'%1$s\' Field'), $field->getLabel()) . '</h3>';

        $out .= '<div class="row-fluid" id="tv3-mail-reminders">
            <div class="span12 tv3-mail-reminder">'
                . sprintf(dgettext('tuleap-tracker_date_reminder', 'The notification on <b>\'%1$s\'</b> will start'), $field->getLabel()) .
                '<INPUT TYPE="TEXT" NAME="start" SIZE="5" VALUE="' . $start . '"> ' . dgettext('tuleap-tracker_date_reminder', 'day(s)') . '
                <SELECT NAME="notif_type">
                    <OPTION VALUE="0" ' . $before . '>' . dgettext('tuleap-tracker_date_reminder', 'before') . '
                    <OPTION VALUE="1" ' . $after . '>' . dgettext('tuleap-tracker_date_reminder', 'after') . '
                </SELECT> ' . dgettext('tuleap-tracker_date_reminder', 'the date set in this field.') .
            '</div>
            <div class="span12 tv3-mail-reminder">'
                . dgettext('tuleap-tracker_date_reminder', 'Codex will send') .
                '<INPUT TYPE="TEXT" NAME="recurse" SIZE="5" VALUE="' . $recurse . '"> ' . dgettext('tuleap-tracker_date_reminder', 'e-mail(s) to');

        $artRoleNames = [['value' => '1', 'text' => $GLOBALS['Language']->getText('tracker_common_types', 'role_SUBMITTER_short_desc')],
                              ['value' => '2', 'text' => $GLOBALS['Language']->getText('tracker_common_types', 'role_ASSIGNEE_short_desc')],
                              ['value' => '3', 'text' => $GLOBALS['Language']->getText('tracker_common_types', 'role_CC_short_desc')],
                              ['value' => '4', 'text' => $GLOBALS['Language']->getText('tracker_common_types', 'role_COMMENTER_short_desc')]];
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
            $groupNames[] = ['value' => 'g' . $rows['ugroup_id'], 'text' => \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) $rows['name'])];
        }
        $out .= html_build_multiple_select_box_from_array($groupNames, 'notified_groups[]', $notified_groups, 8, true, '', false, '', false, '', false);

        $out .= '</SELECT>' .
        dgettext('tuleap-tracker_date_reminder', 'with a frequency of one e-mail every') .
            ' <INPUT TYPE="TEXT" NAME="frequency" SIZE="5" VALUE="' . $frequency . '"> ' . dgettext('tuleap-tracker_date_reminder', 'day(s)') . '</div>' .
            '<INPUT TYPE="SUBMIT" NAME="submit_notif_settings" value="' . $GLOBALS['Language']->getText('global', 'btn_update') . '"></P></FORM><P>' . sprintf(dgettext('tuleap-tracker_date_reminder', '<b>Example</b>:<br><br>The notification on \'%1$s\' date will start <b>2</b> days <b>before</b> the date set in this field.<br>Codex will send <b>3</b> e-mails to <b>Submitter</b> and <b>Assignee</b>, with a frequency of one e-mail every <b>2</b> days.'), $field->getLabel()) .
            '<P>' . sprintf(dgettext('tuleap-tracker_date_reminder', 'It means: Codex will send to artifact submitter and assignee(s): <br> * 1 e-mail 2 days before <i>\'%1$s\'</i> date<br> * 1 e-mail on <i>\'%1$s\'</i> date <br> * 1 e-mail 2 days after <i>\'%1$s\'</i> date'), $field->getLabel()) . '</P>' .
            '<P>';

        echo $out;
    }
}
