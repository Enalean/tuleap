<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright Enalean (c) 2017. All rights reserved.
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

use Tuleap\Tracker\Notifications\CollectionOfUgroupToBeNotifiedPresenterBuilder;
use Tuleap\Tracker\Notifications\CollectionOfUserToBeNotifiedPresenterBuilder;
use Tuleap\Tracker\Notifications\PaneNotificationListPresenter;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDao;
use Tuleap\Tracker\Notifications\UsersToNotifyDao;

class Tracker_NotificationsManager {

    protected $tracker;

    /**
     * @var CollectionOfUserToBeNotifiedPresenterBuilder
     */
    private $user_to_be_notified_builder;
    /**
     * @var UsersToNotifyDao
     */
    private $user_to_notify_dao;
    /**
     * @var CollectionOfUgroupToBeNotifiedPresenterBuilder
     */
    private $ugroup_to_be_notified_builder;
    /**
     * @var UgroupsToNotifyDao
     */
    private $ugroup_to_notify_dao;

    public function __construct(
        $tracker,
        CollectionOfUserToBeNotifiedPresenterBuilder $user_to_be_notified_builder,
        CollectionOfUgroupToBeNotifiedPresenterBuilder $ugroup_to_be_notified_builder,
        UsersToNotifyDao $user_to_notify_dao,
        UgroupsToNotifyDao $ugroup_to_notify_dao
    ) {
        $this->tracker                       = $tracker;
        $this->user_to_be_notified_builder   = $user_to_be_notified_builder;
        $this->user_to_notify_dao            = $user_to_notify_dao;
        $this->ugroup_to_be_notified_builder = $ugroup_to_be_notified_builder;
        $this->ugroup_to_notify_dao          = $ugroup_to_notify_dao;
    }

    public function process(TrackerManager $tracker_manager, Codendi_Request $request, $current_user) {
        if ($request->exist('stop_notification')) {
            if ($this->tracker->stop_notification != $request->get('stop_notification')) {
                $this->tracker->stop_notification = $request->get('stop_notification') ? 1 : 0;
                $dao                              = new TrackerDao();
                if ($dao->save($this->tracker)) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_notification', 'successfully_updated'));
                }
            }
        }

        if ($global_notification_data = $request->get('global_notification')) {
            if (!empty($global_notification_data)) {
                $this->processGlobalNotificationDataForUpdate($global_notification_data);
            }
        }

        $this->createNewGlobalNotification($request);
        $this->deleteGlobalNotification($request);

        $this->displayAdminNotifications($tracker_manager, $request, $current_user);
        $reminderRenderer = new Tracker_DateReminderRenderer($this->tracker);

        if ($this->tracker->userIsAdmin($current_user)) {
            $reminderRenderer->displayDateReminders($request);
        }

        $reminderRenderer->displayFooter($tracker_manager);
    }

    private function createNewGlobalNotification(Codendi_Request $request)
    {
        if ($request->exist('new_global_notification')) {
            $global_notification_data = $request->get('new_global_notification');

            if ($global_notification_data['addresses'] !== '') {
                $this->addGlobalNotification(
                    $global_notification_data['addresses'],
                    $global_notification_data['all_updates'],
                    $global_notification_data['check_permissions']
                );
            }
        }
    }

    private function deleteGlobalNotification(Codendi_Request $request)
    {
        if ($request->exist('remove_global')) {
            foreach ($request->get('remove_global') as $notification_id => $value) {
                $this->removeGlobalNotification($notification_id);
            }
        }
    }

    protected function displayAdminNotifications(TrackerManager $tracker_manager, $request, $current_user) {
        $this->tracker->displayAdminItemHeader($tracker_manager, 'editnotifications');
        echo '<fieldset><form action="'.TRACKER_BASE_URL.'/?tracker='. (int)$this->tracker->id .'&amp;func=admin-notifications" method="POST">';

        $this->displayAdminNotifications_Toggle();
        $this->displayAdminNotifications_Global($request);

        echo '</form></fieldset>';
    }

    protected function displayAdminNotifications_Toggle() {
        if ($this->tracker->userIsAdmin()) {
            echo '<h3><a name="ToggleEmailNotification"></a>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','toggle_notification').' '.
            help_button('tracker.html#e-mail-notification').'</h3>';
            echo '
                <p>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','toggle_notif_note').'<br>
                <br><input type="hidden" name="stop_notification" value="0" /> 
                <label class="checkbox"><input id="toggle_stop_notification" type="checkbox" name="stop_notification" value="1" '.(($this->tracker->stop_notification)?'checked="checked"':'').' /> '.
                $GLOBALS['Language']->getText('plugin_tracker_include_type','stop_notification') .'</label>';
        } else if ($this->tracker->stop_notification) {
            echo '<h3><a name="ToggleEmailNotification"></a>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','notification_suspended').' '.
            help_button('tracker.html#e-mail-notification').'</h3>';
            echo '
            <P><b>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','toggle_notif_warn').'</b><BR>';
        }

        echo '<input class="btn" type="submit" value="'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','submit').'"/>';
    }

    protected function displayAdminNotifications_Global(HTTPRequest $request) {
        echo '<h3><a name="GlobalEmailNotification"></a>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','global_mail_notif').' '.
        help_button('tracker.html#e-mail-notification').'</h3>';

        $notifs    = $this->getGlobalNotifications();
        $nb_notifs = count($notifs);
        if ($this->tracker->userIsAdmin()) {
            $renderer = TemplateRendererFactory::build()->getRenderer(dirname(TRACKER_BASE_DIR).'/templates/notifications');
            $renderer->renderToPage(
                'notifications',
                new PaneNotificationListPresenter(
                    $notifs,
                    $this->user_to_be_notified_builder,
                    $this->ugroup_to_be_notified_builder
                )
            );
        } else {
            $ok = false;
            if ( $nb_notifs ) {
                reset($notifs);
                while(!$ok && (list($id,) = each($notifs))) {
                    $ok = $notifs[$id]->getAddresses();
                }
            }
            if ($ok) {
                echo $GLOBALS['Language']->getText('plugin_tracker_include_type','admin_conf');
                foreach($notifs as $key => $nop) {
                    if ($notifs[$key]->getAddresses()) {
                        echo '<div>'. $notifs[$key]->getAddresses() .'&nbsp;&nbsp;&nbsp; ';
                        echo $GLOBALS['Language']->getText('plugin_tracker_include_type','send_all_or_not',($notifs[$key]->isAllUpdates()?$GLOBALS['Language']->getText('global','yes'):$GLOBALS['Language']->getText('global','no')));
                        echo '</div>';
                    }
                }
            } else {
                echo $GLOBALS['Language']->getText('plugin_tracker_include_type','admin_not_conf');
            }
        }
    }

    /**
     * this function process global notification data
     * @param Array<Array> $data
     */
    private function processGlobalNotificationDataForUpdate($data)
    {
        $global_notifications = $this->getGlobalNotifications();
        foreach ( $data as $id=>$fields ) {
            if ( empty($fields['addresses']) ) {
                continue;
            }

            if ( !isset($fields['all_updates']) ) {
                continue;
            }

            if ( !isset($fields['check_permissions']) ) {
                continue;
            }

            if ( array_key_exists($id, $global_notifications) ) {
                $this->updateGlobalNotification($id, $fields);
            }
        }
    }

    /**
     * @return Tracker_GlobalNotification[]
     */
    public function getGlobalNotifications() {
        $notifs = array();
        foreach($this->getGlobalDao()->searchByTrackerId($this->tracker->id) as $row) {
            $notifs[$row['id']] = new Tracker_GlobalNotification(
                $row['id'],
                $this->tracker->id,
                $row['addresses'],
                $row['all_updates'],
                $row['check_permissions']
            );
        }
        return $notifs;
    }

    /**
     *
     * @param String $addresses
     * @param Integer $all_updates
     * @param Integer $check_permissions
     * @return Integer last inserted id in database
     */
    protected function addGlobalNotification( $addresses, $all_updates, $check_permissions ) {
        return $this->getGlobalDao()->create($this->tracker->id, $addresses, $all_updates, $check_permissions);
    }

    protected function removeGlobalNotification($id)
    {
        $dao   = $this->getGlobalDao();
        $notif = $dao->searchById($id);

        if (! empty($notif)) {
            $deletion_result = $dao->delete($id, $this->tracker->id);

            if ($deletion_result) {
                $this->user_to_notify_dao->deleteByNotificationId($id);
                $this->ugroup_to_notify_dao->deleteByNotificationId($id);
            }
        }
    }

    protected function updateGlobalNotification($global_notification_id, $data) {
        $feedback = '';
        $arr_email_address = preg_split('/[,;]/', $data['addresses']);
        if (!util_validateCCList($arr_email_address, $feedback, false)) {
          $GLOBALS['Response']->addFeedback('error', $feedback);
        } else {
          $data['addresses'] = util_cleanup_emails(implode(', ', $arr_email_address));
          return $this->getGlobalDao()->modify($global_notification_id, $data);
        }
        return false;
    }

    /**
     * @param boolean $update true if the action is an update one (update artifact, add comment, ...) false if it is a create action.
     */
    public function getAllAddresses($update = false) {
        $addresses = array();
        $notifs = $this->getGlobalNotifications();
        foreach($notifs as $key => $nop) {
            if (!$update || $notifs[$key]->isAllUpdates()) {
                foreach(preg_split('/[,;]/', $notifs[$key]->getAddresses()) as $address) {
                    $addresses[] = array('address' => $address, 'check_permissions' => $notifs[$key]->isCheckPermissions());
                }
            }
        }
        return $addresses;
    }

    protected function getGlobalDao() {
        return new Tracker_GlobalNotificationDao();
    }

    protected function getWatcherDao() {
        return new Tracker_WatcherDao();
    }

    protected function getNotificationDao() {
        return new Tracker_NotificationDao();
    }

    public static function isMailingList($email_address) {
        $r = preg_match_all('/\S+\@lists\.\S+/', $subject, $matches);
        if ( !empty($r)  ) {
            return true;
        }
        return false;
    }
}
