<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use ParagonIE\EasyDB\EasyStatement;

class Git_PermissionsDao extends \Tuleap\DB\DataAccessObject
{

    public function disableAnonymousRegisteredAuthenticated($project_id)
    {
        $this->getDB()->beginTransaction();

        try {
            $this->updatePermissionsForGitRepositories(
                $project_id,
                [ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED],
                ProjectUGroup::PROJECT_MEMBERS
            );
            $this->updatePermissionsForDefaultGitAccessRights(
                $project_id,
                [ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED],
                ProjectUGroup::PROJECT_MEMBERS
            );
        } catch (PDOException $ex) {
            $this->getDB()->rollBack();
            throw $ex;
        }

        $this->getDB()->commit();
    }

    public function disableAuthenticated($project_id)
    {
        $this->getDB()->beginTransaction();

        try {
            $this->updatePermissionsForGitRepositories(
                $project_id,
                [ProjectUGroup::AUTHENTICATED],
                ProjectUGroup::REGISTERED
            );
            $this->updatePermissionsForDefaultGitAccessRights(
                $project_id,
                [ProjectUGroup::AUTHENTICATED],
                ProjectUGroup::REGISTERED
            );
        } catch (PDOException $ex) {
            $this->getDB()->rollBack();
            throw $ex;
        }

        $this->getDB()->commit();
    }

    private function updatePermissionsForDefaultGitAccessRights($project_id, array $old_ugroup_ids, $new_ugroup_id)
    {
        $git_permission_type_in_condition = EasyStatement::open()->in('?*', Git::allDefaultPermissionTypes());
        $old_ugroup_ids_in_condition      = EasyStatement::open()->in('?*', $old_ugroup_ids);

        $sql = "UPDATE permissions
                SET ugroup_id = ?
                WHERE ugroup_id IN ($old_ugroup_ids_in_condition)
                  AND object_id = ?
                  AND permission_type IN ($git_permission_type_in_condition)";

        $update_params   = [$new_ugroup_id];
        $update_params   = array_merge($update_params, $old_ugroup_ids_in_condition->values());
        $update_params[] = $project_id;
        $update_params   = array_merge($update_params, $git_permission_type_in_condition->values());

        $this->getDB()->safeQuery($sql, $update_params);
    }

    private function updatePermissionsForGitRepositories($project_id, array $old_ugroup_ids, $new_ugroup_id)
    {
        $git_permission_type_in_condition = EasyStatement::open()->in('?*', Git::allPermissionTypes());
        $old_ugroup_ids_in_condition      = EasyStatement::open()->in('?*', $old_ugroup_ids);

        $sql = "UPDATE permissions perms
                  JOIN plugin_git git ON (perms.object_id = CAST(git.repository_id AS CHAR CHARACTER SET utf8) AND perms.permission_type IN ($git_permission_type_in_condition))
                SET perms.ugroup_id = ?
                WHERE perms.ugroup_id IN ($old_ugroup_ids_in_condition)
                  AND git.project_id = ?";

        $update_params   = $git_permission_type_in_condition->values();
        $update_params[] = $new_ugroup_id;
        $update_params   = array_merge($update_params, $old_ugroup_ids_in_condition->values());
        $update_params[] = $project_id;

        $this->getDB()->safeQuery($sql, $update_params);
    }

    public function getAllProjectsWithAnonymousRepositories()
    {
        return $this->getAllProjectsWithPermissionGroup(ProjectUGroup::ANONYMOUS);
    }

    public function getAllProjectsWithUnrestrictedRepositories()
    {
        return $this->getAllProjectsWithPermissionGroup(ProjectUGroup::AUTHENTICATED);
    }

    private function getAllProjectsWithPermissionGroup($ugroup_id)
    {
        $git_permission_type_in_condition = EasyStatement::open()->in('?*', Git::allPermissionTypes());

        $sql = "SELECT DISTINCT groups.group_id
                FROM groups
                  JOIN plugin_git AS git ON (git.project_id = groups.group_id)
                  JOIN permissions ON (permissions.object_id = CAST(git.repository_id AS CHAR CHARACTER SET utf8) AND permissions.permission_type IN ($git_permission_type_in_condition))
                WHERE groups.status = 'A'
                  AND git.repository_deletion_date = ?
                  AND permissions.ugroup_id = ?
                ";

        $params = $git_permission_type_in_condition->values();
        $params[] = GitDao::NOT_DELETED_DATE;
        $params[] = $ugroup_id;

        return $this->getDB()->safeQuery($sql, $params);
    }

    public function updateAllAnonymousAccessToRegistered()
    {
        $this->updateAllPermissions(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED);
    }

    public function updateAllAuthenticatedAccessToRegistered()
    {
        $this->updateAllPermissions(ProjectUGroup::AUTHENTICATED, ProjectUGroup::REGISTERED);
    }

    private function updateAllPermissions($old_ugroup_id, $new_ugroup_id)
    {
        $all_permission_types             = array_merge(Git::allPermissionTypes(), Git::allDefaultPermissionTypes());
        $git_permission_type_in_condition = EasyStatement::open()->in('?*', $all_permission_types);

        $sql = "UPDATE permissions
                SET ugroup_id = ?
                WHERE ugroup_id = ?
                    AND permission_type IN ($git_permission_type_in_condition)";

        $update_params = [$new_ugroup_id, $old_ugroup_id];
        $update_params = array_merge($update_params, $git_permission_type_in_condition->values());

        $this->getDB()->safeQuery($sql, $update_params);
    }
}
