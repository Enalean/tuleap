<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

use DataAccessObject;
use PFUser;

class UsersToNotifyDao extends DataAccessObject
{
    public function searchUsersByNotificationId($notification_id)
    {
        $notification_id   = $this->da->escapeInt($notification_id);
        $status_active     = $this->da->quoteSmart(PFUser::STATUS_ACTIVE);
        $status_restricted = $this->da->quoteSmart(PFUser::STATUS_RESTRICTED);

        $sql = "SELECT user.*
                FROM tracker_global_notification_users AS notification
                    INNER JOIN user
                    ON (
                        user.user_id = notification.user_id
                        AND notification.notification_id = $notification_id
                        AND user.status IN ($status_active, $status_restricted)
                    )";

        return $this->retrieve($sql);
    }
}
