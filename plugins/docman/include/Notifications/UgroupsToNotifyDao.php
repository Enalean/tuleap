<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Docman\Notifications;

use DataAccessObject;
use ProjectUGroup;

class UgroupsToNotifyDao extends DataAccessObject
{
    public function searchUgroupsByItemIdAndType($item_id, $type)
    {
        $item_id = $this->da->escapeInt($item_id);
        $type    = $this->da->quoteSmart($type);

        $sql = "SELECT ugroup.*
            FROM plugin_docman_notification_ugroups AS notification
            INNER JOIN ugroup
            ON (
                ugroup.ugroup_id = notification.ugroup_id
                AND notification.item_id = $item_id
                AND notification.type = $type
            )";

        return $this->retrieve($sql);
    }

    public function deleteByUgroupId($project_id, $ugroup_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $ugroup_id  = $this->da->escapeInt($ugroup_id);

        $sql = "DELETE notification.*
            FROM plugin_docman_item AS item
            INNER JOIN plugin_docman_notification_ugroups AS notification
            ON (
                item.item_id = notification.item_id
                AND item.group_id = $project_id
                AND notification.ugroup_id = $ugroup_id
            )
        ";

        return $this->update($sql);
    }

    public function deleteByItemId($item_id)
    {
        $item_id = $this->da->escapeInt($item_id);

        $sql = "DELETE FROM plugin_docman_notification_ugroups
                WHERE item_id = $item_id";

        return $this->update($sql);
    }

    public function create($item_id, $ugroup_id, $type)
    {
        $item_id   = $this->da->escapeInt($item_id);
        $type      = $this->da->quoteSmart($type);
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "INSERT INTO plugin_docman_notification_ugroups (item_id, ugroup_id, type)
                VALUES ($item_id, $ugroup_id, $type)";

        return $this->update($sql);
    }

    public function delete($item_id, $ugroup_id, $type)
    {
        $item_id   = $this->da->escapeInt($item_id);
        $type      = $this->da->quoteSmart($type);
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "DELETE
            FROM plugin_docman_notification_ugroups
            WHERE item_id = $item_id
            AND ugroup_id = $ugroup_id
            AND type = $type
            ";

        return $this->update($sql);
    }

    public function searchUGroupByUGroupIdAndItemIdAndType($item_id, $ugroup_id, $type)
    {
        $item_id   = $this->da->escapeInt($item_id);
        $type      = $this->da->quoteSmart($type);
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "SELECT *
            FROM plugin_docman_notification_ugroups
            WHERE item_id = $item_id
            AND ugroup_id = $ugroup_id
            AND type = $type
            ";

        return $this->retrieve($sql);
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
        return $this->updateAllNotifications(
            ProjectUGroup::ANONYMOUS,
            ProjectUGroup::REGISTERED
        );
    }

    public function updateAllAuthenticatedAccessToRegistered()
    {
        return $this->updateAllNotifications(
            ProjectUGroup::AUTHENTICATED,
            ProjectUGroup::REGISTERED
        );
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

    private function getQueryToReplaceUgroupsByProjectId($project_id, $old_ugroup_ids, $new_ugroup_id)
    {
        $sql = "UPDATE IGNORE plugin_docman_notification_ugroups AS notification
                INNER JOIN plugin_docman_item AS item
                    ON item.item_id = notification.item_id
            SET notification.ugroup_id = $new_ugroup_id
            WHERE item.group_id = $project_id
              AND notification.ugroup_id IN ($old_ugroup_ids)";
        return $sql;
    }

    private function getQueryToRemoveRemainingUgroupsByProjectId($project_id, $old_ugroup_ids)
    {
        $sql = "DELETE notification.*
                FROM plugin_docman_item AS item
                INNER JOIN plugin_docman_notification_ugroups AS notification
                    ON (
                        item.item_id = notification.item_id
                        AND item.group_id = $project_id
                        AND notification.ugroup_id IN ($old_ugroup_ids)
                    )";
        return $sql;
    }

    private function updateAllNotifications($old_ugroup_id, $new_ugroup_id)
    {
        $old_ugroup_id = $this->da->escapeInt($old_ugroup_id);
        $new_ugroup_id = $this->da->escapeInt($new_ugroup_id);

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

    private function getQueryToReplaceUgroup($old_ugroup_id, $new_ugroup_id)
    {
        $sql = "UPDATE IGNORE plugin_docman_notification_ugroups
            SET ugroup_id = $new_ugroup_id
            WHERE ugroup_id = $old_ugroup_id";
        return $sql;
    }

    private function getQueryToRemoveRemainingUgroup($old_ugroup_id)
    {
        $sql = "DELETE notification.*
            FROM plugin_docman_notification_ugroups AS notification
            WHERE notification.ugroup_id = $old_ugroup_id";
        return $sql;
    }
}
