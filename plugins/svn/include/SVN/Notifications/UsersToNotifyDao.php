<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Notifications;

use PFUser;

class UsersToNotifyDao extends \DataAccessObject
{
    public function searchUsersByNotificationId($notification_id)
    {
        $notification_id   = $this->da->escapeInt($notification_id);
        $status_active     = $this->da->quoteSmart(PFUser::STATUS_ACTIVE);
        $status_restricted = $this->da->quoteSmart(PFUser::STATUS_RESTRICTED);

        $sql = "SELECT user.*
                FROM plugin_svn_notification_users AS notification
                    INNER JOIN user
                    ON (
                        user.user_id = notification.user_id
                        AND notification.notification_id = $notification_id
                        AND user.status IN ($status_active, $status_restricted)
                    )";

        return $this->retrieve($sql);
    }

    public function deleteUserFromAllNotificationsInProject($user_id, $project_id)
    {
        $user_id    = $this->da->escapeInt($user_id);
        $project_id = $this->da->escapeInt($project_id);

        $sql = "DELETE users.*
                FROM plugin_svn_notification_users AS users
                    INNER JOIN plugin_svn_notification AS notif ON (notif.id = users.notification_id AND users.user_id = $user_id)
                    INNER JOIN plugin_svn_repositories AS repo ON (repo.id = notif.repository_id AND repo.project_id = $project_id)";

        return $this->update($sql);
    }

    public function deleteByNotificationId($notification_id)
    {
        $notification_id = $this->da->escapeInt($notification_id);

        $sql = "DELETE
                FROM plugin_svn_notification_users
                WHERE notification_id = $notification_id";

        return $this->update($sql);
    }

    public function insert($notification_id, $user_id)
    {
        $notification_id = $this->da->escapeInt($notification_id);
        $user_id         = $this->da->escapeInt($user_id);

        $sql = "REPLACE INTO plugin_svn_notification_users(notification_id, user_id)
                VALUES ($notification_id, $user_id)";

        return $this->update($sql);
    }
}
