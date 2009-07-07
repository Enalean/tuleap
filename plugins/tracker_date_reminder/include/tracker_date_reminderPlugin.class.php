<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once 'common/plugin/Plugin.class.php';

require_once 'TrackerDateReminder_ArtifactFieldFactory.class.php';
require_once 'TrackerDateReminder_ArtifactFieldHtml.class.php';
require_once 'TrackerDateReminder_ArtifactType.class.php';

class tracker_date_reminderPlugin extends Plugin {

    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        
        $this->_addHook('artifact_type_html_display_notification_form', 'artifact_type_html_display_notification_form', false);
        $this->_addHook('tracker_graphic_report_admin', 'tracker_graphic_report_admin', false);
        
        $this->_addHook('artifact_type_factory_delete_artifact_type', 'artifact_type_factory_delete_artifact_type', false);
        $this->_addHook('artifact_import_insert_artifact', 'artifact_import_insert_artifact', false);
    }

    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'TrackerDateReminderPluginInfo')) {
            include_once('TrackerDateReminderPluginInfo.class.php');
            $this->pluginInfo = new TrackerDateReminderPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function artifact_type_factory_delete_artifact_type($params) {
        // Delete artifact_date_reminder_settings
        $sql = sprintf('DELETE FROM artifact_date_reminder_settings'.
                       ' WHERE group_artifact_id=%d',
                       $params['tracker_id']);
        db_query ($sql);

        // Delete artifact_date_reminder_processing
        $sql = sprintf('DELETE FROM artifact_date_reminder_processing'.
                       ' WHERE group_artifact_id=%d',
                       $params['tracker_id']);
        db_query ($sql);
    }
    
    function artifact_import_insert_artifact($params) {
        //add the artifact to date reminder processing table, if relevant
        $at = new TrackerDateReminder_ArtifactType($params['artifact_type']);
        $at->addArtifactToDateReminderProcessing(0, $params['artifact_id'], $params['artifact_type']->getID());
    }
    
    function artifact_type_html_display_notification_form($params) {
        if ($params['at']->userIsAdmin()) {
            echo '<br><h3>'.$GLOBALS['Language']->getText('tracker_include_type','date_fields_mail_notif').' </h3>';

            $title_arr=array();
            $title_arr[]=$GLOBALS['Language']->getText('tracker_include_type','df');
            $title_arr[]=$GLOBALS['Language']->getText('tracker_include_type','notification_status');
            $title_arr[]=$GLOBALS['Language']->getText('tracker_include_type','notification_settings');

            $out = html_build_list_table_top ($title_arr);
            $fmt = "\n".'<TR class=%s><td>%s</td><td align="center">%s</td><td align="center">%s</td></tr>';
            $row_color = 0;

            $tdrArtifactFieldFactory = new TrackerDateReminder_ArtifactFieldFactory();
            $tdrArtifactFieldFactory->cacheFieldsWithNotification($params['at']->getID());
            $fields = $tdrArtifactFieldFactory->getUsedDateFields($params['art_field_fact']);
            while (list($field_name,$field) = each($fields)) {

                // no notification status/settings for special Date field (Submitted on)
                if (!$field->isSpecial()) {
                    $notif_settings = '<A href="/tracker/admin/index.php?func=date_field_notification&group_id='.$params['group_id'].'&atid='.$params['at']->getID().'&field_id='.$field->getID().'">'.$GLOBALS['Language']->getText('tracker_include_type','edit_notif_settings').'</A>';
                    if ($tdrArtifactFieldFactory->notificationEnabled($field->getID())) {
                        $notif_status = $GLOBALS['Language']->getText('tracker_include_type','active');
                    } else {
                        $notif_status = $GLOBALS['Language']->getText('tracker_include_type','disabled');
                    }

                    $out .= sprintf($fmt,
                                    util_get_alt_row_color($row_color),
                                    $field->getLabel(),
                                    $notif_status,
                                    $notif_settings);
                    $row_color++;
                }
            }

            $out .= "</table>";
            echo $out;
        }

        echo "<HR>\n";
    }
    
    function tracker_graphic_report_admin($params) {
        if ( !user_isloggedin() ) {
            exit_not_logged_in();
            return;
        }

        if ( !$params['ath']->userIsAdmin() ) {
            exit_permission_denied();
            return;
        }

        $request = HTTPRequest::instance();
        
        $field_id = $request->getValidated('field_id', 'uint');
        $field    = $params['art_field_fact']->getFieldFromId($field_id);
        //@todo: change check

/*        //check if  field_id exist
        $sql = sprintf('SELECT field_id FROM artifact_field'
        .' WHERE group_artifact_id=%d'
        .' AND field_id=%d',
        $params['ath']->getID(),$field_id);
        $result = db_query($sql);
        if (db_numrows($result) < 1) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('tracker_admin_index','wrong_field',array($field_id)));
        } else {
            $field = $art_field_fact->getFieldFromId($field_id);
            if (! $field->getNotificationStatus()) {
                exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('tracker_admin_index','wrong_field',array($field_id)));
            }
        }
*/
        if (array_key_exists('submit_notif_settings', $_REQUEST) && $_REQUEST['submit_notif_settings']) {
            if ((!isset($_REQUEST['notified_users']) || (isset($_REQUEST['notified_users']) && $_REQUEST['notified_users'] == NULL)) && _
            (!isset($_REQUEST['notified_groups']) || (isset($_REQUEST['notified_groups']) && $_REQUEST['notified_groups'] == NULL))) {
                $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('tracker_admin_index','specify_notified_users'));
            } else if (!isset($_REQUEST['start']) || (isset($_REQUEST['start']) && $_REQUEST['start'] == NULL)) {
                $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('tracker_admin_index','specify_notification_start'));
            } else if (!ereg("^[0-9]+$",$_REQUEST['start']) || $_REQUEST['start'] < 0) {
                $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('tracker_admin_index','positive_value'));
            } else if (!isset($_REQUEST['frequency']) || (isset($_REQUEST['frequency']) && ($_REQUEST['frequency'] == NULL || $_REQUEST['frequency'] == 0))) {
                $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('tracker_admin_index','specify_notification_frequency'));
            } else if (!ereg("^[0-9]+$",$_REQUEST['frequency']) || $_REQUEST['frequency'] < 0) {
                $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('tracker_admin_index','positive_value'));
            } else if (!isset($_REQUEST['recurse']) || (isset($_REQUEST['recurse']) && ($_REQUEST['recurse'] == NULL || $_REQUEST['recurse'] == 0))) {
                $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('tracker_admin_index','specify_notification_recurse'));
            } else if (!ereg("^[0-9]+$",$_REQUEST['recurse']) || $_REQUEST['recurse'] < 0) {
                $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('tracker_admin_index','positive_value'));
            } else {
                
                $enabled = $request->getValidated('enabled', 'uint', 0);
                
                //merge notified_users and notified_groups into one array
                if (isset($_REQUEST['notified_users'])) {
                    $notified = $_REQUEST['notified_users'];
                } else {
                    $notified = array();
                }
                if (isset($_REQUEST['notified_groups'])) {
                    foreach ($_REQUEST['notified_groups'] as $gr) {
                        array_push($notified,$gr);
                    }
                }
                // now update the reminder settings
                $tdrArtifactField = new TrackerDateReminder_ArtifactField();
                $res = $tdrArtifactField->updateDateFieldReminderSettings($params['ath'], $field, $params['ath']->getID(),$enabled,$_REQUEST['start'],$_REQUEST['notif_type'],$_REQUEST['frequency'],$_REQUEST['recurse'],$notified);
                if ($res) {
                    $GLOBALS['Response']->addFeedback('info',$GLOBALS['Language']->getText('tracker_admin_index','notif_update_success',array($field->getLabel())));
                } else {
                    $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('tracker_admin_index','notif_update_fail',array($field->getLabel())));
                }
            }
        }

        $params['ath']->adminHeader(array ('title'=>$GLOBALS['Language']->getText('tracker_admin_index','admin_date_field_notif'),
           'help' => 'TrackerAdministration.html#TrackerEmailNotificationSettings'));
         
        echo '<H2>'.$GLOBALS['Language']->getText('tracker_import_admin','tracker').' \'<a href="/tracker/admin/?group_id='.$params['ath']->Group->getID().'&atid='.$params['ath']->getID().'">'.$params['ath']->getName().'</a>\' - '.$GLOBALS['Language']->getText('tracker_include_type','mail_notif').'</h2>';

        $tdrArtifactFieldHtml = new TrackerDateReminder_ArtifactFieldHtml();
        $tdrArtifactFieldHtml->displayDateFieldNotificationSettings($params['ath'], $field);
        $params['ath']->footer(array());
        exit;
    }
}

?>
