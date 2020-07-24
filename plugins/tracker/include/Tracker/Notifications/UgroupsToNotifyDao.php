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
use ProjectUGroup;

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

    public function insert($notification_id, $ugroup_id)
    {
        $notification_id = $this->da->escapeInt($notification_id);
        $ugroup_id       = $this->da->escapeInt($ugroup_id);

        $sql = "REPLACE INTO tracker_global_notification_ugroups(notification_id, ugroup_id)
                VALUES ($notification_id, $ugroup_id)";

        return $this->update($sql);
    }

    public function disableAnonymousRegisteredAuthenticated($project_id)
    {
        return $this->updateNotificationUgroups(
            $project_id,
            [ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED],
            ProjectUGroup::PROJECT_MEMBERS
        );
    }

    public function disableAuthenticated($project_id)
    {
        return $this->updateNotificationUgroups(
            $project_id,
            [ProjectUGroup::AUTHENTICATED],
            ProjectUGroup::REGISTERED
        );
    }

    public function updateAllAnonymousAccessToRegistered()
    {
        return $this->updateAllPermissions(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED);
    }

    public function updateAllAuthenticatedAccessToRegistered()
    {
        return $this->updateAllPermissions(ProjectUGroup::AUTHENTICATED, ProjectUGroup::REGISTERED);
    }

    private function updateNotificationUgroups($project_id, array $old_ugroup_ids, $new_ugroup_id)
    {
        $project_id     = $this->da->escapeInt($project_id);
        $new_ugroup_id  = $this->da->escapeInt($new_ugroup_id);
        $old_ugroup_ids = $this->da->escapeIntImplode($old_ugroup_ids);

        $this->startTransaction();

        $sql = $this->getQueryToReplaceUgroupsByProjectId($project_id, $old_ugroup_ids, $new_ugroup_id);

        if (! $this->update($sql)) {
            $this->rollBack();
            return false;
        }

        /**
         * Ugroups to be removed if new_ugroup_id already exists in
         * tracker_global_notification_ugroups table for the same
         * notification_id
         */
        $sql = $this->getQueryToRemoveRemainingUgroupsByProjectId($project_id, $old_ugroup_ids);

        if (! $this->update($sql)) {
            $this->rollBack();
            return false;
        }

        $this->commit();
        return true;
    }

    private function updateAllPermissions($old_ugroup_id, $new_ugroup_id)
    {
        $new_ugroup_id = $this->da->escapeInt($new_ugroup_id);
        $old_ugroup_id = $this->da->escapeInt($old_ugroup_id);

        $this->startTransaction();

        $sql = $this->getQueryToReplaceUgroup($old_ugroup_id, $new_ugroup_id);

        if (! $this->update($sql)) {
            $this->rollBack();
            return false;
        }

        /**
         * Ugroups to be removed if new_ugroup_id already exists in
         * tracker_global_notification_ugroups table for the same
         * notification_id
         */
        $sql = $this->getQueryToRemoveRemainingUgroup($old_ugroup_id);

        if (! $this->update($sql)) {
            $this->rollBack();
            return false;
        }

        $this->commit();
        return true;
    }

    private function getQueryToReplaceUgroupsByProjectId($project_id, $old_ugroup_ids, $new_ugroup_id)
    {
        $sql = "UPDATE IGNORE tracker_global_notification_ugroups AS notification
                INNER JOIN tracker_global_notification AS global_notification
                  ON global_notification.id = notification.notification_id
                INNER JOIN tracker ON tracker.id = global_notification.tracker_id
                SET notification.ugroup_id = $new_ugroup_id
                WHERE tracker.group_id = $project_id AND notification.ugroup_id IN ($old_ugroup_ids)";
        return $sql;
    }

    private function getQueryToRemoveRemainingUgroupsByProjectId($project_id, $old_ugroup_ids)
    {
        $sql = "DELETE notification.*
                    FROM tracker_global_notification_ugroups AS notification
                    INNER JOIN tracker_global_notification AS global_notification
                     ON (global_notification.id = notification.notification_id)
                    INNER JOIN tracker
                     ON (tracker.id = global_notification.tracker_id)
                    WHERE tracker.group_id = $project_id AND notification.ugroup_id IN ($old_ugroup_ids)";
        return $sql;
    }

    private function getQueryToReplaceUgroup($old_ugroup_id, $new_ugroup_id)
    {
        $sql = "UPDATE IGNORE tracker_global_notification_ugroups
                SET ugroup_id = $new_ugroup_id
                WHERE ugroup_id = $old_ugroup_id";
        return $sql;
    }

    private function getQueryToRemoveRemainingUgroup($old_ugroup_id)
    {
        $sql = "DELETE notification.*
                FROM tracker_global_notification_ugroups AS notification
                WHERE notification.ugroup_id = $old_ugroup_id";
        return $sql;
    }
}
