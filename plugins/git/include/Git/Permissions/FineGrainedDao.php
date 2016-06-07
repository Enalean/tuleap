<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Git\Permissions;

use DataAccessObject;

class FineGrainedDao extends DataAccessObject
{
    public function enableRepository($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "REPLACE INTO plugin_git_repository_fine_grained_permissions_enabled (repository_id)
                VALUES ($repository_id)";

        return $this->update($sql);
    }

    public function disableRepository($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "DELETE FROM plugin_git_repository_fine_grained_permissions_enabled
                WHERE repository_id = $repository_id";

        return $this->update($sql);
    }

    public function searchRepositoryUseFineGrainedPermissions($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT repository_id
                FROM plugin_git_repository_fine_grained_permissions_enabled
                WHERE repository_id = $repository_id";

        return $this->retrieveFirstRow($sql);
    }

    public function searchBranchesFineGrainedPermissionsForRepository($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT *
                FROM plugin_git_repository_fine_grained_permissions
                WHERE repository_id = $repository_id
                AND pattern LIKE 'refs/heads/%'";

        return $this->retrieve($sql);
    }

    public function searchTagsFineGrainedPermissionsForRepository($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT *
                FROM plugin_git_repository_fine_grained_permissions
                WHERE repository_id = $repository_id
                AND pattern LIKE 'refs/tags/%'";

        return $this->retrieve($sql);
    }

    public function searchWriterUgroupIdsForFineGrainedPermissions($permission_id)
    {
        $permission_id = $this->da->escapeInt($permission_id);

        $sql = "SELECT ugroup_id
                FROM plugin_git_repository_fine_grained_permissions_writers
                WHERE permission_id = $permission_id";

        return $this->retrieve($sql);
    }

    public function searchRewinderUgroupIdsForFineGrainePermissions($permission_id)
    {
        $permission_id = $this->da->escapeInt($permission_id);

        $sql = "SELECT ugroup_id
                FROM plugin_git_repository_fine_grained_permissions_rewinders
                WHERE permission_id = $permission_id";

        return $this->retrieve($sql);
    }

    public function save($repository_id, $pattern, array $writer_ids, array $rewinder_ids)
    {
        $this->da->startTransaction();

        $permission_id = $this->createPermission($repository_id, $pattern);
        if (! $permission_id) {
            $this->da->rollback();
            return false;
        }

        if (! $this->saveWriters($permission_id, $writer_ids) || ! $this->saveRewinders($permission_id, $rewinder_ids)) {
            $this->da->rollback();
            return false;
        }

        return $this->da->commit();
    }

    private function createPermission($repository_id, $pattern)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $pattern       = $this->da->quoteSmart($pattern);

        $sql = "INSERT INTO plugin_git_repository_fine_grained_permissions (repository_id, pattern)
                VALUES ($repository_id, $pattern)";

        return $this->updateAndGetLastId($sql);
    }

    private function saveWriters($permission_id, array $writer_ids)
    {
        if (count($writer_ids) === 0) {
            return true;
        }

        $permission_id = $this->da->escapeInt($permission_id);

        $values = array();
        foreach ($writer_ids as $writer_id) {
            $writer_id = $this->da->escapeInt($writer_id);
            $values[]  = "($permission_id, $writer_id)";
        }

        $sql = "INSERT INTO plugin_git_repository_fine_grained_permissions_writers (permission_id, ugroup_id)
                VALUES " . implode(',', $values);

        return $this->update($sql);
    }

    private function saveRewinders($permission_id, array $rewinder_ids)
    {
        if (count($rewinder_ids) === 0) {
            return true;
        }

        $permission_id = $this->da->escapeInt($permission_id);

        $values = array();
        foreach ($rewinder_ids as $rewinder_id) {
            $rewinder_id = $this->da->escapeInt($rewinder_id);
            $values[]    = "($permission_id, $rewinder_id)";
        }

        $sql = "INSERT INTO plugin_git_repository_fine_grained_permissions_rewinders (permission_id, ugroup_id)
                VALUES " . implode(',', $values);

        return $this->update($sql);
    }

    public function getPermissionIdByPatternForRepository($repository_id, $pattern)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $pattern       = $this->da->quoteSmart($pattern);

        $sql = "SELECT id
                FROM plugin_git_repository_fine_grained_permissions
                WHERE pattern = $pattern
                    AND repository_id = $repository_id";

        return $this->retrieveIds($sql);
    }
}
