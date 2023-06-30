<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 */

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use Tuleap\DB\DataAccessObject;

class BindUgroupsValueDao extends DataAccessObject implements SearchUserGroupsValuesById, SearchUserGroupsValuesByFieldIdAndUserGroupId
{
    /**
     * @psalm-return array{id: int, field_id: int, ugroup_id: int, is_hidden: int} | null
     */
    public function searchById(int $id): ?array
    {
        $sql = "
            SELECT *
            FROM tracker_field_list_bind_ugroups_value
            WHERE id = ?
        ";

        return $this->getDB()->row($sql, $id);
    }

    /**
     * @psalm-return array{id: int, field_id: int, ugroup_id: int, is_hidden: int}[]
     */
    public function searchByFieldId(int $field_id): array
    {
        $sql = "
            SELECT *
            FROM tracker_field_list_bind_ugroups_value
            WHERE field_id = ?
            ORDER BY id
        ";

        return $this->getDB()->safeQuery($sql, [$field_id]);
    }

    /**
     * @psalm-return array{id: int, field_id: int, ugroup_id: int, is_hidden: int} | null
     */
    public function searchByFieldIdAndGroupId(int $field_id, int $ugroup_id): ?array
    {
        $sql = "
            SELECT *
            FROM tracker_field_list_bind_ugroups_value
            WHERE field_id = ? AND ugroup_id = ?
            ORDER BY id
        ";

        return $this->getDB()->row($sql, $field_id, $ugroup_id);
    }

    public function duplicate(int $from_value_id, int $to_field_id): int
    {
        $sql = "
            REPLACE INTO tracker_field_list_bind_ugroups_value (field_id, ugroup_id, is_hidden)
            SELECT ?, u1.ugroup_id, v.is_hidden
            FROM ugroup u1
            INNER JOIN tracker t ON (
                t.group_id = u1.group_id AND u1.ugroup_id > 100
                OR
                u1.ugroup_id <= 100
            )
            INNER JOIN tracker_field AS f ON (t.id = f.tracker_id)
            INNER JOIN ugroup u2 ON (u1.name = u2.name)
            INNER JOIN tracker_field_list_bind_ugroups_value v ON (v.ugroup_id = u2.ugroup_id)
            WHERE f.id = ?
              AND v.id = ?
          ";

        $this->getDB()->safeQuery($sql, [$to_field_id, $to_field_id, $from_value_id]);
        return (int) $this->getDB()->lastInsertId();
    }

    public function create(int $field_id, int $ugroup_id, bool $is_hidden): int
    {
        return (int) $this->getDB()->insertReturnId(
            "tracker_field_list_bind_ugroups_value",
            [
                'field_id' => $field_id,
                'ugroup_id' => $ugroup_id,
                'is_hidden' => $is_hidden,
            ]
        );
    }

    public function hide(int $id): void
    {
        $this->toggleHidden($id, true);
    }

    public function show(int $id): void
    {
        $this->toggleHidden($id, false);
    }

    private function toggleHidden(int $id, bool $is_hidden): void
    {
        $this->getDB()->update(
            'tracker_field_list_bind_ugroups_value',
            ['is_hidden' => $is_hidden],
            ['id' => $id]
        );
    }

    /**
     * @psalm-return array{id: int, ugroup_id: int, field_id: int}
     */
    public function searchChangesetValues(int $changeset_id, int $field_id): array
    {
        $sql = "
            SELECT f.id, f.ugroup_id, f.is_hidden
            FROM tracker_field_list_bind_ugroups_value AS f
            INNER JOIN tracker_changeset_value_list AS l ON (l.bindvalue_id = f.id AND f.field_id = $field_id)
            INNER JOIN tracker_changeset_value AS c
            ON (
                l.changeset_value_id = c.id
                AND c.changeset_id = ?
                AND c.field_id = ?
            )
            ORDER BY f.id";
        return $this->getDB()->run($sql, [$changeset_id, $field_id]);
    }
}
