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
use Tracker_GlobalNotification;
use Tuleap\Tracker\Tracker;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use UserHelper;

class CollectionOfUserInvolvedInNotificationPresenterBuilder
{
    public function __construct(
        private readonly UsersToNotifyDao $users_to_notify_dao,
        private readonly UnsubscribersNotificationDAO $unsubscribers_notification_dao,
        private readonly \UserManager $user_manager,
        private readonly UserHelper $user_helper,
        private readonly ProvideUserAvatarUrl $avatar_url_provider,
    ) {
    }

    public function getCollectionOfUserToBeNotifiedPresenter(Tracker_GlobalNotification $notification)
    {
        $user_rows = $this->users_to_notify_dao->searchUsersByNotificationId($notification->getId());
        return $this->getCollectionOfUserPresenters($user_rows);
    }

    /**
     * @return UserInvolvedInTrackerNotificationPresenter[]
     */
    public function getCollectionOfNotificationUnsubscribersPresenter(Tracker $tracker): array
    {
        $user_rows = $this->unsubscribers_notification_dao->searchUsersUnsubcribedFromNotificationByTrackerID($tracker->getId());
        return $this->getCollectionOfUserPresenters(new \ArrayIterator($user_rows));
    }

    /**
     * @return UserInvolvedInTrackerNotificationPresenter[]
     */
    private function getCollectionOfUserPresenters(Iterator $user_rows): array
    {
        $presenters = [];
        foreach ($user_rows as $row) {
            $user = $this->user_manager->getUserById($row['user_id']);
            if (! $user) {
                continue;
            }
            $presenters[] = UserInvolvedInTrackerNotificationPresenter::fromPFUser(
                $user,
                $this->user_helper,
                $this->avatar_url_provider,
            );
        }
        $this->sortUsersAlphabetically($presenters);

        return $presenters;
    }

    /**
     * @param UserInvolvedInTrackerNotificationPresenter[] $presenters
     *
     */
    private function sortUsersAlphabetically(array &$presenters): void
    {
        usort($presenters, function (UserInvolvedInTrackerNotificationPresenter $a, UserInvolvedInTrackerNotificationPresenter $b) {
            return strnatcasecmp($a->display_name, $b->display_name);
        });
    }
}
