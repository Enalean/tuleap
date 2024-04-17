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
use Tuleap\DB\DataAccessObject;

final class TrackersPermissionsDao extends DataAccessObject implements SearchUserGroupsPermissionOnFields
{
    public function searchUserGroupsPermissionOnFields(array $user_groups_id, array $fields_id, string $permission): array
    {
        $ugroups_statement = EasyStatement::open()->in('permissions.ugroup_id IN (?*)', $user_groups_id);
        $fields_statement  = EasyStatement::open()->in('permissions.object_id IN (?*)', $fields_id);

        $sql = <<<SQL
        SELECT object_id AS field_id
        FROM permissions
        WHERE $ugroups_statement AND $fields_statement AND permissions.permission_type = ?
        SQL;

        $results = $this->getDB()->safeQuery($sql, [...$user_groups_id, ...$fields_id, $permission]);
        assert(is_array($results));
        return array_map(static fn(array $row) => (int) $row['field_id'], $results);
    }
}
