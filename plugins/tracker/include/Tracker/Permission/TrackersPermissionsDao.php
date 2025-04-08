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
use Tuleap\Tracker\Artifact\Artifact;

final class TrackersPermissionsDao extends DataAccessObject implements SearchUserGroupsPermissionOnFields, SearchUserGroupsPermissionOnTrackers, SearchUserGroupsPermissionOnArtifacts
{
    public function searchUserGroupsPermissionOnFields(array $user_groups, array $fields_id, FieldPermissionType $permission): array
    {
        $fields_statement = EasyStatement::open()->in('field.id IN (?*)', $fields_id);
        $groups_statement = implode(', ', array_map(static fn(UserGroupInProject $user_group) => '(?, ?)', $user_groups));
        $groups_values    = [];
        foreach ($user_groups as $user_group) {
            $groups_values[] = $user_group->project_id;
            $groups_values[] = $user_group->user_group_id;
        }

        $sql = <<<SQL
        SELECT DISTINCT permissions.object_id AS field_id
        FROM permissions
        INNER JOIN tracker_field AS field ON permissions.object_id = CAST(field.id AS CHAR CHARACTER SET utf8)
        INNER JOIN tracker ON tracker.id = field.tracker_id
        INNER JOIN `groups` AS project ON project.group_id = tracker.group_id
        WHERE permissions.permission_type = ? AND $fields_statement AND (project.group_id, permissions.ugroup_id) IN ($groups_statement)
        SQL;

        $results = $this->getDB()->safeQuery($sql, [$permission->value, ...$fields_id, ...$groups_values]);
        assert(is_array($results));
        return array_map(static fn(array $row) => (int) $row['field_id'], $results);
    }

    public function searchUserGroupsViewPermissionOnTrackers(array $user_groups, array $trackers_id): array
    {
        $trackers_statement  = EasyStatement::open()->in('tracker.id IN (?*)', $trackers_id);
        $perm_type_statement = EasyStatement::open()->in('permissions.permission_type IN (?*)', [
            Tracker::PERMISSION_ADMIN,
            Tracker::PERMISSION_FULL,
            Tracker::PERMISSION_ASSIGNEE,
            Tracker::PERMISSION_SUBMITTER,
            Tracker::PERMISSION_SUBMITTER_ONLY,
        ]);

        $groups_statement = implode(', ', array_map(static fn(UserGroupInProject $user_group) => '(?, ?)', $user_groups));
        $groups_values    = [];
        foreach ($user_groups as $user_group) {
            $groups_values[] = $user_group->project_id;
            $groups_values[] = $user_group->user_group_id;
        }

        $sql = <<<SQL
        SELECT DISTINCT tracker.id AS tracker_id
        FROM permissions
        INNER JOIN tracker ON permissions.object_id = CAST(tracker.id AS CHAR CHARACTER SET utf8)
        INNER JOIN `groups` AS project ON project.group_id = tracker.group_id
        WHERE tracker.deletion_date IS NULL
            AND $trackers_statement AND $perm_type_statement
            AND (project.group_id, permissions.ugroup_id) IN ($groups_statement)
        SQL;

        $results = $this->getDB()->safeQuery($sql, [
            ...$trackers_id,
            ...array_values($perm_type_statement->values()),
            ...$groups_values,
        ]);
        assert(is_array($results));
        return array_map(static fn(array $row) => (int) $row['tracker_id'], $results);
    }

    public function searchUserGroupsSubmitPermissionOnTrackers(array $user_groups_id, array $trackers_id): array
    {
        $ugroups_tracker_statement = EasyStatement::open()->in('tracker_permission.ugroup_id IN (?*)', $user_groups_id);
        $ugroups_field_statement   = EasyStatement::open()->in('field_permission.ugroup_id IN (?*)', $user_groups_id);
        $trackers_statement        = EasyStatement::open()->in('tracker.id IN (?*)', $trackers_id);

        $sql = <<<SQL
        SELECT DISTINCT tracker.id AS tracker_id
        FROM permissions AS tracker_permission
        INNER JOIN tracker ON (tracker_permission.object_id = CAST(tracker.id AS CHAR CHARACTER SET utf8) AND tracker.deletion_date IS NULL)
        INNER JOIN tracker_field AS field ON (tracker.id = field.tracker_id)
        INNER JOIN permissions AS field_permission ON (
            field_permission.object_id = CAST(field.id AS CHAR CHARACTER SET utf8) AND field_permission.permission_type = ?
        )
        WHERE $ugroups_tracker_statement AND $ugroups_field_statement AND $trackers_statement AND tracker_permission.permission_type <> ?
        SQL;

        $results = $this->getDB()->safeQuery($sql, [
            FieldPermissionType::PERMISSION_SUBMIT->value,
            ...$user_groups_id,
            ...$user_groups_id,
            ...$trackers_id,
            Tracker::PERMISSION_NONE,
        ]);
        assert(is_array($results));
        return array_map(static fn(array $row) => (int) $row['tracker_id'], $results);
    }

    public function searchUserGroupsViewPermissionOnArtifacts(array $user_groups_id, array $artifacts_id): array
    {
        $artifacts_statement       = EasyStatement::open()->in('artifact.id IN (?*)', $artifacts_id);
        $ugroup_artifact_statement = EasyStatement::open()->in('artifact_permission.ugroup_id IN (?*)', $user_groups_id);

        $sql = <<<SQL
        SELECT DISTINCT artifact.id AS artifact_id
        FROM tracker_artifact AS artifact
        INNER JOIN tracker ON (artifact.tracker_id = tracker.id AND tracker.deletion_date IS NULL)
        LEFT JOIN permissions AS artifact_permission ON (
            artifact.use_artifact_permissions = 1
            AND artifact_permission.object_id = CAST(artifact.id AS CHAR CHARACTER SET utf8)
            AND artifact_permission.permission_type = ?
            AND $ugroup_artifact_statement
        )
        WHERE $artifacts_statement AND (
            artifact.use_artifact_permissions = 0 OR (
                artifact.use_artifact_permissions = 1 AND artifact_permission.object_id IS NOT NULL
            )
        )
        SQL;

        $results = $this->getDB()->q(
            $sql,
            Artifact::PERMISSION_ACCESS,
            ...$user_groups_id,
            ...$artifacts_id,
        );
        return array_map(static fn(array $row) => (int) $row['artifact_id'], $results);
    }
}
