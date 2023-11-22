<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

namespace Tuleap\Tracker\Notifications;

use Iterator;
use Tracker;
use Tracker_GlobalNotification;
use Tuleap\Notification\UserInvolvedInNotificationPresenter;

class CollectionOfUserInvolvedInNotificationPresenterBuilder
{
    /**
     * @var UsersToNotifyDao
     */
    private $users_to_notify_dao;
    /**
     * @var UnsubscribersNotificationDAO
     */
    private $unsubscribers_notification_dao;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(
        UsersToNotifyDao $users_to_notify_dao,
        UnsubscribersNotificationDAO $unsubscribers_notification_dao,
        \UserManager $user_manager,
    ) {
        $this->users_to_notify_dao            = $users_to_notify_dao;
        $this->unsubscribers_notification_dao = $unsubscribers_notification_dao;
        $this->user_manager                   = $user_manager;
    }

    public function getCollectionOfUserToBeNotifiedPresenter(Tracker_GlobalNotification $notification)
    {
        $user_rows = $this->users_to_notify_dao->searchUsersByNotificationId($notification->getId());
        return $this->getCollectionOfUserPresenters($user_rows);
    }

    /**
     * @return UserInvolvedInNotificationPresenter[]
     */
    public function getCollectionOfNotificationUnsubscribersPresenter(Tracker $tracker)
    {
        $user_rows = $this->unsubscribers_notification_dao->searchUsersUnsubcribedFromNotificationByTrackerID($tracker->getId());
        return $this->getCollectionOfUserPresenters(new \ArrayIterator($user_rows));
    }

    /**
     * @return UserInvolvedInNotificationPresenter[]
     */
    private function getCollectionOfUserPresenters(Iterator $user_rows)
    {
        $presenters = [];
        foreach ($user_rows as $row) {
            $user = $this->user_manager->getUserById($row['user_id']);
            if (! $user) {
                continue;
            }

            $presenters[] = new UserInvolvedInNotificationPresenter(
                $row['user_id'],
                $row['user_name'],
                $row['realname'],
                $user->getAvatarUrl()
            );
        }
        $this->sortUsersAlphabetically($presenters);

        return $presenters;
    }

    private function sortUsersAlphabetically(&$presenters)
    {
        usort($presenters, function (UserInvolvedInNotificationPresenter $a, UserInvolvedInNotificationPresenter $b) {
            return strnatcasecmp($a->label, $b->label);
        });
    }
}
