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

use Tracker;
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
use UGroupDao;
use UGroupManager;
use UserManager;

trait NotificationsAdminSettingsControllerCommon
{
    /**
     * @return \Tracker
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
    private function getDateReminderManager(\Tracker $tracker)
    {
        return new Tracker_DateReminderManager($tracker);
    }

    /**
     * @return Tracker_NotificationsManager
     */
    private function getNotificationsManager(UserManager $user_manager, \Tracker $tracker)
    {
        $user_to_notify_dao             = new UsersToNotifyDao();
        $ugroup_to_notify_dao           = new UgroupsToNotifyDao();
        $unsubscribers_notification_dao = new UnsubscribersNotificationDAO;
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
                    new UnsubscribersNotificationDAO,
                    new UserNotificationSettingsRetriever(
                        new \Tracker_GlobalNotificationDao(),
                        new UnsubscribersNotificationDAO(),
                        new UserNotificationOnlyStatusChangeDAO(),
                        new InvolvedNotificationDao()
                    ),
                    new UserNotificationOnlyStatusChangeDAO()
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
