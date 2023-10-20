<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All rights reserved
 * Copyright Enalean (c) 2015 - Present. All rights reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class tracker_date_reminderPlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-tracker_date_reminder', __DIR__ . '/../site-content');

        $this->addHook('artifact_type_html_display_notification_form', 'artifact_type_html_display_notification_form');
        // Tracker admin "controller"
        $this->addHook('tracker_graphic_report_admin', 'tracker_graphic_report_admin');
        // Field deletion
        $this->addHook('tracker_admin_field_delete', 'tracker_admin_field_delete');
        // Codendi daily actions
        $this->addHook('codendi_daily_start', 'codendi_daily_start');
        // Tracker deletion
        $this->addHook('artifact_type_factory_delete_artifact_type', 'artifact_type_factory_delete_artifact_type');

        // CSV artifact import
        $this->addHook('artifact_import_insert_artifact', 'tracker_create_artifact');
        // Create new artifact
        $this->addHook('tracker_postadd', 'tracker_create_artifact');
        // Copy an artifact
        $this->addHook('tracker_postcopy', 'tracker_create_artifact');
        // Modification of an artifact
        $this->addHook('tracker_postmod', 'tracker_update_artifact');
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo instanceof \TrackerDateReminderPluginInfo) {
            include_once('TrackerDateReminderPluginInfo.class.php');
            $this->pluginInfo = new TrackerDateReminderPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    private function isLoggingEnabled()
    {
        return $this->getPluginInfo()->getPropertyValueForName('enable_log');
    }

    public function codendi_daily_start($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        include_once 'ArtifactDateReminder.class.php';
        include_once 'TrackerDateReminder_Logger_Prefix.class.php';

        if ($this->isLoggingEnabled()) {
            $logfile = ForgeConfig::get('codendi_log') . "/tracker_date_reminder.log";
        } else {
            $logfile = false;
        }

        $logger = new TrackerDateReminder_Logger($logfile);

        $artifactDateReminder = new ArtifactDateReminder($logger);
        $artifactDateReminder->codexDaily();
    }

    public function artifact_type_factory_delete_artifact_type($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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

    public function artifact_type_html_display_notification_form($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['at']->userIsAdmin()) {
            echo '<br><h3>' . dgettext('tuleap-tracker_date_reminder', 'Date Fields Email Notification') . ' </h3>';

            $title_arr   = [];
            $title_arr[] = $GLOBALS['Language']->getText('tracker_include_type', 'df');
            $title_arr[] = dgettext('tuleap-tracker_date_reminder', 'Notification Status');
            $title_arr[] = dgettext('tuleap-tracker_date_reminder', 'Notification Settings');

            $out       = html_build_list_table_top($title_arr);
            $fmt       = "\n" . '<TR class=%s><td>%s</td><td align="center">%s</td><td align="center">%s</td></tr>';
            $row_color = 0;

            $tdrArtifactFieldFactory = new TrackerDateReminder_ArtifactFieldFactory();
            $tdrArtifactFieldFactory->cacheFieldsWithNotification($params['at']->getID());
            $fields = $tdrArtifactFieldFactory->getUsedDateFields($params['art_field_fact']);
            foreach ($fields as $field) {
                // no notification status/settings for special Date field (Submitted on)
                if (! $field->isSpecial()) {
                    $notif_settings = '<A href="/tracker/admin/index.php?func=date_field_notification&group_id=' . $params['group_id'] . '&atid=' . $params['at']->getID() . '&field_id=' . $field->getID() . '">' . dgettext('tuleap-tracker', '[Edit Reminder Settings]') . '</A>';
                    if ($tdrArtifactFieldFactory->notificationEnabled($field->getID())) {
                        $notif_status = dgettext('tuleap-tracker_date_reminder', 'active');
                    } else {
                        $notif_status = dgettext('tuleap-tracker_date_reminder', 'disabled');
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
    public function tracker_graphic_report_admin($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = HTTPRequest::instance();
        if ($request->getValidated('func', 'string') != 'date_field_notification') {
            return;
        }

        if (! user_isloggedin()) {
            exit_not_logged_in();
            return;
        }

        if (! $params['ath']->userIsAdmin()) {
            exit_permission_denied();
            return;
        }

        $field_id = $request->getValidated('field_id', 'uint');
        $field    = $params['art_field_fact']->getFieldFromId($field_id);
        if ($field && $field->isDateField() && ! $field->isSpecial()) {
            if ($request->isPost()) {
                if ($request->existAndNonEmpty('delete_reminder')) {
                    $tdrArtifactField = new TrackerDateReminder_ArtifactField();
                    $tdrArtifactField->deleteFieldReminderSettings($field->getID(), $params['ath']->getID());
                } elseif (array_key_exists('submit_notif_settings', $_REQUEST) && $_REQUEST['submit_notif_settings']) {
                    if ((! isset($_REQUEST['notified_users']) || (isset($_REQUEST['notified_users']) && $_REQUEST['notified_users'] == null)) && (! isset($_REQUEST['notified_groups']) || (isset($_REQUEST['notified_groups']) && $_REQUEST['notified_groups'] == null))) {
                        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker_date_reminder', 'Error. You must specify the users to be notified.'));
                    } elseif (
                        count($_REQUEST['notified_users']) == 1 && $_REQUEST['notified_users'][0] == 100 &&
                        count($_REQUEST['notified_groups']) == 1 && $_REQUEST['notified_groups'][0] == 100
                    ) {
                        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker_date_reminder', 'Error. You must specify the users to be notified.'));
                    } elseif (! isset($_REQUEST['start']) || (isset($_REQUEST['start']) && $_REQUEST['start'] == null)) {
                        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker_date_reminder', 'Error. You must specify the notification start date.'));
                    } elseif (! preg_match("/^[0-9]+$/", $_REQUEST['start']) || $_REQUEST['start'] < 0) {
                        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker_date_reminder', 'Error. You must specify a positive value.'));
                    } elseif (! isset($_REQUEST['frequency']) || (isset($_REQUEST['frequency']) && ($_REQUEST['frequency'] == null || $_REQUEST['frequency'] == 0))) {
                        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker_date_reminder', 'Error. You must specify the notification frequency.'));
                    } elseif (! preg_match("/^[0-9]+$/", $_REQUEST['frequency']) || $_REQUEST['frequency'] < 0) {
                        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker_date_reminder', 'Error. You must specify a positive value.'));
                    } elseif (! isset($_REQUEST['recurse']) || (isset($_REQUEST['recurse']) && ($_REQUEST['recurse'] == null || $_REQUEST['recurse'] == 0))) {
                        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker_date_reminder', 'Error. You must specify the number of mails to be sent.'));
                    } elseif (! preg_match("/^[0-9]+$/", $_REQUEST['recurse']) || $_REQUEST['recurse'] < 0) {
                        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker_date_reminder', 'Error. You must specify a positive value.'));
                    } else {
                        //merge notified_users and notified_groups into one array
                        $notified = [];
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
                        $res              = $tdrArtifactField->updateDateFieldReminderSettings($params['ath'], $field, $params['ath']->getID(), $_REQUEST['start'], $_REQUEST['notif_type'], $_REQUEST['frequency'], $_REQUEST['recurse'], $notified);
                        if ($res) {
                            $GLOBALS['Response']->addFeedback('info', sprintf(dgettext('tuleap-tracker_date_reminder', '\'%1$s\' Reminder Settings Update Successful.'), $field->getLabel()));
                        } else {
                            $GLOBALS['Response']->addFeedback('error', sprintf(dgettext('tuleap-tracker_date_reminder', '\'%1$s\' Reminder Settings Update Failed.'), $field->getLabel()));
                        }
                    }
                }
            }
            $params['ath']->adminHeader(['title' => dgettext('tuleap-tracker_date_reminder', 'Tracker Administration - Date Fields Reminder Settings'),
            ]);

            echo '<H2>' . dgettext('tuleap-tracker', 'Tracker') . ' \'<a href="/tracker/admin/?group_id=' . $params['ath']->Group->getID() . '&atid=' . $params['ath']->getID() . '">' . $params['ath']->getName() . '</a>\' - ' . dgettext('tuleap-tracker_date_reminder', 'Notification Settings') . '</h2>';

            $tdrArtifactFieldHtml = new TrackerDateReminder_ArtifactFieldHtml();
            $tdrArtifactFieldHtml->displayDateFieldNotificationSettings($params['ath'], $field);
            $params['ath']->footer([]);
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
    public function tracker_admin_field_delete($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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
    public function tracker_create_artifact($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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
    public function tracker_update_artifact($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['ah']->getStatusID() == 1) {
            $tdrArtifactType = new TrackerDateReminder_ArtifactType($params['ath']);
            $tdrArtifactType->deleteArtifactFromDateReminderProcessing(0, $params['ah']->getID(), $params['ath']->getID());
            $tdrArtifactType->addArtifactToDateReminderProcessing(0, $params['ah']->getID(), $params['ath']->getID());
        }
    }
}
