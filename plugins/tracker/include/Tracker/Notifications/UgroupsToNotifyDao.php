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

class UgroupsToNotifyDao extends DataAccessObject
{
    public function searchUgroupsByNotificationId($notification_id)
    {
        $notification_id = $this->da->escapeInt($notification_id);

        $sql = "SELECT ugroup.*
                FROM tracker_global_notification_ugroups AS notification
                    INNER JOIN ugroup
                    ON (
                        ugroup.ugroup_id = notification.ugroup_id
                        AND notification.notification_id = $notification_id
                    )";

        return $this->retrieve($sql);
    }

    public function deleteByNotificationId($notification_id)
    {
        $notification_id = $this->da->escapeInt($notification_id);

        $sql = "DELETE
                FROM tracker_global_notification_ugroups
                WHERE notification_id = $notification_id";

        return $this->update($sql);
    }

    public function deleteByUgroupId($project_id, $ugroup_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $ugroup_id  = $this->da->escapeInt($ugroup_id);

        $sql = "DELETE notification.*
                FROM tracker
                    INNER JOIN tracker_global_notification AS global_notification
                    ON (
                      tracker.id = global_notification.tracker_id
                      AND tracker.group_id = $project_id
                    )
                    INNER JOIN tracker_global_notification_ugroups AS notification
                    ON (
                      global_notification.id = notification.notification_id
                      AND notification.ugroup_id = $ugroup_id
                    )";

        return $this->update($sql);
    }
}
