<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\Notifications\ConfigNotificationAssignedToDao;
use Tuleap\Tracker\Notifications\GlobalNotificationsAddressesBuilder;
use Tuleap\Tracker\Notifications\GlobalNotificationSubscribersFilter;
use Tuleap\Tracker\Notifications\NotificationCustomisationSettingsPresenter;
use Tuleap\Tracker\Notifications\NotificationLevelExtractor;
use Tuleap\Tracker\Notifications\NotificationListBuilder;
use Tuleap\Tracker\Notifications\NotificationsForceUsageUpdater;
use Tuleap\Tracker\Notifications\PaneNotificationListPresenter;
use Tuleap\Tracker\Notifications\Settings\Administration\CalendarConfigUpdater;
use Tuleap\Tracker\Notifications\Settings\CalendarEventConfigDao;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsDAO;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSender;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSenderDao;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDao;
use Tuleap\Tracker\Notifications\UsersToNotifyDao;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\User\InvalidEntryInAutocompleterCollection;
use Tuleap\User\RequestFromAutocompleter;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_NotificationsManager
{
    /** @var Tracker */
    private $tracker;

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
    /**
     * @var UserNotificationSettingsDAO
     */
    private $user_notification_settings_dao;
    /**
     * @var GlobalNotificationSubscribersFilter
     */
    private $subscribers_filter;
    /**
     * @var NotificationLevelExtractor
     */
    private $notification_level_extractor;
    /**
     * @var TrackerDao
     */
    private $tracker_dao;
    /**
     * @var ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var NotificationsForceUsageUpdater
     */
    private $force_usage_updater;

    public function __construct(
        $tracker,
        NotificationListBuilder $notification_list_builder,
        UsersToNotifyDao $user_to_notify_dao,
        UgroupsToNotifyDao $ugroup_to_notify_dao,
        UserNotificationSettingsDAO $user_notification_settings_dao,
        GlobalNotificationsAddressesBuilder $addresses_builder,
        UserManager $user_manager,
        UGroupManager $ugroup_manager,
        GlobalNotificationSubscribersFilter $subscribers_filter,
        NotificationLevelExtractor $notification_level_extractor,
        TrackerDao $tracker_dao,
        ProjectHistoryDao $project_history_dao,
        NotificationsForceUsageUpdater $force_usage_updater,
    ) {
        $this->tracker                        = $tracker;
        $this->user_to_notify_dao             = $user_to_notify_dao;
        $this->ugroup_to_notify_dao           = $ugroup_to_notify_dao;
        $this->user_notification_settings_dao = $user_notification_settings_dao;
        $this->addresses_builder              = $addresses_builder;
        $this->user_manager                   = $user_manager;
        $this->ugroup_manager                 = $ugroup_manager;
        $this->notification_list_builder      = $notification_list_builder;
        $this->subscribers_filter             = $subscribers_filter;
        $this->notification_level_extractor   = $notification_level_extractor;
        $this->tracker_dao                    = $tracker_dao;
        $this->project_history_dao            = $project_history_dao;
        $this->force_usage_updater            = $force_usage_updater;
    }

    public function displayTrackerAdministratorSettings(HTTPRequest $request, CSRFSynchronizerToken $csrf_token)
    {
        $this->displayAdminNotifications($csrf_token);
        (new Tracker_DateReminderRenderer($this->tracker))->displayDateReminders($request, $csrf_token);
    }

    public function processUpdate(HTTPRequest $request)
    {
        if ($this->requestProvidesNewNotificationLevel($request)) {
            $this->updateNotificationLevel($request);
        }

        $config_notification_assigned_to = new ConfigNotificationAssignedTo(new ConfigNotificationAssignedToDao());

        if ($request->exist('enable-assigned-to-me')) {
            $config_notification_assigned_to->enableAssignedToInSubject($this->tracker);
        } else {
            $config_notification_assigned_to->disableAssignedToInSubject($this->tracker);
        }

        $calendar_event_config_dao = new CalendarEventConfigDao();
        $calendar_config_updater   = new CalendarConfigUpdater(
            $calendar_event_config_dao,
            $calendar_event_config_dao,
            SemanticTimeframeBuilder::build(),
        );
        $calendar_config_updater
            ->updateConfigAccordingToRequest($this->tracker, $request)
            ->match(
                static function (bool $is_updated) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::SUCCESS,
                        dgettext(
                            'tuleap-tracker',
                            'Calendar events configuration updated'
                        )
                    );
                },
                static function (string $error) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        sprintf(
                            dgettext(
                                'tuleap-tracker',
                                'Unable to update calendar events configuration: %s',
                            ),
                            $error,
                        )
                    );
                }
            );

        $config_notification_custom_email_from = new ConfigNotificationEmailCustomSender(new ConfigNotificationEmailCustomSenderDao());

        $email_custom_enabled = $request->get('email-custom-enabled');
        $email_custom_from    = $request->get('email-custom-from');
        if ($request->exist('email-custom-from')) {
            $config_notification_custom_email_from->setCustomSender($this->tracker, $email_custom_from, $email_custom_enabled);
        }

        $new_global_notification = $request->get('new_global_notification');
        $global_notification     = $request->get('global_notification');
        $remove_global           = $request->get('remove_global');
        $notification_id         = $request->get('submit_notification_edit');

        if ($remove_global) {
            $this->deleteGlobalNotification($remove_global);
        } elseif ($new_global_notification && $new_global_notification['addresses']) {
            $this->createNewGlobalNotification($new_global_notification);
        } elseif ($global_notification && $notification_id) {
            $this->updateGlobalNotification($notification_id, $global_notification[$notification_id]);
        }

        $new_unsubscribers = $request->get('new_unsubscriber');
        if ($new_unsubscribers !== false) {
            $this->addUnsubscribers($new_unsubscribers);
        }
        $remove_unsubscribers = $request->get('remove_unsubscribers');
        if ($remove_unsubscribers !== false) {
            $this->deleteUnsubscribers($remove_unsubscribers);
        }
    }

    private function createNewGlobalNotification($global_notification_data)
    {
        $invalid_entries = new InvalidEntryInAutocompleterCollection();
        $autocompleter   = $this->getAutocompleter($global_notification_data['addresses'], $invalid_entries);
        $invalid_entries->generateWarningMessageForInvalidEntries();

        if ($this->isNotificationEmpty($autocompleter)) {
            $this->addFeedbackNoElement();
            return;
        }

        $notification_id = $this->notificationAddEmails($global_notification_data, $autocompleter);

        if (! $notification_id) {
            $this->addFeedbackNotSaved();
            return;
        }

        $this->notificationAddUsers($notification_id, $autocompleter);
        $this->notificationAddUgroups($notification_id, $autocompleter);
        $this->addFeedbackCorrectlySaved();
    }

    private function updateGlobalNotification($notification_id, $notification)
    {
        $global_notifications = $this->getGlobalNotifications();
        if (array_key_exists($notification_id, $global_notifications)) {
            $invalid_entries           = new InvalidEntryInAutocompleterCollection();
            $autocompleter             = $this->getAutocompleter($notification['addresses'], $invalid_entries);
            $emails                    = $autocompleter->getEmails();
            $notification['addresses'] = $this->addresses_builder->transformNotificationAddressesArrayAsString($emails);

            $invalid_entries->generateWarningMessageForInvalidEntries();

            if (! $this->getGlobalDao()->modify($notification_id, $notification)) {
                $this->addFeedbackNotSaved();
                return;
            }

            $this->user_to_notify_dao->deleteByNotificationId($notification_id);
            $this->ugroup_to_notify_dao->deleteByNotificationId($notification_id);

            if ($this->isNotificationEmpty($autocompleter)) {
                $this->removeGlobalNotification($notification_id);
            } else {
                $this->notificationAddUsers($notification_id, $autocompleter);
                $this->notificationAddUgroups($notification_id, $autocompleter);
                $this->addFeedbackCorrectlySaved();
            }
        }
    }

    private function deleteGlobalNotification($remove_global)
    {
        foreach ($remove_global as $notification_id => $value) {
            $this->removeGlobalNotification($notification_id);
        }
    }

    private function addUnsubscribers($new_unsubcribers)
    {
        $invalid_entries = new InvalidEntryInAutocompleterCollection();
        $autocompleter   = $this->getAutocompleter($new_unsubcribers, $invalid_entries);
        $invalid_entries->generateWarningMessageForInvalidEntries();

        $users_to_add_as_unsubcribers = $autocompleter->getUsers();
        if (empty($users_to_add_as_unsubcribers)) {
            return;
        }

        foreach ($users_to_add_as_unsubcribers as $new_unsubcriber) {
            $this->user_notification_settings_dao->enableNoNotificationAtAllMode(
                $new_unsubcriber->getId(),
                $this->tracker->getId()
            );
        }

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-tracker', 'The unsubscribe list has been successfully updated.')
        );
    }

    private function deleteUnsubscribers(array $unsubscribers)
    {
        foreach ($unsubscribers as $user_id => $value) {
            $this->user_notification_settings_dao->enableNoGlobalNotificationMode($user_id, $this->tracker->getId());
        }
        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-tracker', 'The unsubscribe list has been successfully updated.')
        );
    }

    private function displayAdminNotifications(CSRFSynchronizerToken $csrf_token)
    {
        echo '<fieldset><form id="tracker-admin-notifications-form" method="POST">' . $csrf_token->fetchHTMLInput();

        $this->displayAdminNotifications_Toggle();
        if ($this->tracker->getNotificationsLevel() !== Tracker::NOTIFICATIONS_LEVEL_DISABLED) {
            $this->displayAdminNotifications_Global();
            $this->displayAdminNotificationUnsubcribers();
        }
        $this->displayAdminMailConfiguration();

        echo '</form></fieldset>';
    }

    protected function displayAdminNotifications_Toggle() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $renderer            = $this->getNotificationsRenderer();
        $notifications_level = $this->tracker->getNotificationsLevel();
        $renderer->renderToPage(
            'admin-notifications-level',
            [
                'disabled_value'              => Tracker::NOTIFICATIONS_LEVEL_DISABLED,
                'default_value'               => Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
                'status_change_value'         => Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
                'is_default'                  => $notifications_level === Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
                'is_disabled'                 => $notifications_level === Tracker::NOTIFICATIONS_LEVEL_DISABLED,
                'is_status_change'            => $notifications_level === Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE,
                'has_status_semantic_defined' => $this->tracker->hasSemanticsStatus(),
            ]
        );
    }

    private function displayAdminMailConfiguration(): void
    {
        $config_notification_assigned_to   = new ConfigNotificationAssignedTo(new ConfigNotificationAssignedToDao());
        $config_notification_custom_sender
            = new ConfigNotificationEmailCustomSender(new ConfigNotificationEmailCustomSenderDao());
        $is_assigned_to_enabled            = $config_notification_assigned_to->isAssignedToSubjectEnabled($this->tracker);

        $custom_email_sender = $config_notification_custom_sender->getCustomSender($this->tracker);

        $should_send_event_in_notification = (new CalendarEventConfigDao())->shouldSendEventInNotification($this->tracker->getId());
        $semantic_timeframe_builder        = SemanticTimeframeBuilder::build();
        $semantic_timeframe                = $semantic_timeframe_builder->getSemantic($this->tracker);
        $is_semantic_timeframe_defined     = $semantic_timeframe->isDefined();

        $semantic_title            = Tracker_Semantic_Title::load($this->tracker);
        $is_semantic_title_defined = $semantic_title->getField() !== null;

        $renderer = $this->getNotificationsRenderer();
        $renderer->renderToPage(
            'admin-mail-configuration',
            new NotificationCustomisationSettingsPresenter(
                $is_assigned_to_enabled,
                $custom_email_sender,
                $should_send_event_in_notification,
                $is_semantic_timeframe_defined,
                $semantic_timeframe->getUrl(),
                $is_semantic_title_defined,
                $semantic_title->getUrl(),
            )
        );
    }

    private function displayAdminNotifications_Global() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        echo '<h3><a name="GlobalEmailNotification"></a>' . dgettext('tuleap-tracker', 'Global Email Notification') . ' ' .
        help_button('trackers/administration/configuration/notifications.html#global-email-notification') . '</h3>';

        $notifs   = $this->getGlobalNotifications();
        $renderer = $this->getNotificationsRenderer();
        $renderer->renderToPage(
            'notifications',
            new PaneNotificationListPresenter(
                $this->tracker->getGroupId(),
                $this->tracker->getId(),
                $this->notification_list_builder->getNotificationsPresenter($notifs, $this->addresses_builder)
            )
        );
        $assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../frontend-assets', '/assets/trackers');
        $GLOBALS['Response']->includeFooterJavascriptFile($assets->getFileURL('tracker-admin.js'));
    }

    private function displayAdminNotificationUnsubcribers()
    {
        $unsubscriber_list_presenter = $this->notification_list_builder->getUnsubscriberListPresenter($this->tracker);
        $renderer                    = $this->getNotificationsRenderer();
        $renderer->renderToPage('admin-notifications-unsubscribers', $unsubscriber_list_presenter);
    }

    /**
     * @return TemplateRenderer
     */
    private function getNotificationsRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(dirname(TRACKER_BASE_DIR) . '/templates/notifications');
    }

    /**
     * @return Tracker_GlobalNotification[]
     */
    public function getGlobalNotifications()
    {
        $notifs = [];
        foreach ($this->getGlobalDao()->searchByTrackerId((int) $this->tracker->id) as $row) {
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
     * @param int $all_updates
     * @param int $check_permissions
     * @return int last inserted id in database
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
        $users           = $autocompleter->getUsers();
        $users_not_added = [];
        $user_ids        = [];
        foreach ($users as $user) {
            $user_ids[] = $user->getId();
        }
        $user_ids_filtered = $this->subscribers_filter->filterInvalidUserIDs($this->tracker, $user_ids);
        array_filter($users, function (PFUser $user) use ($user_ids_filtered, &$users_not_added) {
            if (! in_array($user->getId(), $user_ids_filtered)) {
                $users_not_added[] = $user;
                return false;
            }
            return true;
        });
        foreach ($users as $user) {
            if (! $this->user_to_notify_dao->insert($notification_id, $user->getId())) {
                $users_not_added[] = $user->getUserName();
            }
        }

        if (! empty($users_not_added)) {
            $this->addFeedbackUsersNotAdded($users_not_added);
        }

        return empty($users_not_added);
    }

    private function notificationAddUgroups($notification_id, RequestFromAutocompleter $autocompleter)
    {
        $ugroups           = $autocompleter->getUgroups();
        $ugroups_not_added = [];
        foreach ($ugroups as $ugroup) {
            if (! $this->ugroup_to_notify_dao->insert($notification_id, $ugroup->getId())) {
                $ugroups_not_added[] = $ugroup->getTranslatedName();
            }
        }

        if (! empty($ugroups_not_added)) {
            $this->addFeedbackUgroupsNotAdded($ugroups_not_added);
        }

        return empty($ugroups_not_added);
    }

    protected function removeGlobalNotification($id)
    {
        $dao   = $this->getGlobalDao();
        $notif = $dao->searchById($id);

        if (empty($notif)) {
            $this->addFeedbackNotDeleted();
            return;
        }

        if ($dao->delete($id, $this->tracker->id)) {
            $this->user_to_notify_dao->deleteByNotificationId($id);
            $this->ugroup_to_notify_dao->deleteByNotificationId($id);
            $this->addFeedbackCorrectlyDeleted();
        } else {
            $this->addFeedbackNotDeleted();
        }
    }

    /**
     * @param bool $update true if the action is an update one (update artifact, add comment, ...) false if it is a create action.
     */
    public function getAllAddresses($update = false)
    {
        $addresses = [];
        $notifs    = $this->getGlobalNotifications();
        foreach ($notifs as $key => $nop) {
            if (! $update || $notifs[$key]->isAllUpdates()) {
                foreach (preg_split('/[,;]/', $notifs[$key]->getAddresses()) as $address) {
                    $addresses[] = ['address' => $address, 'check_permissions' => $notifs[$key]->isCheckPermissions()];
                }
            }
        }
        return $addresses;
    }

    protected function getGlobalDao()
    {
        return new Tracker_GlobalNotificationDao();
    }

    public function removeAddressByTrackerId($tracker_id, PFUser $user)
    {
        $dao               = $this->getGlobalDao();
        $addresses_builder = $this->getGlobalNotificationsAddressesBuilder();

        foreach ($dao->searchByTrackerId($tracker_id) as $row) {
            $notification_id   = $row['id'];
            $addresses         = $row['addresses'];
            $updated_addresses = $addresses_builder->removeAddressFromString($addresses, $user);

            if (empty($updated_addresses)) {
                $users_to_notify_exist   = $this->user_to_notify_dao->searchUsersByNotificationId($notification_id);
                $ugroups_to_notify_exist = $this->ugroup_to_notify_dao->searchUgroupsByNotificationId($notification_id);

                if ($users_to_notify_exist->count() === 0 && $ugroups_to_notify_exist->count() === 0) {
                    $dao->delete($notification_id, $tracker_id);
                }
            } elseif ($addresses !== $updated_addresses) {
                $dao->updateAddressById($notification_id, $updated_addresses);
            }
        }
    }

    protected function getGlobalNotificationsAddressesBuilder()
    {
        return new GlobalNotificationsAddressesBuilder();
    }

    public static function isMailingList($email_address)
    {
        $r = preg_match_all('/\S+\@lists\.\S+/', $subject, $matches);
        if (! empty($r)) {
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
     * @return bool
     */
    private function isNotificationEmpty(RequestFromAutocompleter $autocompleter)
    {
        $emails  = $autocompleter->getEmails();
        $ugroups = $autocompleter->getUgroups();
        $users   = $autocompleter->getUsers();
        return empty($emails) && empty($ugroups) && empty($users);
    }

    private function addFeedbackCorrectlySaved()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext(
                'tuleap-tracker',
                'Notification successfully saved.'
            )
        );
    }

    protected function addFeedbackCorrectlyDeleted()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext(
                'tuleap-tracker',
                'Notification successfully deleted.'
            )
        );
    }

    private function addFeedbackUsersNotAdded($users_not_added)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::WARN,
            sprintf(
                dngettext(
                    'tuleap-tracker',
                    "User '%s' couldn't be added.",
                    "Users '%s' couldn't be added.",
                    count($users_not_added)
                ),
                implode("' ,'", $users_not_added)
            )
        );
    }

    private function addFeedbackUgroupsNotAdded($ugroups_not_added)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::WARN,
            sprintf(
                dngettext(
                    'tuleap-tracker',
                    "Group '%s' couldn't be added.",
                    "Groups '%s' couldn't be added.",
                    count($ugroups_not_added)
                ),
                implode("' ,'", $ugroups_not_added)
            )
        );
    }

    private function addFeedbackNotSaved()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext(
                'tuleap-tracker',
                'The notification could not be saved.'
            )
        );
    }

    private function addFeedbackNotDeleted()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext(
                'tuleap-tracker',
                'The notification could not be deleted.'
            )
        );
    }

    private function addFeedbackNoElement()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext(
                'tuleap-tracker',
                'No element selected.'
            )
        );
    }

    private function getNotificationLevelLabel($notification_level)
    {
        switch ($notification_level) {
            case Tracker::NOTIFICATIONS_LEVEL_DISABLED:
                return dgettext('plugin-tracker', 'No notifications');
            case Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE:
                return dgettext('plugin-tracker', 'Status change notifications');
            default:
                return dgettext('plugin-tracker', 'Default Tuleap notifications');
        }
    }

    private function updateNotificationLevel(HTTPRequest $request)
    {
        if (! $this->notificationLevelMustBeUpdated($request)) {
            return;
        }

        $new_notifications_level = $this->notification_level_extractor->extractNotificationLevel($request);

        if ($request->exist('submit_and_force_notifications_level')) {
            $this->force_usage_updater->forceUserPreferences($this->tracker, $new_notifications_level);
        }

        $this->tracker->setNotificationsLevel($new_notifications_level);
        if ($this->tracker_dao->save($this->tracker)) {
            if ($request->exist('submit_and_force_notifications_level')) {
                $this->project_history_dao->groupAddHistory(
                    'global_notification_update_with_force',
                    $this->getNotificationLevelLabel($new_notifications_level),
                    $this->tracker->getGroupId(),
                    [$this->tracker->getName()]
                );
            } else {
                $this->project_history_dao->groupAddHistory(
                    'global_notification_update',
                    $this->getNotificationLevelLabel($new_notifications_level),
                    $this->tracker->getGroupId(),
                    [$this->tracker->getName()]
                );
            }

            $this->addFeedbackCorrectlySaved();
        }
    }

    private function notificationLevelMustBeUpdated(HTTPRequest $request)
    {
        return ((int) $this->tracker->getNotificationsLevel() !== (int) $request->get('notifications_level')) ||
            $request->exist('disable_notifications');
    }

    /**
     * @return bool
     */
    private function requestProvidesNewNotificationLevel(HTTPRequest $request)
    {
        return $request->exist('notifications_level') ||
            $request->exist('disable_notifications') ||
            $request->exist('enable_notifications');
    }
}
