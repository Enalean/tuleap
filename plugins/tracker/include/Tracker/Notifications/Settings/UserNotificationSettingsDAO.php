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

namespace Tuleap\Tracker\Notifications\Settings;

use Tuleap\DB\DataAccessObject;

class UserNotificationSettingsDAO extends DataAccessObject
{
    public function enableNoNotificationAtAllMode($user_id, $tracker_id)
    {
        $this->getDB()->beginTransaction();
        try {
            $this->deleteUserNotificationSettings($user_id, $tracker_id);
            $this->getDB()->insert(
                'tracker_global_notification_unsubscribers',
                ['user_id' => $user_id, 'tracker_id' => $tracker_id]
            );
            $this->getDB()->commit();
        } catch (\Exception $ex) {
            $this->getDB()->rollBack();
            throw $ex;
        }
    }

    public function enableNoGlobalNotificationMode($user_id, $tracker_id)
    {
        $this->getDB()->beginTransaction();
        try {
            $this->deleteUserNotificationSettings($user_id, $tracker_id);
            $this->getDB()->insert(
                'plugin_tracker_involved_notification_subscribers',
                ['user_id' => $user_id, 'tracker_id' => $tracker_id]
            );
            $this->getDB()->commit();
        } catch (\Exception $ex) {
            $this->getDB()->rollBack();
            throw $ex;
        }
    }

    public function enableNotifyOnStatusChangeMode($user_id, $tracker_id)
    {
        $this->getDB()->beginTransaction();
        try {
            $this->deleteUserNotificationSettings($user_id, $tracker_id);
            $this->getDB()->insert(
                'tracker_only_status_change_notification_subscribers',
                ['user_id' => $user_id, 'tracker_id' => $tracker_id]
            );
            $this->getDB()->commit();
        } catch (\Exception $ex) {
            $this->getDB()->rollBack();
            throw $ex;
        }
    }

    public function enableNotifyOnArtifactCreationMode($user_id, $tracker_id)
    {
        $this->enableGlobalNotification($user_id, $tracker_id, false);
    }

    public function enableNotifyOnEveryChangeMode($user_id, $tracker_id)
    {
        $this->enableGlobalNotification($user_id, $tracker_id, true);
    }

    private function enableGlobalNotification($user_id, $tracker_id, $all_updates)
    {
        $this->getDB()->beginTransaction();
        try {
            $check_permission = $this->getCurrentGlobalNotificationCheckPermissionSetting($user_id, $tracker_id);
            $this->deleteUserNotificationSettings($user_id, $tracker_id);
            $this->getDB()->insert(
                'tracker_global_notification',
                ['tracker_id' => $tracker_id, 'all_updates' => $all_updates, 'check_permissions' => $check_permission]
            );
            $notification_id = $this->getDB()->lastInsertId();
            $this->getDB()->insert(
                'tracker_global_notification_users',
                ['notification_id' => $notification_id, 'user_id' => $user_id]
            );

            $this->getDB()->commit();
        } catch (\Exception $ex) {
            $this->getDB()->rollBack();
            throw $ex;
        }
    }

    private function deleteUserNotificationSettings($user_id, $tracker_id)
    {
        $this->deleteUserFromUnsubscribers($user_id, $tracker_id);
        $this->deleteUserFromGlobalNotification($user_id, $tracker_id);
        $this->deleteUserFromStatusUpdateOnlyNotification($user_id, $tracker_id);
        $this->deleteUserFromInvolvedNotification($user_id, $tracker_id);
        $this->cleanUpEmptyGlobalNotification($tracker_id);
    }

    private function deleteUserFromInvolvedNotification($user_id, $tracker_id)
    {
        $sql = 'DELETE FROM plugin_tracker_involved_notification_subscribers WHERE user_id = ? AND tracker_id = ?';
        $this->getDB()->run($sql, $user_id, $tracker_id);
    }

    private function deleteUserFromUnsubscribers($user_id, $tracker_id)
    {
        $sql = 'DELETE FROM tracker_global_notification_unsubscribers WHERE user_id = ? AND tracker_id = ?';
        $this->getDB()->run($sql, $user_id, $tracker_id);
    }

    private function deleteUserFromStatusUpdateOnlyNotification($user_id, $tracker_id)
    {
        $sql = 'DELETE FROM tracker_only_status_change_notification_subscribers WHERE user_id = ? AND tracker_id = ?';
        $this->getDB()->run($sql, $user_id, $tracker_id);
    }

    private function deleteUserFromGlobalNotification($user_id, $tracker_id)
    {
        $sql = 'DELETE tracker_global_notification_users.*
                FROM tracker_global_notification_users
                JOIN tracker_global_notification ON (tracker_global_notification.id = tracker_global_notification_users.notification_id)
                WHERE user_id = ? AND tracker_id = ?';
        $this->getDB()->run($sql, $user_id, $tracker_id);
    }

    private function cleanUpEmptyGlobalNotification($tracker_id)
    {
        $sql = 'DELETE tracker_global_notification.*
                FROM tracker_global_notification
                LEFT OUTER JOIN tracker_global_notification_users ON (tracker_global_notification.id = tracker_global_notification_users.notification_id)
                LEFT OUTER JOIN tracker_global_notification_ugroups ON (tracker_global_notification.id = tracker_global_notification_ugroups.notification_id)
                WHERE tracker_global_notification_users.notification_id IS NULL AND tracker_global_notification_ugroups.notification_id IS NULL
                  AND tracker_global_notification.tracker_id = ?';
        $this->getDB()->run($sql, $tracker_id);
    }

    private function getCurrentGlobalNotificationCheckPermissionSetting($user_id, $tracker_id)
    {
        $sql = 'SELECT
                  COALESCE(MAX(check_permissions), TRUE) AS check_permissions
                FROM (
                       SELECT
                         check_permissions
                       FROM tracker_global_notification
                         JOIN tracker_global_notification_users
                           ON (tracker_global_notification.id = tracker_global_notification_users.notification_id)
                       WHERE user_id = ? AND tracker_id = ?
                       UNION ALL
                       SELECT
                         check_permissions
                       FROM tracker_global_notification
                         JOIN tracker_global_notification_ugroups
                           ON (tracker_global_notification.id = tracker_global_notification_ugroups.notification_id)
                         JOIN ugroup_user ON (tracker_global_notification_ugroups.ugroup_id = ugroup_user.ugroup_id)
                       WHERE ugroup_user.user_id = ? AND tracker_global_notification.tracker_id = ?
                ) AS global_notification';
        return $this->getDB()->single($sql, [$user_id, $tracker_id, $user_id, $tracker_id]);
    }
}
