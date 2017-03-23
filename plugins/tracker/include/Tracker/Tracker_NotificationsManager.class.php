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

use Tuleap\Tracker\Notifications\GlobalNotificationsAddressesBuilder;
use Tuleap\Tracker\Notifications\NotificationListBuilder;
use Tuleap\Tracker\Notifications\PaneNotificationListPresenter;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDao;
use Tuleap\Tracker\Notifications\UsersToNotifyDao;
use Tuleap\User\InvalidEntryInAutocompleterCollection;
use Tuleap\User\RequestFromAutocompleter;

class Tracker_NotificationsManager {

    protected $tracker;

    /**
     * @var UsersToNotifyDao
     */
    private $user_to_notify_dao;
    /**
     * @var UgroupsToNotifyDao
     */
    private $ugroup_to_notify_dao;
    /**
     * @var GlobalNotificationsAddressesBuilder
     */
    private $addresses_builder;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var NotificationListBuilder
     */
    private $notification_list_builder;

    public function __construct(
        $tracker,
        NotificationListBuilder $notification_list_builder,
        UsersToNotifyDao $user_to_notify_dao,
        UgroupsToNotifyDao $ugroup_to_notify_dao,
        GlobalNotificationsAddressesBuilder $addresses_builder,
        UserManager $user_manager,
        UGroupManager $ugroup_manager
    ) {
        $this->tracker                       = $tracker;
        $this->user_to_notify_dao            = $user_to_notify_dao;
        $this->ugroup_to_notify_dao          = $ugroup_to_notify_dao;
        $this->addresses_builder             = $addresses_builder;
        $this->user_manager                  = $user_manager;
        $this->ugroup_manager                = $ugroup_manager;
        $this->notification_list_builder     = $notification_list_builder;
    }

    public function process(TrackerManager $tracker_manager, Codendi_Request $request, $current_user)
    {
        if ($request->exist('stop_notification')) {
            if ($this->tracker->stop_notification != $request->get('stop_notification')) {
                $this->tracker->stop_notification = $request->get('stop_notification') ? 1 : 0;
                $dao                              = new TrackerDao();
                if ($dao->save($this->tracker)) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_notification', 'successfully_updated'));
                }
            }
        }

        $new_global_notification = $request->get('new_global_notification');
        $global_notification     = $request->get('global_notification');
        $remove_global           = $request->get('remove_global');
        $notification_id         = $request->get('submit_notification_edit');

        if ($remove_global) {
            $this->deleteGlobalNotification($remove_global);
        } else if ($new_global_notification && $new_global_notification['addresses']) {
            $this->createNewGlobalNotification($new_global_notification);
        } else if ($global_notification && $notification_id) {
            $this->updateGlobalNotification($notification_id, $global_notification[$notification_id]);
        }

        $this->displayAdminNotifications($tracker_manager, $request, $current_user);
        $reminderRenderer = new Tracker_DateReminderRenderer($this->tracker);

        if ($this->tracker->userIsAdmin($current_user)) {
            $reminderRenderer->displayDateReminders($request);
        }

        $reminderRenderer->displayFooter($tracker_manager);
    }

    private function createNewGlobalNotification($global_notification_data)
    {
        $invalid_entries = new InvalidEntryInAutocompleterCollection();
        $autocompleter = $this->getAutocompleter($global_notification_data['addresses'], $invalid_entries);
        $invalid_entries->generateWarningMessageForInvalidEntries();

        if (! $this->isNotificationEmpty($autocompleter)) {
            $notification_id = $this->notificationAddEmails($global_notification_data, $autocompleter);
            $this->notificationAddUsers($notification_id, $autocompleter);
            $this->notificationAddUgroups($notification_id, $autocompleter);
        }
    }

    private function updateGlobalNotification($notification_id, $notification)
    {
        $global_notifications = $this->getGlobalNotifications();
        if (array_key_exists($notification_id, $global_notifications)) {
            $invalid_entries = new InvalidEntryInAutocompleterCollection();
            $autocompleter             = $this->getAutocompleter($notification['addresses'], $invalid_entries);
            $emails                    = $autocompleter->getEmails();
            $notification['addresses'] = $this->addresses_builder->transformNotificationAddressesArrayAsString($emails);

            $invalid_entries->generateWarningMessageForInvalidEntries();

            $this->getGlobalDao()->modify($notification_id, $notification);
            $this->user_to_notify_dao->deleteByNotificationId($notification_id);
            $this->ugroup_to_notify_dao->deleteByNotificationId($notification_id);

            if ($this->isNotificationEmpty($autocompleter)) {
                $this->removeGlobalNotification($notification_id);
            } else {
                $this->notificationAddUsers($notification_id, $autocompleter);
                $this->notificationAddUgroups($notification_id, $autocompleter);
            }
        }
    }

    private function deleteGlobalNotification($remove_global)
    {
        foreach ($remove_global as $notification_id => $value) {
            $this->removeGlobalNotification($notification_id);
        }
    }

    protected function displayAdminNotifications(TrackerManager $tracker_manager, $request, $current_user) {
        $this->tracker->displayAdminItemHeader($tracker_manager, 'editnotifications');
        echo '<fieldset><form id="tracker-admin-notifications-form" action="'.TRACKER_BASE_URL.'/?tracker='. (int)$this->tracker->id .'&amp;func=admin-notifications" method="POST">';

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
                    $this->tracker->getGroupId(),
                    $this->notification_list_builder->getNotificationsPresenter($notifs, $this->addresses_builder)
                )
            );
            $GLOBALS['Response']->includeFooterJavascriptFile('/scripts/tuleap/user-and-ugroup-autocompleter.js');
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
    protected function addGlobalNotification($addresses, $all_updates, $check_permissions)
    {
        return $this->getGlobalDao()->create($this->tracker->id, $addresses, $all_updates, $check_permissions);
    }

    private function notificationAddEmails($global_notification_data, RequestFromAutocompleter $autocompleter)
    {
        $emails          = $autocompleter->getEmails();
        $notification_id = $this->addGlobalNotification(
            $this->addresses_builder->transformNotificationAddressesArrayAsString($emails),
            $global_notification_data['all_updates'],
            $global_notification_data['check_permissions']
        );

        return $notification_id;
    }

    private function notificationAddUsers($notification_id, RequestFromAutocompleter $autocompleter)
    {
        $users = $autocompleter->getUsers();
        foreach ($users as $user) {
            $this->user_to_notify_dao->insert($notification_id, $user->getId());
        }
    }

    private function notificationAddUgroups($notification_id, RequestFromAutocompleter $autocompleter)
    {
        $ugroups = $autocompleter->getUgroups();
        foreach ($ugroups as $ugroup) {
            $this->ugroup_to_notify_dao->insert($notification_id, $ugroup->getId());
        }
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

    public function removeAddressByTrackerId($tracker_id, PFUser $user)
    {
        $dao               = $this->getGlobalDao();
        $addresses_builder = $this->getGlobalNotificationsAddressesBuilder();

        foreach($dao->searchByTrackerId($tracker_id) as $row) {
            $notification_id   = $row['id'];
            $addresses         = $row['addresses'];
            $updated_addresses = $addresses_builder->removeAddressFromString($addresses, $user);

            if (empty($updated_addresses)) {
                $users_to_notify_exist   = $this->user_to_notify_dao->searchUsersByNotificationId($notification_id);
                $ugroups_to_notify_exist = $this->ugroup_to_notify_dao->searchUgroupsByNotificationId($notification_id);

                if ($users_to_notify_exist->count() === 0 && $ugroups_to_notify_exist->count() === 0) {
                    $dao->delete($notification_id, $tracker_id);
                }
            } else if ($addresses !== $updated_addresses) {
                $dao->updateAddressById($notification_id, $updated_addresses);
            }
        }
    }

    protected function getWatcherDao() {
        return new Tracker_WatcherDao();
    }

    protected function getNotificationDao() {
        return new Tracker_NotificationDao();
    }

    protected function getGlobalNotificationsAddressesBuilder()
    {
        return new GlobalNotificationsAddressesBuilder();
    }

    public static function isMailingList($email_address) {
        $r = preg_match_all('/\S+\@lists\.\S+/', $subject, $matches);
        if ( !empty($r)  ) {
            return true;
        }
        return false;
    }

    /**
     * @return RequestFromAutocompleter
     */
    private function getAutocompleter($addresses, InvalidEntryInAutocompleterCollection $invalid_entries)
    {
        $autocompleter = new RequestFromAutocompleter(
            $invalid_entries,
            new Rule_Email(),
            UserManager::instance(),
            $this->ugroup_manager,
            $this->user_manager->getCurrentUser(),
            $this->tracker->getProject(),
            $addresses
        );
        return $autocompleter;
    }

    /**
     * @return boolean
     */
    private function isNotificationEmpty(RequestFromAutocompleter $autocompleter)
    {
        $emails  = $autocompleter->getEmails();
        $ugroups = $autocompleter->getUgroups();
        $users   = $autocompleter->getUsers();
        return empty($emails) && empty($ugroups) && empty($users);
    }
}
