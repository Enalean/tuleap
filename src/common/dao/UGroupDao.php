<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 */

/**
 * @psalm-type UGroupRow = array{ugroup_id: int, name: string, description: string, source_id: int, group_id: int}
 */
class UGroupDao extends \Tuleap\DB\DataAccessObject
{
    /**
     * @psalm-return ?UGroupRow
     */
    public function searchByUGroupId(int $ugroup_id): ?array
    {
        $sql = 'SELECT ugroup_id, name, description, source_id, group_id
                FROM ugroup
                WHERE ugroup_id = ?';
        return $this->getDB()->row($sql, $ugroup_id);
    }

    /**
     * @psalm-return UGroupRow[]
     */
    public function searchByListOfUGroupsId(array $ugroup_ids): array
    {
        if (count($ugroup_ids) <= 0) {
            return [];
        }
        $ugroup_ids_stmt = \ParagonIE\EasyDB\EasyStatement::open()->in(
            'ugroup_id IN (?*)',
            $ugroup_ids
        );
        $sql             = "SELECT ugroup_id, name, description, source_id, group_id
                FROM ugroup
                WHERE $ugroup_ids_stmt";

        return $this->getDB()->run($sql, ...$ugroup_ids_stmt->values());
    }

    /**
     * @psalm-return UGroupRow[]
     */
    public function searchDynamicAndStaticByGroupId(int $project_id): array
    {
        $sql = 'SELECT *
                FROM ugroup
                WHERE group_id = ? OR (group_id = 100 and ugroup_id <= 100)
                ORDER BY ugroup_id';
        return $this->getDB()->run($sql, $project_id);
    }

    /**
     * @psalm-return UGroupRow[]
     */
    public function searchStaticByGroupId(int $project_id): array
    {
        $sql = 'SELECT *
                FROM ugroup
                WHERE group_id = ?
                ORDER BY ugroup_id';
        return $this->getDB()->run($sql, $project_id);
    }

    /**
     * @psalm-return ?UGroupRow
     */

    public function searchByGroupIdAndUGroupId(int $project_id, int $ugroup_id): ?array
    {
        $sql = 'SELECT *
                FROM ugroup
                WHERE group_id = ? AND ugroup_id = ?';
        return $this->getDB()->row($sql, $project_id, $ugroup_id);
    }

    /**
     * @psalm-return array{name: string}|null
     */
    public function searchNameByGroupIdAndUGroupId(int $project_id, int $ugroup_id): ?array
    {
        $sql = 'SELECT name
                FROM ugroup
                WHERE (group_id = ? OR group_id = 100) AND ugroup_id = ?';
        return $this->getDB()->row($sql, $project_id, $ugroup_id);
    }

    /**
     * @psalm-return ?UGroupRow
     */
    public function searchByGroupIdAndName(int $project_id, string $name): ?array
    {
        $sql = 'SELECT *
                FROM ugroup
                WHERE group_id = ? AND name = ?';


        return $this->getDB()->row($sql, $project_id, $name);
    }

    /**
     * Searches group that user belongs to one of its static ugroup
     * return all groups
     *
     * @psalm-return array<array{group_id: int}>
     */
    public function searchGroupByUserId(int $user_id): array
    {
        $sql = "SELECT `groups`.group_id
                  FROM ugroup
                  JOIN ugroup_user USING (ugroup_id)
                  JOIN `groups` USING (group_id)
                WHERE user_id = ?
                AND status != 'D'
                ORDER BY group_name";

        return $this->getDB()->run($sql, $user_id);
    }

    /**
     * Return all UGroups the user belongs to (cross projects)
     *
     * @psalm-return UGroupRow[]
     */
    public function searchByUserId(int $user_id): array
    {
        $sql = 'SELECT ug.*
                FROM ugroup_user AS ug_u
                    INNER JOIN ugroup AS ug USING (ugroup_id)
                WHERE ug_u.user_id = ?';

        return $this->getDB()->run($sql, $user_id);
    }

    /**
     * @psalm-return UGroupRow[]
     */
    public function searchByUserIdTakingAccountUserProjectMembership(int $user_id): array
    {
        $sql = "SELECT ug.*
                FROM ugroup_user AS ug_u
                     INNER JOIN user USING (user_id)
                     INNER JOIN ugroup AS ug USING (ugroup_id)
                     INNER JOIN `groups` AS g USING (group_id)
                     LEFT JOIN user_group USING(group_id, user_id)
                     INNER JOIN forgeconfig ON (forgeconfig.name = 'access_mode')
                WHERE ug_u.user_id = ?
                  AND (
                    (
                        forgeconfig.value = ?
                        AND (
                            user_group.group_id IS NOT NULL
                            OR
                            user.status = 'A' AND g.access IN (?, ?)
                            OR
                            user.status = 'R' AND g.access = ?
                        )
                    )
                    OR
                    (
                        forgeconfig.value <> ?
                        AND (
                            user_group.group_id IS NOT NULL
                            OR
                            g.access NOT IN (?, ?)
                        )
                    )
                  )
                  AND g.status = ?";

        return $this->getDB()->run(
            $sql,
            $user_id,
            ForgeAccess::RESTRICTED,
            Project::ACCESS_PUBLIC,
            Project::ACCESS_PUBLIC_UNRESTRICTED,
            Project::ACCESS_PUBLIC_UNRESTRICTED,
            ForgeAccess::RESTRICTED,
            Project::ACCESS_PRIVATE,
            Project::ACCESS_PRIVATE_WO_RESTRICTED,
            Project::STATUS_ACTIVE,
        );
    }

    public function checkUGroupValidityByGroupId(int $project_id, int $ugroup_id): bool
    {
        $sql = 'SELECT COUNT(ugroup_id)
                FROM ugroup
                WHERE group_id = ? AND ugroup_id = ?';

        return $this->getDB()->exists($sql, $project_id, $ugroup_id);
    }

    public function updateUgroupBinding(int $ugroup_id, ?int $source_id = null): void
    {
        if ($source_id === null) {
            $this->getDB()->run('UPDATE ugroup SET source_id = NULL WHERE ugroup_id = ?', $ugroup_id);
        } else {
            $this->getDB()->run('UPDATE ugroup SET source_id = ? WHERE ugroup_id = ?', $source_id, $ugroup_id);
        }
    }

    /**
     * @psalm-return UGroupRow[]
     */
    public function searchUGroupByBindingSource(int $source_id): array
    {
        $sql = 'SELECT * FROM ugroup WHERE source_id = ?';
        return $this->getDB()->run($sql, $source_id);
    }

    /**
     * @psalm-return UGroupRow[]
     */
    public function searchBindedUgroupsInProject(int $project_id): array
    {
        $sql = 'SELECT *
                FROM ugroup
                WHERE group_id = ?
                AND source_id IS NOT NULL';

        return $this->getDB()->run($sql, $project_id);
    }

    /**
     * Retrieve the source user group from a given bound ugroup id
     *
     * @psalm-return UGroupRow[]
     */
    public function getUgroupBindingSource(int $ugroup_id): array
    {
        $sql = 'SELECT source.*
                     FROM ugroup u
                       JOIN ugroup source ON (source.ugroup_id = u.source_id)
                     WHERE u.ugroup_id = ?';
        return $this->getDB()->run($sql, $ugroup_id);
    }

    public function createUgroupFromSourceUgroup(int $ugroup_id, int $new_project_id): int
    {
        $create_ugroup = 'INSERT INTO ugroup (name,description,group_id)
            SELECT name,description,?
            FROM ugroup
            WHERE ugroup_id=?';

        $this->getDB()->run($create_ugroup, $new_project_id, $ugroup_id);
        return (int) $this->getDB()->lastInsertId();
    }

    public function createBinding(int $new_project_id, int $ugroup_id, int $new_ugroup_id): void
    {
        $create_binding = 'INSERT INTO ugroup_mapping (to_group_id, src_ugroup_id, dst_ugroup_id)
                           VALUES (?, ?, ?)';

        $this->getDB()->run($create_binding, $new_project_id, $ugroup_id, $new_ugroup_id);
    }

    /**
     * @psalm-return UGroupRow[]
     */
    public function searchUgroupsUserIsMemberInProject(int $user_id, int $project_id): array
    {
        $sql = "SELECT ugroup.*
                FROM ugroup
                    INNER JOIN user_group ON (
                        ugroup.ugroup_id = ?
                        AND ugroup.group_id = 100
                        AND user_group.user_id = ?
                        AND user_group.group_id = ?
                    )
                UNION
                SELECT ugroup.*
                FROM ugroup
                    INNER JOIN user_group ON (
                        ugroup.ugroup_id = ?
                        AND ugroup.group_id = 100
                        AND user_group.user_id = ?
                        AND user_group.group_id = ?
                        AND user_group.admin_flags = 'A'
                    )
                UNION
                SELECT ugroup.*
                FROM ugroup
                    INNER JOIN ugroup_user ON (
                        ugroup.ugroup_id = ugroup_user.ugroup_id
                        AND ugroup_user.user_id = ?
                        AND ugroup.group_id = ?
                    )
                ";

        return $this->getDB()->run(
            $sql,
            ProjectUGroup::PROJECT_MEMBERS,
            $user_id,
            $project_id,
            ProjectUGroup::PROJECT_ADMIN,
            $user_id,
            $project_id,
            $user_id,
            $project_id,
        );
    }

    /**
     * @psalm-return UGroupRow[]
     */
    public function searchUgroupsForAdministratorInProject(int $user_id, int $project_id): array
    {
        $sql = "SELECT ugroup.*
                FROM ugroup
                    INNER JOIN user_group ON (
                        ugroup.ugroup_id = ?
                        AND ugroup.group_id = 100
                        AND user_group.user_id = ?
                        AND user_group.group_id = ?
                    )
                UNION
                SELECT ugroup.*
                FROM ugroup
                    INNER JOIN user_group ON (
                        ugroup.ugroup_id = ?
                        AND ugroup.group_id = 100
                        AND user_group.user_id = ?
                        AND user_group.group_id = ?
                        AND user_group.admin_flags = 'A'
                    )
                UNION
                SELECT ugroup.*
                FROM ugroup
                WHERE ugroup.group_id = ?
                ";

        return $this->getDB()->run(
            $sql,
            ProjectUGroup::PROJECT_MEMBERS,
            $user_id,
            $project_id,
            ProjectUGroup::PROJECT_ADMIN,
            $user_id,
            $project_id,
            $project_id,
        );
    }
}
