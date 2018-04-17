<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Tuleap\DB\DataAccessObject;

class UnsubscribersNotificationDAO extends DataAccessObject
{
    public function searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID($tracker_id, $artifact_id)
    {
        $sql = 'SELECT user_id FROM tracker_global_notification_unsubscribers WHERE tracker_id = ?
                UNION
                SELECT user_id FROM tracker_artifact_unsubscribe WHERE artifact_id = ?';

        return $this->getDB()->column($sql, [$tracker_id, $artifact_id]);
    }

    public function doesUserIDHaveUnsubscribedFromTrackerNotifications($user_id, $tracker_id)
    {
        $sql = 'SELECT COUNT(*) FROM tracker_global_notification_unsubscribers WHERE user_id = ? AND tracker_id = ?';

        return $this->getDB()->exists($sql, $user_id, $tracker_id);
    }

    public function searchUsersUnsubcribedFromNotificationByTrackerID($tracker_id)
    {
        $sql = 'SELECT user.*
                FROM tracker_global_notification_unsubscribers
                JOIN user ON (tracker_global_notification_unsubscribers.user_id = user.user_id)
                WHERE tracker_id = ?';

        return $this->getDB()->run($sql, $tracker_id);
    }
}
