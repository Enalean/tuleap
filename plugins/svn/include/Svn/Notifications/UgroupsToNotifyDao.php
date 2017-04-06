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

namespace Tuleap\Svn\Notifications;

use DataAccessObject;
use ProjectUGroup;

class UgroupsToNotifyDao extends DataAccessObject
{
    public function searchUgroupsByNotificationId($notification_id)
    {
        $notification_id = $this->da->escapeInt($notification_id);

        $sql = "SELECT ugroup.*
                FROM plugin_svn_notification_ugroups AS notification
                    INNER JOIN ugroup
                    ON (
                        ugroup.ugroup_id = notification.ugroup_id
                        AND notification.notification_id = $notification_id
                    )";

        return $this->retrieve($sql);
    }

    public function disableAnonymousRegisteredAuthenticated($project_id)
    {
        return $this->updateNotificationUgroups(
            $project_id,
            array(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::PROJECT_MEMBERS
        );
    }

    public function disableAuthenticated($project_id)
    {
        return $this->updateNotificationUgroups(
            $project_id,
            array(ProjectUGroup::AUTHENTICATED),
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
         * plugin_svn_notification_ugroups table for the same
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
        $sql = "UPDATE IGNORE plugin_svn_notification_ugroups AS u
                INNER JOIN plugin_svn_notification AS n ON (n.id = u.notification_id)
                INNER JOIN plugin_svn_repositories AS r ON (r.id = n.repository_id)
                SET u.ugroup_id = $new_ugroup_id
                WHERE r.project_id = $project_id
                  AND u.ugroup_id IN ($old_ugroup_ids)";
        return $sql;
    }

    private function getQueryToRemoveRemainingUgroupsByProjectId($project_id, $old_ugroup_ids)
    {
        $sql = "DELETE u.*
                    FROM plugin_svn_notification_ugroups AS u
                    INNER JOIN plugin_svn_notification AS n ON (n.id = u.notification_id)
                    INNER JOIN plugin_svn_repositories AS r ON (r.id = n.repository_id)
                    WHERE r.project_id = $project_id
                      AND u.ugroup_id IN ($old_ugroup_ids)";
        return $sql;
    }

    private function getQueryToReplaceUgroup($old_ugroup_id, $new_ugroup_id)
    {
        $sql = "UPDATE IGNORE plugin_svn_notification_ugroups
                SET ugroup_id = $new_ugroup_id
                WHERE ugroup_id = $old_ugroup_id";
        return $sql;
    }

    private function getQueryToRemoveRemainingUgroup($old_ugroup_id)
    {
        $sql = "DELETE notification.*
                FROM plugin_svn_notification_ugroups AS notification
                WHERE notification.ugroup_id = $old_ugroup_id";
        return $sql;
    }
}
