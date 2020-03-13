<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\FRS;

use DataAccessObject;
use FRSPackage;
use FRSRelease;
use ProjectUGroup;

class FRSPermissionDao extends DataAccessObject
{

    public function savePermission($project_id, $permission_type, $ugroup_id)
    {
        $project_id      = $this->da->escapeInt($project_id);
        $permission_type = $this->da->quoteSmart($permission_type);
        $ugroup_id       = $this->da->escapeInt($ugroup_id);

        $sql = "INSERT INTO frs_global_permissions (project_id, permission_type, ugroup_id)
            VALUES ($project_id, $permission_type, $ugroup_id)";

        return $this->update($sql);
    }


    public function searchPermissionsForProjectbyType($project_id, $permission_type)
    {
        $project_id      = $this->da->escapeInt($project_id);
        $permission_type = $this->da->quoteSmart($permission_type);

        $sql = "SELECT * FROM frs_global_permissions
            WHERE project_id = $project_id
            AND permission_type = $permission_type";

        return $this->retrieve($sql);
    }

    public function savePermissions($project_id, $permission_type, array $ugroup_ids)
    {
        $this->da->startTransaction();

        $project_id      = $this->da->escapeInt($project_id);
        $permission_type = $this->da->quoteSmart($permission_type);

        $sql = "DELETE FROM frs_global_permissions WHERE project_id = $project_id AND permission_type = $permission_type";
        if (! $this->update($sql)) {
            $this->da->rollback();
            return false;
        }

        if (count($ugroup_ids) == 0) {
            return $this->da->commit();
        }
        $ugroups = array();
        foreach ($ugroup_ids as $ugroup_id) {
            $ugroup_id   = $this->da->escapeInt($ugroup_id);
            $ugroups[] = "($project_id, $permission_type, $ugroup_id)";
        }

        $sql = "INSERT INTO frs_global_permissions (project_id, permission_type, ugroup_id)
            VALUES " . implode(",", $ugroups);

        if (! $this->update($sql)) {
            $this->da->rollback();
            return false;
        }

        return $this->da->commit();
    }

    public function duplicate($project_id, $template_id)
    {
        $project_id  = $this->da->escapeInt($project_id);
        $template_id = $this->da->escapeInt($template_id);

        $sql = "INSERT INTO frs_global_permissions(project_id, permission_type, ugroup_id)
                SELECT $project_id, permission_type, R.dst_ugroup_id
                FROM frs_global_permissions
                    INNER JOIN (
                            SELECT src_ugroup_id, dst_ugroup_id
                            FROM ugroup_mapping
                            WHERE to_group_id = $project_id
                        UNION
                            SELECT ugroup_id AS src_ugroup_id, ugroup_id AS dst_ugroup_id
                            FROM ugroup
                            WHERE ugroup_id <= 100
                    ) AS R ON (R.src_ugroup_id = ugroup_id)
                WHERE project_id = $template_id";

        return $this->update($sql);
    }

    public function disableAnonymousRegisteredAuthenticated($project_id)
    {
        return $this->updateAccessControl(
            $project_id,
            array(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::PROJECT_MEMBERS
        );
    }

    public function disableAuthenticated($project_id)
    {
        return $this->updateAccessControl(
            $project_id,
            array(ProjectUGroup::AUTHENTICATED),
            ProjectUGroup::REGISTERED
        );
    }

    private function updateAccessControl($project_id, array $old_ugroup_ids, $new_ugroup_id)
    {
        $project_id     = $this->da->escapeInt($project_id);
        $old_ugroup_ids = $this->da->escapeIntImplode($old_ugroup_ids);
        $new_ugroup_id  = $this->da->escapeInt($new_ugroup_id);
        $package_read   = $this->da->quoteSmart(FRSPackage::PERM_READ);
        $release_read   = $this->da->quoteSmart(FRSRelease::PERM_READ);

        $this->da->startTransaction();

        $sql = "UPDATE frs_global_permissions
                SET ugroup_id = $new_ugroup_id
                WHERE ugroup_id IN ($old_ugroup_ids)
                  AND project_id = $project_id";
        $this->update($sql);

        $sql = "UPDATE permissions
                INNER JOIN frs_package ON permissions.object_id = CAST(frs_package.package_id AS CHAR CHARACTER SET utf8)
                INNER JOIN frs_release ON frs_release.package_id = frs_package.package_id
                SET ugroup_id = $new_ugroup_id
                WHERE ugroup_id IN ($old_ugroup_ids)
                  AND frs_package.group_id = $project_id
                  AND permission_type IN ($package_read, $release_read)";
        $this->update($sql);

        return $this->da->commit();
    }

    private function searchAllProjectsWithAnonymous()
    {
        return $this->searchAllProjectsWithPermissionGroup(ProjectUGroup::ANONYMOUS);
    }

    private function searchAllProjectsWithUnrestricted()
    {
        return $this->searchAllProjectsWithPermissionGroup(ProjectUGroup::AUTHENTICATED);
    }

    private function searchAllProjectsWithPermissionGroup($ugroup_id)
    {
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "SELECT DISTINCT groups.group_id
                FROM groups
                  JOIN frs_global_permissions ON groups.group_id = frs_global_permissions.project_id
                WHERE groups.status = 'A'
                  AND ugroup_id = $ugroup_id
                ";

        return $this->retrieve($sql);
    }

    public function updateAllAnonymousAccessToRegistered()
    {
        if ($this->searchAllProjectsWithAnonymous()->rowCount() > 0) {
            return $this->switchDynamicUgroup(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED);
        }
    }

    public function updateAllAuthenticatedAccessToRegistered()
    {
        if ($this->searchAllProjectsWithUnrestricted()->rowCount() > 0) {
            return $this->switchDynamicUgroup(ProjectUGroup::AUTHENTICATED, ProjectUGroup::REGISTERED);
        }
    }

    private function switchDynamicUgroup($old_ugroup_id, $new_ugroup_id)
    {
        $old_ugroup_id = $this->da->escapeInt($old_ugroup_id);
        $new_ugroup_id = $this->da->escapeInt($new_ugroup_id);
        $package_read  = $this->da->quoteSmart(FRSPackage::PERM_READ);
        $release_read  = $this->da->quoteSmart(FRSRelease::PERM_READ);

        $this->da->startTransaction();

        $sql = "UPDATE frs_global_permissions
                SET ugroup_id = $new_ugroup_id
                WHERE ugroup_id = $old_ugroup_id
                ";
        $this->update($sql);

        $sql = "UPDATE permissions
                SET ugroup_id = $new_ugroup_id
                WHERE ugroup_id = $old_ugroup_id
                AND permission_type IN ($package_read, $release_read)
                ";
        $this->update($sql);

        return $this->da->commit();
    }
}
