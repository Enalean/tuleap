<?php

require_once 'TrackerDateReminder_ArtifactField.class.php';

class TrackerDateReminder_ArtifactFieldHtml {
    /**
     *  Display Date Field Notification Settings form
     *
     * @param
     * @return void
     */
    function displayDateFieldNotificationSettings(ArtifactType $at, ArtifactField $field) {
        //get date field reminder settings from database
        $tdrArtifactField = new TrackerDateReminder_ArtifactField();
        $res = $tdrArtifactField->getDateFieldReminderSettings($field->getID(), $at->getID());
        $enabled = (db_numrows($res) == 1);
        $start = db_result($res,0,'notification_start');
        $frequency = db_result($res,0,'frequency');
        $recurse = db_result($res,0,'recurse');
        $notif = explode(",",db_result($res,0,'notified_people'));
        $notified_groups = array();
        $notified_users = array();
        foreach ($notif as $value) {
            if (preg_match("/^g/",$value)) {
                array_push($notified_groups,$value);
            } else {
                array_push($notified_users,$value);
            }
        }
        $notif_type = db_result($res,0,'notification_type');
        if ($notif_type == 1) {
            $after = "selected";
            $before = "";
        } else {
            $after = "";
            $before = "selected";
        }

        $out = '';
        $out = '<FORM ACTION="/tracker/admin/index.php?func=date_field_notification&group_id='.$at->Group->getID().'&atid='.$at->getID().'&field_id='.$field->getID().'" METHOD="POST" name="date_field_notification_settings_form">
            <INPUT TYPE="HIDDEN" NAME="field_id" VALUE="'.$field->getID().'">
            <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$at->Group->getID().'">
            <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.$at->getID().'">';
        
        $out .= '<h3>Activate reminder on this field</h3>';
        $checked = '';
        if ($enabled) {
            $checked = 'CHECKED="CHECKED"';
        }
        $out .= '<INPUT TYPE="CHECKBOX" NAME="enabled" VALUE="1" '.$checked.'/>';

        
        $out .= '<h3>'.$GLOBALS['Language']->getText('tracker_include_type','notif_settings_field',array($field->getLabel())).'</h3>';
         
        $out .= '<fieldset>
            <TABLE BORDER="0" WIDTH="930px"><TR height="30"><TD>'.$GLOBALS['Language']->getText('tracker_include_type','reminder_form_part1',array($field->getLabel())).
            '</TD><TD> <INPUT TYPE="TEXT" NAME="start" SIZE="5" VALUE="'.$start.'"> '.$GLOBALS['Language']->getText('tracker_include_type','days').'</TD><TD colspan=3">
            <SELECT NAME="notif_type">
                <OPTION VALUE="0" '.$before.'>'.$GLOBALS['Language']->getText('tracker_include_type','notify_before').'
                <OPTION VALUE="1" '.$after.'>'.$GLOBALS['Language']->getText('tracker_include_type','notify_after').'
            </SELECT> '.$GLOBALS['Language']->getText('tracker_include_type','reminder_form_part2').
            '</TD></TR><TR><TD valign="top">'.$GLOBALS['Language']->getText('tracker_include_type','reminder_form_part3').' <INPUT TYPE="TEXT" NAME="recurse" SIZE="5" VALUE="'.$recurse.'"> '.$GLOBALS['Language']->getText('tracker_include_type','reminder_form_part4').
            '</TD><TD valign="top"> <SELECT MULTIPLE NAME="notified_users[]">';

        if (isset($notified_users) && in_array("1",$notified_users)) {
            $selected = "selected";
        } else {
            $selected = "";
        }
        $out .= '<OPTION VALUE="1" '.$selected.'>'.$GLOBALS['Language']->getText('tracker_common_types','role_SUBMITTER_short_desc');

        if (isset($notified_users) && in_array("2",$notified_users)) {
            $selected = "selected";
        } else {
            $selected = "";
        }
        $out .= '<OPTION VALUE="2" '.$selected.'>'.$GLOBALS['Language']->getText('tracker_common_types','role_ASSIGNEE_short_desc');

        if (isset($notified_users) && in_array("3",$notified_users)) {
            $selected = "selected";
        } else {
            $selected = "";
        }
        $out .= '<OPTION VALUE="3" '.$selected.'>'.$GLOBALS['Language']->getText('tracker_common_types','role_CC_short_desc');

        if (isset($notified_users) && in_array("4",$notified_users)) {
            $selected = "selected";
        } else {
            $selected = "";
        }

        $out .= '<OPTION VALUE="4" '.$selected.'>'.$GLOBALS['Language']->getText('tracker_common_types','role_COMMENTER_short_desc').'</SELECT></TD><TD valign="top">'.
        $GLOBALS['Language']->getText('global','and').' </TD>
            <TD valign="top"><SELECT MULTIPLE NAME="notified_groups[]">';           

        $qry = sprintf('SELECT ugroup_id, name FROM ugroup'.
                        ' WHERE (group_id = %d || group_id = 100)'.
                        ' AND ugroup_id <> 1'.
                        ' AND ugroup_id <> 2'.
                        ' AND ugroup_id <> 100',
        db_ei($at->Group->getID()));
        $res = db_query($qry);
        while ($rows = db_fetch_array($res)) {
            $val = "g".$rows['ugroup_id'];
            if (isset($notified_groups) && in_array($val,$notified_groups)) {
                $sel = "selected";
            } else {
                $sel = "";
            }
            $out .= '<OPTION VALUE="'.$val.'" '.$sel.'>'.util_translate_name_ugroup($rows['name']).'</OPTION>';
        }

        $out .= '</SELECT></TD><TD valign="top">'.
        $GLOBALS['Language']->getText('tracker_include_type','reminder_form_part5').
            ' <INPUT TYPE="TEXT" NAME="frequency" SIZE="5" VALUE="'.$frequency.'"> '.$GLOBALS['Language']->getText('tracker_include_type','days').
            '.</TD></TR></TABLE></fieldset><P>'.$GLOBALS['Language']->getText('tracker_include_type','reminder_form_part6',array($field->getLabel())).
            '<P>'.$GLOBALS['Language']->getText('tracker_include_type','reminder_form_part7',array($field->getLabel())).'</P>'.
            '<P><INPUT TYPE="SUBMIT" NAME="submit_notif_settings"></P></FORM>';
        echo $out;

    }
}

?>