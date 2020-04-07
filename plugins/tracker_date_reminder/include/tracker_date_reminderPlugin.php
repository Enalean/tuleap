<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All rights reserved
 * Copyright Enalean (c) 2015-2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once __DIR__ . '/../vendor/autoload.php';

class tracker_date_reminderPlugin extends Plugin
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        $this->addHook('artifact_type_html_display_notification_form', 'artifact_type_html_display_notification_form', false);
        // Tracker admin "controller"
        $this->addHook('tracker_graphic_report_admin', 'tracker_graphic_report_admin', false);
        // Field deletion
        $this->addHook('tracker_admin_field_delete', 'tracker_admin_field_delete', false);
        // Codendi daily actions
        $this->addHook('codendi_daily_start', 'codendi_daily_start', false);
        // Tracker deletion
        $this->addHook('artifact_type_factory_delete_artifact_type', 'artifact_type_factory_delete_artifact_type', false);

        // CSV artifact import
        $this->addHook('artifact_import_insert_artifact', 'tracker_create_artifact', false);
        // Create new artifact
        $this->addHook('tracker_postadd', 'tracker_create_artifact', false);
        // Copy an artifact
        $this->addHook('tracker_postcopy', 'tracker_create_artifact', false);
        // Modification of an artifact
        $this->addHook('tracker_postmod', 'tracker_update_artifact', false);
    }

    public function getPluginInfo()
    {
        if (!is_a($this->pluginInfo, 'TrackerDateReminderPluginInfo')) {
            include_once('TrackerDateReminderPluginInfo.class.php');
            $this->pluginInfo = new TrackerDateReminderPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    private function isLoggingEnabled()
    {
        return $this->getPluginInfo()->getPropertyValueForName('enable_log');
    }

    public function codendi_daily_start($params)
    {
        include_once 'ArtifactDateReminder.class.php';
        include_once 'TrackerDateReminder_Logger_Prefix.class.php';

        if ($this->isLoggingEnabled()) {
            $logfile = $GLOBALS['codendi_log'] . "/tracker_date_reminder.log";
        } else {
            $logfile = false;
        }

        $logger = new TrackerDateReminder_Logger($logfile);

        $artifactDateReminder = new ArtifactDateReminder($logger);
        $artifactDateReminder->codexDaily();
    }

    public function artifact_type_factory_delete_artifact_type($params)
    {
        // Delete artifact_date_reminder_settings
        $sql = sprintf(
            'DELETE FROM artifact_date_reminder_settings' .
                       ' WHERE group_artifact_id=%d',
            $params['tracker_id']
        );
        db_query($sql);

        // Delete artifact_date_reminder_processing
        $sql = sprintf(
            'DELETE FROM artifact_date_reminder_processing' .
                       ' WHERE group_artifact_id=%d',
            $params['tracker_id']
        );
        db_query($sql);
    }

    public function artifact_type_html_display_notification_form($params)
    {
        if ($params['at']->userIsAdmin()) {
            echo '<br><h3>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'date_fields_mail_notif') . ' </h3>';

            $title_arr = array();
            $title_arr[] = $GLOBALS['Language']->getText('tracker_include_type', 'df');
            $title_arr[] = $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'notification_status');
            $title_arr[] = $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'notification_settings');

            $out = html_build_list_table_top($title_arr);
            $fmt = "\n" . '<TR class=%s><td>%s</td><td align="center">%s</td><td align="center">%s</td></tr>';
            $row_color = 0;

            $tdrArtifactFieldFactory = new TrackerDateReminder_ArtifactFieldFactory();
            $tdrArtifactFieldFactory->cacheFieldsWithNotification($params['at']->getID());
            $fields = $tdrArtifactFieldFactory->getUsedDateFields($params['art_field_fact']);
            foreach ($fields as $field) {
                // no notification status/settings for special Date field (Submitted on)
                if (!$field->isSpecial()) {
                    $notif_settings = '<A href="/tracker/admin/index.php?func=date_field_notification&group_id=' . $params['group_id'] . '&atid=' . $params['at']->getID() . '&field_id=' . $field->getID() . '">' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'edit_notif_settings') . '</A>';
                    if ($tdrArtifactFieldFactory->notificationEnabled($field->getID())) {
                        $notif_status = $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'active');
                    } else {
                        $notif_status = $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'disabled');
                    }

                    $out .= sprintf(
                        $fmt,
                        util_get_alt_row_color($row_color),
                        $field->getLabel(),
                        $notif_status,
                        $notif_settings
                    );
                    $row_color++;
                }
            }

            $out .= "</table>";
            echo $out;
        }

        echo "<HR>\n";
    }

    /**
     * Hook: Tracker admin "controller"
     *
     * @param $params
     *
     * @return void
     */
    public function tracker_graphic_report_admin($params)
    {
        $request = HTTPRequest::instance();
        if ($request->getValidated('func', 'string') != 'date_field_notification') {
            return;
        }

        if (!user_isloggedin()) {
            exit_not_logged_in();
            return;
        }

        if (!$params['ath']->userIsAdmin()) {
            exit_permission_denied();
            return;
        }

        $field_id = $request->getValidated('field_id', 'uint');
        $field    = $params['art_field_fact']->getFieldFromId($field_id);
        if ($field && $field->isDateField() && !$field->isSpecial()) {
            if ($request->isPost()) {
                if ($request->existAndNonEmpty('delete_reminder')) {
                    $tdrArtifactField = new TrackerDateReminder_ArtifactField();
                    $tdrArtifactField->deleteFieldReminderSettings($field->getID(), $params['ath']->getID());
                } elseif (array_key_exists('submit_notif_settings', $_REQUEST) && $_REQUEST['submit_notif_settings']) {
                    if ((!isset($_REQUEST['notified_users']) || (isset($_REQUEST['notified_users']) && $_REQUEST['notified_users'] == null)) && _(!isset($_REQUEST['notified_groups']) || (isset($_REQUEST['notified_groups']) && $_REQUEST['notified_groups'] == null))) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'specify_notified_users'));
                    } elseif (
                        count($_REQUEST['notified_users']) == 1 && $_REQUEST['notified_users'][0] == 100 &&
                        count($_REQUEST['notified_groups']) == 1 && $_REQUEST['notified_groups'][0] == 100
                    ) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'specify_notified_users'));
                    } elseif (!isset($_REQUEST['start']) || (isset($_REQUEST['start']) && $_REQUEST['start'] == null)) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'specify_notification_start'));
                    } elseif (!preg_match("/^[0-9]+$/", $_REQUEST['start']) || $_REQUEST['start'] < 0) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'positive_value'));
                    } elseif (!isset($_REQUEST['frequency']) || (isset($_REQUEST['frequency']) && ($_REQUEST['frequency'] == null || $_REQUEST['frequency'] == 0))) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'specify_notification_frequency'));
                    } elseif (!preg_match("/^[0-9]+$/", $_REQUEST['frequency']) || $_REQUEST['frequency'] < 0) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'positive_value'));
                    } elseif (!isset($_REQUEST['recurse']) || (isset($_REQUEST['recurse']) && ($_REQUEST['recurse'] == null || $_REQUEST['recurse'] == 0))) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'specify_notification_recurse'));
                    } elseif (!preg_match("/^[0-9]+$/", $_REQUEST['recurse']) || $_REQUEST['recurse'] < 0) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'positive_value'));
                    } else {
                        //merge notified_users and notified_groups into one array
                        $notified = array();
                        if (isset($_REQUEST['notified_users'])) {
                            foreach ($_REQUEST['notified_users'] as $u) {
                                if ($u != 100) {
                                    $notified[] = $u;
                                }
                            }
                        }
                        if (isset($_REQUEST['notified_groups'])) {
                            foreach ($_REQUEST['notified_groups'] as $gr) {
                                if ($gr != 100) {
                                    $notified[] = $gr;
                                }
                            }
                        }
                        // now update the reminder settings
                        $tdrArtifactField = new TrackerDateReminder_ArtifactField();
                        $res = $tdrArtifactField->updateDateFieldReminderSettings($params['ath'], $field, $params['ath']->getID(), $_REQUEST['start'], $_REQUEST['notif_type'], $_REQUEST['frequency'], $_REQUEST['recurse'], $notified);
                        if ($res) {
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'notif_update_success', array($field->getLabel())));
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'notif_update_fail', array($field->getLabel())));
                        }
                    }
                }
            }
            $params['ath']->adminHeader(array ('title' => $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'admin_date_field_notif'),
            'help' => 'tracker.html#email-notification-settings'));

            echo '<H2>' . $GLOBALS['Language']->getText('tracker_import_admin', 'tracker') . ' \'<a href="/tracker/admin/?group_id=' . $params['ath']->Group->getID() . '&atid=' . $params['ath']->getID() . '">' . $params['ath']->getName() . '</a>\' - ' . $GLOBALS['Language']->getText('tracker_include_type', 'mail_notif') . '</h2>';

            $tdrArtifactFieldHtml = new TrackerDateReminder_ArtifactFieldHtml();
            $tdrArtifactFieldHtml->displayDateFieldNotificationSettings($params['ath'], $field);
            $params['ath']->footer(array());
            exit;
        }
    }

    /**
     * Hook: Tracker field deletion
     *
     * @param $params
     *
     * @return void
     */
    public function tracker_admin_field_delete($params)
    {
        $tdrArtifactField = new TrackerDateReminder_ArtifactField();
        $tdrArtifactField->deleteFieldReminderSettings($params['field']->getID(), $params['ath']->getID());
    }

    /**
     * Hook: Artifact creation, copy & csv import
     *
     * Add the artifact to date reminder processing table, if relevant
     *
     * @param $params
     *
     * @return void
     */
    public function tracker_create_artifact($params)
    {
        if ($params['ah']->getStatusID() == 1) {
            $tdrArtifactType = new TrackerDateReminder_ArtifactType($params['ath']);
            $tdrArtifactType->addArtifactToDateReminderProcessing(0, $params['ah']->getID(), $params['ath']->getID());
        }
    }


    /**
     * Hook: Artifact update in web interface
     *
     * @param $params
     *
     * @return void
     */
    public function tracker_update_artifact($params)
    {
        if ($params['ah']->getStatusID() == 1) {
            $tdrArtifactType = new TrackerDateReminder_ArtifactType($params['ath']);
            $tdrArtifactType->deleteArtifactFromDateReminderProcessing(0, $params['ah']->getID(), $params['ath']->getID());
            $tdrArtifactType->addArtifactToDateReminderProcessing(0, $params['ah']->getID(), $params['ath']->getID());
        }
    }
}
