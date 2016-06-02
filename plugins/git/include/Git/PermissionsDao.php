<?php
/**
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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

class Git_PermissionsDao extends DataAccessObject
{

    public function disableAnonymousRegisteredAuthenticated($project_id)
    {
        $this->da->startTransaction();

        if (! $this->updatePermissionsForGitRepositories(
            $project_id,
            array(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::PROJECT_MEMBERS
        )) {
            $this->da->rollback();
            return false;
        }

        if (! $this->updatePermissionsForDefaultGitAccessRights(
            $project_id,
            array(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::PROJECT_MEMBERS
        )) {
            $this->da->rollback();
            return false;
        }

        return $this->da->commit();
    }

    public function disableAuthenticated($project_id)
    {
        $this->da->startTransaction();

        if (! $this->updatePermissionsForGitRepositories(
            $project_id,
            array(ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::REGISTERED
        )) {
            $this->da->rollback();
            return false;
        }

        if (! $this->updatePermissionsForDefaultGitAccessRights(
            $project_id,
            array(ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::REGISTERED
        )) {
            $this->da->rollback();
            return false;
        }

        return $this->da->commit();
    }

    private function updatePermissionsForDefaultGitAccessRights($project_id, array $old_ugroup_ids, $new_ugroup_id)
    {
        $project_id          = $this->da->escapeInt($project_id);
        $old_ugroup_ids      = $this->da->escapeIntImplode($old_ugroup_ids);
        $git_permission_type = $this->da->quoteSmartImplode(',', Git::allDefaultPermissionTypes());

        $sql = "UPDATE permissions
                SET ugroup_id = $new_ugroup_id
                WHERE ugroup_id IN ($old_ugroup_ids)
                  AND object_id = $project_id
                  AND permission_type IN ($git_permission_type)";

        return $this->update($sql);
    }

    private function updatePermissionsForGitRepositories($project_id, array $old_ugroup_ids, $new_ugroup_id)
    {
        $project_id          = $this->da->escapeInt($project_id);
        $old_ugroup_ids      = $this->da->escapeIntImplode($old_ugroup_ids);
        $git_permission_type = $this->da->quoteSmartImplode(',', Git::allPermissionTypes());
        $sql = "UPDATE permissions perms
                  JOIN plugin_git git ON (perms.object_id = CAST(git.repository_id AS CHAR) AND perms.permission_type IN ($git_permission_type))
                SET perms.ugroup_id = $new_ugroup_id
                WHERE perms.ugroup_id IN ($old_ugroup_ids)
                  AND git.project_id = $project_id
                ";
        return $this->update($sql);
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
        $git_permission_type = $this->da->quoteSmartImplode(',', Git::allPermissionTypes());
        $not_deleted_date    = $this->da->quoteSmart(GitDao::NOT_DELETED_DATE);

        $sql = "SELECT DISTINCT groups.group_id
                FROM groups
                  JOIN plugin_git AS git ON (git.project_id = groups.group_id)
                  JOIN permissions ON (permissions.object_id = CAST(git.repository_id AS CHAR) AND permissions.permission_type IN ($git_permission_type))
                WHERE groups.status = 'A'
                  AND git.repository_deletion_date = $not_deleted_date
                  AND permissions.ugroup_id = $ugroup_id
                ";
        return $this->retrieve($sql);
    }

    public function updateAllAnonymousAccessToRegistered()
    {
        return $this->updateAllPermissions(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED);
    }

    public function updateAllAuthenticatedAccessToRegistered()
    {
        return $this->updateAllPermissions(ProjectUGroup::AUTHENTICATED, ProjectUGroup::REGISTERED);
    }

    private function updateAllPermissions($old_ugroup_id, $new_ugroup_id)
    {
        $all_permission_types = array_merge(Git::allPermissionTypes(), Git::allDefaultPermissionTypes());
        $git_permission_type  = $this->da->quoteSmartImplode(',', $all_permission_types);

        $sql = "UPDATE permissions
                SET ugroup_id = $new_ugroup_id
                WHERE ugroup_id = $old_ugroup_id
                    AND permission_type IN ($git_permission_type)
                ";
        return $this->update($sql);
    }
}
