<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Permission;

use ParagonIE\EasyDB\EasyStatement;
use Tracker;
use Tuleap\DB\DataAccessObject;

final class TrackersPermissionsDao extends DataAccessObject implements SearchUserGroupsPermissionOnFields, SearchUserGroupsPermissionOnTrackers
{
    public function searchUserGroupsPermissionOnFields(array $user_groups_id, array $fields_id, string $permission): array
    {
        $ugroups_statement = EasyStatement::open()->in('permissions.ugroup_id IN (?*)', $user_groups_id);
        $fields_statement  = EasyStatement::open()->in('permissions.object_id IN (?*)', $fields_id);

        $sql = <<<SQL
        SELECT DISTINCT object_id AS field_id
        FROM permissions
        WHERE $ugroups_statement AND $fields_statement AND permissions.permission_type = ?
        SQL;

        $results = $this->getDB()->safeQuery($sql, [...$user_groups_id, ...$this->castIdsToString($fields_id), $permission]);
        assert(is_array($results));
        return array_map(static fn(array $row) => (int) $row['field_id'], $results);
    }

    public function searchUserGroupsPermissionOnTrackers(array $user_groups_id, array $trackers_id): array
    {
        $ugroups_statement   = EasyStatement::open()->in('permissions.ugroup_id IN (?*)', $user_groups_id);
        $trackers_statement  = EasyStatement::open()->in('tracker.id IN (?*)', $trackers_id);
        $perm_type_statement = EasyStatement::open()->in('permissions.permission_type IN (?*)', [
            Tracker::PERMISSION_ADMIN,
            Tracker::PERMISSION_FULL,
            Tracker::PERMISSION_ASSIGNEE,
            Tracker::PERMISSION_SUBMITTER,
            Tracker::PERMISSION_SUBMITTER_ONLY,
        ]);

        $sql = <<<SQL
        SELECT DISTINCT tracker.id AS tracker_id
        FROM tracker
        INNER JOIN permissions ON (
            permissions.object_id = CAST(tracker.id AS CHAR CHARACTER SET utf8)
            AND $ugroups_statement
            AND $perm_type_statement
        )
        WHERE tracker.deletion_date IS NULL AND $trackers_statement
        SQL;

        $results = $this->getDB()->safeQuery($sql, [
            ...$user_groups_id,
            ...array_values($perm_type_statement->values()),
            ...$trackers_id,
        ]);
        assert(is_array($results));
        return array_map(static fn(array $row) => (int) $row['tracker_id'], $results);
    }

    /**
     * @param int[] $ids
     * @return string[]
     */
    private function castIdsToString(array $ids): array
    {
        return array_map(static fn(int $id) => (string) $id, $ids);
    }
}
