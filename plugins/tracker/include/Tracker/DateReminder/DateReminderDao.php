<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\DateReminder;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;
use Tuleap\Project\Duplication\DuplicationUserGroupMapping;

class DateReminderDao extends DataAccessObject
{
    /**
     * @psalm-return list<array{reminder_id: int, tracker_id: int, field_id: int, ugroups: string, notification_type: int, distance: int, status: int}>
     */
    public function getAllDateReminders(int $tracker_id): array
    {
        $sql = 'SELECT tracker_reminder.*
                FROM tracker_reminder
                JOIN tracker_field ON tracker_reminder.field_id = tracker_field.id
                WHERE tracker_reminder.tracker_id = ? AND tracker_field.use_it = 1
                ORDER BY reminder_id';

        return $this->getDB()->run(
            $sql,
            $tracker_id
        );
    }

    /**
     * @psalm-return list<array{reminder_id: int, tracker_id: int, field_id: int, ugroups: string, notification_type: int, distance: int, status: int}>
     */
    public function getActiveDateReminders(int $tracker_id): array
    {
        $sql = 'SELECT tracker_reminder.*
                FROM tracker_reminder
                JOIN tracker_field ON tracker_reminder.field_id = tracker_field.id
                WHERE tracker_reminder.tracker_id = ? AND tracker_field.use_it = 1 AND tracker_reminder.status = 1
                ORDER BY reminder_id';

        return $this->getDB()->run(
            $sql,
            $tracker_id
        );
    }

    public function addDateReminder(
        int $tracker_id,
        int $field_id,
        string $ugroups,
        array $roles,
        int $notification_type = 0,
        int $distance = 0,
        bool $notify_closed_artifacts = true,
    ): int {
        return $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($tracker_id, $field_id, $ugroups, $notification_type, $distance, $roles, $notify_closed_artifacts): int {
                $reminder_id = (int) $db->insertReturnId(
                    'tracker_reminder',
                    [
                        'tracker_id' => $tracker_id,
                        'field_id' => $field_id,
                        'ugroups' => $ugroups,
                        'notification_type' => $notification_type,
                        'distance' => $distance,
                        'notify_closed_artifacts' => $notify_closed_artifacts,
                    ]
                );
                if ($reminder_id && ! empty($roles)) {
                    $this->insertDateReminderRoles($reminder_id, $roles);
                }
                return $reminder_id;
            }
        );
    }

    public function updateDateReminder(
        int $reminder_id,
        string $ugroups,
        array $roles,
        int $notification_type = 0,
        int $distance = 0,
        int $status = 1,
        bool $notify_closed_artifacts = true,
    ): bool {
        return $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($reminder_id, $ugroups, $notification_type, $distance, $roles, $status, $notify_closed_artifacts): bool {
                $db->update(
                    'tracker_reminder',
                    [
                        'ugroups' => $ugroups,
                        'notification_type' => $notification_type,
                        'distance' => $distance,
                        'status' => $status,
                        'notify_closed_artifacts' => $notify_closed_artifacts,
                    ],
                    ['reminder_id' => $reminder_id],
                );
                $db->delete(
                    'tracker_reminder_notified_roles',
                    ['reminder_id' => $reminder_id],
                );
                if (! empty($roles)) {
                    $this->insertDateReminderRoles($reminder_id, $roles);
                }
                return true;
            }
        );
    }

    private function insertDateReminderRoles(int $reminder_id, array $roles): void
    {
        $data_to_insert = [];

        foreach ($roles as $role) {
            $data_to_insert[] = ['reminder_id' => $reminder_id, 'role_id' => $role];
        }

        $this->getDB()->insertMany(
            'tracker_reminder_notified_roles',
            $data_to_insert,
        );
    }

    /**
     * @psalm-return null|array{reminder_id: int, tracker_id: int, field_id: int, ugroups: string, notification_type: int, distance: int, status: int}
     */
    public function searchById(int $reminder_id): ?array
    {
        $sql = 'SELECT *
            FROM tracker_reminder
            JOIN tracker_field ON tracker_reminder.field_id = tracker_field.id
            WHERE reminder_id = ? AND tracker_field.use_it = 1';

        return $this->getDB()->row($sql, $reminder_id);
    }

    public function deleteReminder(int $reminder_id): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($reminder_id): void {
                $db->delete(
                    'tracker_reminder',
                    ['reminder_id' => $reminder_id],
                );
                $db->delete(
                    'tracker_reminder_notified_roles',
                    ['reminder_id' => $reminder_id],
                );
            }
        );
    }

    /**
     * @return int[]
     */
    public function getRolesByReminderId(int $reminder_id): array
    {
        $sql = 'SELECT role_id
            FROM tracker_reminder_notified_roles
            WHERE reminder_id = ?';

        return $this->getDB()->column($sql, [$reminder_id]);
    }

    public function doesARemindersAlreadyExist(int $tracker_id, int $field_id, int $notification_type, int $distance): bool
    {
        $sql = 'SELECT NULL
                FROM tracker_reminder
                JOIN tracker_field ON tracker_reminder.field_id = tracker_field.id
                WHERE tracker_reminder.tracker_id = ?
                  AND field_id                    = ?
                  AND notification_type           = ?
                  AND distance                    = ?
                  AND tracker_reminder.status     = 1
                  AND tracker_field.use_it        = 1';

        $rows = $this->getDB()->run($sql, $tracker_id, $field_id, $notification_type, $distance);

        return count($rows) > 0;
    }

    public function doesAnotherRemindersAlreadyExist(int $tracker_id, int $field_id, int $notification_type, int $distance, int $reminder_id): bool
    {
        $sql = 'SELECT NULL
                FROM tracker_reminder
                JOIN tracker_field ON tracker_reminder.field_id = tracker_field.id
                WHERE tracker_reminder.tracker_id = ?
                  AND field_id                    = ?
                  AND notification_type           = ?
                  AND distance                    = ?
                  AND tracker_reminder.status     = 1
                  AND tracker_field.use_it        = 1
                  AND reminder_id <>                ?';

        $rows = $this->getDB()->run($sql, $tracker_id, $field_id, $notification_type, $distance, $reminder_id);

        return count($rows) > 0;
    }

    /**
     * @return int[]
     */
    public function getTrackersHavingDateReminders(): array
    {
        $sql = "SELECT DISTINCT(tracker_reminder.tracker_id)
                FROM tracker_reminder
                JOIN tracker_field ON tracker_reminder.field_id = tracker_field.id
                JOIN tracker ON (tracker.id = tracker_reminder.tracker_id)
                JOIN `groups` ON (`groups`.group_id = tracker.group_id)
                WHERE tracker_reminder.status = 1 AND tracker.deletion_date IS NULL AND `groups`.status = 'A'
                ORDER BY reminder_id";

        return $this->getDB()->column($sql, []);
    }

    public function duplicate(int $template_tracker_id, int $new_tracker_id, DuplicationUserGroupMapping $duplication_user_group_mapping, array $fields_mapping): void
    {
        if (count($fields_mapping) === 0) {
            return;
        }

        $fields_hash = [];
        foreach ($fields_mapping as $field_mapping) {
            $fields_hash[$field_mapping['from']] = $field_mapping['to'];
        }
        $ugroups_mapping    = $duplication_user_group_mapping->ugroup_mapping;
        $ugroups_mapping[3] = 3;
        $ugroups_mapping[4] = 4;

        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($template_tracker_id, $new_tracker_id, $ugroups_mapping, $fields_hash) {
            foreach ($this->getDB()->run('SELECT * FROM tracker_reminder WHERE tracker_id = ?', $template_tracker_id) as $row) {
                if (! isset($fields_hash[$row['field_id']])) {
                    continue;
                }

                $new_project_ugroups = [];
                foreach (explode(',', $row['ugroups']) as $template_ugroup) {
                    $template_ugroup = (int) trim($template_ugroup);
                    if (isset($ugroups_mapping[$template_ugroup])) {
                        $new_project_ugroups[] = $ugroups_mapping[$template_ugroup];
                    }
                }

                $new_reminder_id = $db->insertReturnId(
                    'tracker_reminder',
                    [
                        'tracker_id' => $new_tracker_id,
                        'field_id' => $fields_hash[$row['field_id']],
                        'ugroups' => implode(',', $new_project_ugroups),
                        'notification_type' => $row['notification_type'],
                        'distance' => $row['distance'],
                        'status' => $row['status'],
                        'notify_closed_artifacts' => $row['notify_closed_artifacts'],
                    ]
                );

                $sql = <<<SQL
                INSERT INTO tracker_reminder_notified_roles(reminder_id, role_id)
                SELECT ?, role_id
                FROM tracker_reminder_notified_roles
                WHERE reminder_id = ?
                SQL;
                $db->run($sql, $new_reminder_id, $row['reminder_id']);
            }
        });
    }
}
