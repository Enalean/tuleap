<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications\Settings;

use Tracker_DateReminderManager;
use Tracker_NotificationsManager;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Notifications\CollectionOfUgroupToBeNotifiedPresenterBuilder;
use Tuleap\Tracker\Notifications\CollectionOfUserInvolvedInNotificationPresenterBuilder;
use Tuleap\Tracker\Notifications\GlobalNotificationsAddressesBuilder;
use Tuleap\Tracker\Notifications\GlobalNotificationSubscribersFilter;
use Tuleap\Tracker\Notifications\InvolvedNotificationDao;
use Tuleap\Tracker\Notifications\NotificationLevelExtractor;
use Tuleap\Tracker\Notifications\NotificationListBuilder;
use Tuleap\Tracker\Notifications\NotificationsForceUsageUpdater;
use Tuleap\Tracker\Notifications\RecipientsManager;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDao;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;
use Tuleap\Tracker\Notifications\UserNotificationOnlyStatusChangeDAO;
use Tuleap\Tracker\Notifications\UsersToNotifyDao;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\User\NotificationOnAllUpdatesRetriever;
use Tuleap\Tracker\User\NotificationOnOwnActionRetriever;
use UGroupDao;
use UGroupManager;
use UserManager;
use UserPreferencesDao;

trait NotificationsAdminSettingsControllerCommon
{
    /**
     * @return \Tuleap\Tracker\Tracker
     * @throws NotFoundException
     */
    private function getTrackerFromTrackerID(\TrackerFactory $tracker_factory, $id)
    {
        $tracker = $tracker_factory->getTrackerById($id);
        if ($tracker === null) {
            throw new NotFoundException(dgettext('tuleap-tracker', 'That tracker does not exist.'));
        }
        return $tracker;
    }

    /**
     * @return Tracker_DateReminderManager
     */
    private function getDateReminderManager(\Tuleap\Tracker\Tracker $tracker)
    {
        return new Tracker_DateReminderManager($tracker);
    }

    /**
     * @return Tracker_NotificationsManager
     */
    private function getNotificationsManager(UserManager $user_manager, \Tuleap\Tracker\Tracker $tracker)
    {
        $user_to_notify_dao             = new UsersToNotifyDao();
        $ugroup_to_notify_dao           = new UgroupsToNotifyDao();
        $unsubscribers_notification_dao = new UnsubscribersNotificationDAO();
        $only_status_change_dao         = new UserNotificationOnlyStatusChangeDAO();
        $user_preferences_dao           = new UserPreferencesDao();
        $notification_list_builder      = new NotificationListBuilder(
            new UGroupDao(),
            new CollectionOfUserInvolvedInNotificationPresenterBuilder(
                $user_to_notify_dao,
                $unsubscribers_notification_dao,
                $user_manager
            ),
            new CollectionOfUgroupToBeNotifiedPresenterBuilder($ugroup_to_notify_dao)
        );
        return new Tracker_NotificationsManager(
            $tracker,
            $notification_list_builder,
            $user_to_notify_dao,
            $ugroup_to_notify_dao,
            new UserNotificationSettingsDAO(),
            new GlobalNotificationsAddressesBuilder(),
            $user_manager,
            new UGroupManager(),
            new GlobalNotificationSubscribersFilter($unsubscribers_notification_dao),
            new NotificationLevelExtractor(),
            new \TrackerDao(),
            new \ProjectHistoryDao(),
            new NotificationsForceUsageUpdater(
                new RecipientsManager(
                    \Tracker_FormElementFactory::instance(),
                    $user_manager,
                    $unsubscribers_notification_dao,
                    new UserNotificationSettingsRetriever(
                        new \Tracker_GlobalNotificationDao(),
                        $unsubscribers_notification_dao,
                        $only_status_change_dao,
                        new InvolvedNotificationDao()
                    ),
                    $only_status_change_dao,
                    new NotificationOnAllUpdatesRetriever($user_preferences_dao),
                    new NotificationOnOwnActionRetriever($user_preferences_dao)
                ),
                new UserNotificationSettingsDAO()
            )
        );
    }

    /**
     * @return \CSRFSynchronizerToken
     */
    private function getCSRFToken(Tracker $tracker)
    {
        return new \CSRFSynchronizerToken($this->getURL($tracker));
    }

    private function getURL(Tracker $tracker)
    {
        return TRACKER_BASE_URL . '/notifications/' . urlencode($tracker->getId()) . '/';
    }
}
