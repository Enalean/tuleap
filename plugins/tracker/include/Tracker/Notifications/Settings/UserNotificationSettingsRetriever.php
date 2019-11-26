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

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications\Settings;

use Tracker_GlobalNotificationDao;
use Tuleap\Tracker\Notifications\GlobalNotification;
use Tuleap\Tracker\Notifications\InvolvedNotificationDao;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;
use Tuleap\Tracker\Notifications\UserNotificationOnlyStatusChangeDAO;

class UserNotificationSettingsRetriever
{
    /**
     * @var Tracker_GlobalNotificationDao
     */
    private $tracker_global_notification_dao;
    /**
     * @var UnsubscribersNotificationDAO
     */
    private $unsubscribers_notification_dao;
    /**
     * @var UserNotificationOnlyStatusChangeDAO
     */
    private $only_status_change_dao;

    /**
     * @var InvolvedNotificationDao
     */
    private $involved_notification_dao;

    public function __construct(
        Tracker_GlobalNotificationDao $tracker_global_notification_dao,
        UnsubscribersNotificationDAO $unsubscribers_notification_dao,
        UserNotificationOnlyStatusChangeDAO $only_status_change_dao,
        InvolvedNotificationDao $involved_notification_dao
    ) {
        $this->tracker_global_notification_dao = $tracker_global_notification_dao;
        $this->unsubscribers_notification_dao  = $unsubscribers_notification_dao;
        $this->only_status_change_dao          = $only_status_change_dao;
        $this->involved_notification_dao       = $involved_notification_dao;
    }

    /**
     * @return UserNotificationSettings
     */
    public function getUserNotificationSettings(\PFUser $user, \Tracker $tracker)
    {
        $has_unsubscribed = $this->unsubscribers_notification_dao->doesUserIDHaveUnsubscribedFromTrackerNotifications(
            $user->getId(),
            $tracker->getId()
        );

        $is_only_on_status_update = $this->only_status_change_dao->doesUserIdHaveSubscribeOnlyForStatusChangeNotification(
            $user->getId(),
            $tracker->getId()
        );

        $is_involved = $this->involved_notification_dao->doesUserIdHaveSubscribeForInvolvedNotification(
            (int) $user->getId(),
            (int) $tracker->getId()
        );

        $global_notifications = $this->getGlobalNotifications($user, $tracker);

        return new UserNotificationSettings(
            $has_unsubscribed,
            $is_only_on_status_update,
            $is_involved,
            $tracker->getNotificationsLevel(),
            ...$global_notifications
        );
    }

    /**
     * @return GlobalNotification[]
     */
    private function getGlobalNotifications(\PFUser $user, \Tracker $tracker)
    {
        $global_notification_rows = $this->tracker_global_notification_dao->searchByUserIdAndTrackerId(
            $user->getId(),
            $tracker->getId()
        );

        $global_notifications = [];
        foreach ($global_notification_rows as $global_notification_row) {
            $global_notifications[] = new GlobalNotification((bool) $global_notification_row['all_updates']);
        }

        return $global_notifications;
    }
}
