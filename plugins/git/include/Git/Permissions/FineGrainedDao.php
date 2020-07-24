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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\Permissions;

use ParagonIE\EasyDB\EasyStatement;
use ProjectUGroup;
use Tuleap\DB\DataAccessObject;

class FineGrainedDao extends DataAccessObject
{
    public function enableRepository($repository_id)
    {
        $sql = 'REPLACE INTO plugin_git_repository_fine_grained_permissions_enabled (repository_id)
                VALUES (?)';

        $this->getDB()->run($sql, $repository_id);
    }

    public function disableRepository($repository_id)
    {
        $sql = 'DELETE FROM plugin_git_repository_fine_grained_permissions_enabled
                WHERE repository_id = ?';

        $this->getDB()->run($sql, $repository_id);
    }

    public function replicateFineGrainedPermissionsEnabledFromDefault($project_id, $repository_id)
    {
        $sql = 'INSERT INTO plugin_git_repository_fine_grained_permissions_enabled (repository_id)
                SELECT ?
                FROM plugin_git_default_fine_grained_permissions_enabled
                WHERE project_id = ?';

        $this->getDB()->run($sql, $repository_id, $project_id);
    }

    public function replicateFineGrainedPermissionsEnabledFromRepository($source_repository_id, $repository_id)
    {
        $sql = 'INSERT INTO plugin_git_repository_fine_grained_permissions_enabled (repository_id)
                SELECT ?
                FROM plugin_git_repository_fine_grained_permissions_enabled
                WHERE repository_id = ?';

        $this->getDB()->run($sql, $source_repository_id, $repository_id);
    }

    public function searchRepositoryUseFineGrainedPermissions($repository_id)
    {
        $sql = 'SELECT repository_id
                FROM plugin_git_repository_fine_grained_permissions_enabled
                WHERE repository_id = ?';

        return $this->getDB()->row($sql, $repository_id);
    }

    public function searchBranchesFineGrainedPermissionsForRepository($repository_id)
    {
        $sql = "SELECT *
                FROM plugin_git_repository_fine_grained_permissions
                WHERE repository_id = ?
                AND pattern LIKE 'refs/heads/%'";

        return $this->getDB()->run($sql, $repository_id);
    }

    public function searchTagsFineGrainedPermissionsForRepository($repository_id)
    {
        $sql = "SELECT *
                FROM plugin_git_repository_fine_grained_permissions
                WHERE repository_id = ?
                AND pattern LIKE 'refs/tags/%'";

        return $this->getDB()->run($sql, $repository_id);
    }

    public function searchWriterUgroupIdsForFineGrainedPermissions($permission_id)
    {
        $sql = 'SELECT ugroup_id
                FROM plugin_git_repository_fine_grained_permissions_writers
                WHERE permission_id = ?';

        return $this->getDB()->run($sql, $permission_id);
    }

    public function searchRewinderUgroupIdsForFineGrainePermissions($permission_id)
    {
        $sql = 'SELECT ugroup_id
                FROM plugin_git_repository_fine_grained_permissions_rewinders
                WHERE permission_id = ?';

        return $this->getDB()->run($sql, $permission_id);
    }

    public function save($repository_id, $pattern, array $writer_ids, array $rewinder_ids)
    {
        $this->getDB()->beginTransaction();

        try {
            $permission_id = $this->createPermission($repository_id, $pattern);
            $this->saveWriters($permission_id, $writer_ids);
            $this->saveRewinders($permission_id, $rewinder_ids);
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
            return false;
        }

        return $this->getDB()->commit();
    }

    private function createPermission($repository_id, $pattern)
    {
        $sql = 'INSERT INTO plugin_git_repository_fine_grained_permissions (repository_id, pattern)
                VALUES (?, ?)';

        $this->getDB()->run($sql, $repository_id, $pattern);

        return $this->getDB()->lastInsertId();
    }

    private function saveWriters($permission_id, array $writer_ids)
    {
        if (count($writer_ids) === 0) {
            return;
        }

        $data_to_insert = [];
        foreach ($writer_ids as $writer_id) {
            $data_to_insert[] = ['permission_id' => $permission_id, 'ugroup_id' => $writer_id];
        }

        $this->getDB()->insertMany('plugin_git_repository_fine_grained_permissions_writers', $data_to_insert);
    }

    private function saveRewinders($permission_id, array $rewinder_ids)
    {
        if (count($rewinder_ids) === 0) {
            return;
        }

        $data_to_insert = [];
        foreach ($rewinder_ids as $rewinder_id) {
            $data_to_insert[] = ['permission_id' => $permission_id, 'ugroup_id' => $rewinder_id];
        }

        $this->getDB()->insertMany('plugin_git_repository_fine_grained_permissions_rewinders', $data_to_insert);
    }

    public function getPermissionIdByPatternForRepository($repository_id, $pattern)
    {
        $sql = 'SELECT id
                FROM plugin_git_repository_fine_grained_permissions
                WHERE pattern = ?
                    AND repository_id = ?';

        return $this->getDB()->run($sql, $pattern, $repository_id);
    }

    public function enableProject($project_id)
    {
        $sql = 'REPLACE INTO plugin_git_default_fine_grained_permissions_enabled (project_id)
                VALUES (?)';

        $this->getDB()->run($sql, $project_id);
    }

    public function disableProject($project_id)
    {
        $sql = 'DELETE FROM plugin_git_default_fine_grained_permissions_enabled
                WHERE project_id = ?';

        $this->getDB()->run($sql, $project_id);
    }

    public function searchProjectUseFineGrainedPermissions($project_id)
    {
        $sql = 'SELECT project_id
                FROM plugin_git_default_fine_grained_permissions_enabled
                WHERE project_id = ?';

        return $this->getDB()->row($sql, $project_id);
    }

    public function searchDefaultBranchesFineGrainedPermissions($project_id)
    {
        $sql = "SELECT *
                FROM plugin_git_default_fine_grained_permissions
                WHERE project_id = ?
                AND pattern LIKE 'refs/heads/%'";

        return $this->getDB()->run($sql, $project_id);
    }

    public function searchDefaultTagsFineGrainedPermissions($project_id)
    {
        $sql = "SELECT *
                FROM plugin_git_default_fine_grained_permissions
                WHERE project_id = ?
                AND pattern LIKE 'refs/tags/%'";

        return $this->getDB()->run($sql, $project_id);
    }

    public function searchDefaultWriterUgroupIdsForFineGrainedPermissions($permission_id)
    {
        $sql = 'SELECT ugroup_id
                FROM plugin_git_default_fine_grained_permissions_writers
                WHERE permission_id = ?';

        return $this->getDB()->run($sql, $permission_id);
    }

    public function searchDefaultRewinderUgroupIdsForFineGrainePermissions($permission_id)
    {
        $sql = 'SELECT ugroup_id
                FROM plugin_git_default_fine_grained_permissions_rewinders
                WHERE permission_id = ?';

        return $this->getDB()->run($sql, $permission_id);
    }

    public function getPermissionIdByPatternForProject($project_id, $pattern)
    {
        $sql = 'SELECT id
                FROM plugin_git_default_fine_grained_permissions
                WHERE pattern = ?
                    AND project_id = ?';

        return $this->getDB()->run($sql, $pattern, $project_id);
    }

    public function saveDefault($project_id, $pattern, array $writer_ids, array $rewinder_ids)
    {
        $this->getDB()->beginTransaction();

        try {
            $permission_id = $this->createDefaultPermission($project_id, $pattern);
            $this->saveDefaultWriters($permission_id, $writer_ids);
            $this->saveDefaultRewinders($permission_id, $rewinder_ids);
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
            return false;
        }

        return $this->getDB()->commit();
    }

    private function createDefaultPermission($project_id, $pattern)
    {
        $sql = 'INSERT INTO plugin_git_default_fine_grained_permissions (project_id, pattern)
                VALUES (?, ?)';

        $this->getDB()->run($sql, $project_id, $pattern);

        return $this->getDB()->lastInsertId();
    }

    private function saveDefaultWriters($permission_id, array $writer_ids)
    {
        if (count($writer_ids) === 0) {
            return;
        }

        $data_to_insert = [];
        foreach ($writer_ids as $writer_id) {
            $data_to_insert[] = ['permission_id' => $permission_id, 'ugroup_id' => $writer_id];
        }

        $this->getDB()->insertMany('plugin_git_default_fine_grained_permissions_writers', $data_to_insert);
    }

    private function saveDefaultRewinders($permission_id, array $rewinder_ids)
    {
        if (count($rewinder_ids) === 0) {
            return;
        }

        $data_to_insert = [];
        foreach ($rewinder_ids as $rewinder_id) {
            $data_to_insert[] = ['permission_id' => $permission_id, 'ugroup_id' => $rewinder_id];
        }

        $this->getDB()->insertMany('plugin_git_default_fine_grained_permissions_rewinders', $data_to_insert);
    }

    public function deleteUgroupPermissions($ugroup_id, $project_id)
    {
        $delete_01 = 'DELETE dr
                        FROM plugin_git_default_fine_grained_permissions_rewinders AS dr
                            INNER JOIN plugin_git_default_fine_grained_permissions AS perm ON (dr.permission_id = perm.id)
                        WHERE dr.ugroup_id = ?
                            AND perm.project_id = ?';

        $delete_02 = 'DELETE dw
                        FROM plugin_git_default_fine_grained_permissions_writers AS dw
                            INNER JOIN plugin_git_default_fine_grained_permissions AS perm ON (dw.permission_id = perm.id)
                        WHERE dw.ugroup_id = ?
                            AND perm.project_id = ?';

        $delete_03 = 'DELETE rr
                        FROM plugin_git_repository_fine_grained_permissions_rewinders AS rr
                            INNER JOIN plugin_git_repository_fine_grained_permissions AS perm ON (rr.permission_id = perm.id)
                            INNER JOIN plugin_git ON (perm.repository_id = plugin_git.repository_id)
                        WHERE rr.ugroup_id = ?
                            AND plugin_git.project_id = ?';

        $delete_04 = 'DELETE rw
                        FROM plugin_git_repository_fine_grained_permissions_writers AS rw
                            INNER JOIN plugin_git_repository_fine_grained_permissions AS perm ON (rw.permission_id = perm.id)
                            INNER JOIN plugin_git ON (perm.repository_id = plugin_git.repository_id)
                        WHERE rw.ugroup_id = ?
                            AND plugin_git.project_id = ?';

        $this->getDB()->beginTransaction();

        try {
            $this->getDB()->run($delete_01, $ugroup_id, $project_id);
            $this->getDB()->run($delete_02, $ugroup_id, $project_id);
            $this->getDB()->run($delete_03, $ugroup_id, $project_id);
            $this->getDB()->run($delete_04, $ugroup_id, $project_id);
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
            throw $ex;
        }

        $this->getDB()->commit();
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
        $update_01 = 'UPDATE IGNORE plugin_git_default_fine_grained_permissions_writers
                      SET ugroup_id = ?
                      WHERE ugroup_id = ?';

        $update_02 = 'UPDATE IGNORE plugin_git_default_fine_grained_permissions_rewinders
                      SET ugroup_id = ?
                      WHERE ugroup_id = ?';

        $update_03 = 'UPDATE IGNORE plugin_git_repository_fine_grained_permissions_writers
                      SET ugroup_id = ?
                      WHERE ugroup_id = ?';

        $update_04 = 'UPDATE IGNORE plugin_git_repository_fine_grained_permissions_rewinders
                      SET ugroup_id = ?
                      WHERE ugroup_id = ?';

        $delete_01 = 'DELETE FROM plugin_git_default_fine_grained_permissions_writers
                      WHERE ugroup_id = ?';

        $delete_02 = 'DELETE FROM plugin_git_default_fine_grained_permissions_rewinders
                      WHERE ugroup_id = ?';

        $delete_03 = 'DELETE FROM plugin_git_repository_fine_grained_permissions_writers
                      WHERE ugroup_id = ?';

        $delete_04 = 'DELETE FROM plugin_git_repository_fine_grained_permissions_rewinders
                      WHERE ugroup_id = ?';

        $this->getDB()->beginTransaction();

        try {
            $this->getDB()->run($update_01, $new_ugroup_id, $old_ugroup_id);
            $this->getDB()->run($update_02, $new_ugroup_id, $old_ugroup_id);
            $this->getDB()->run($update_03, $new_ugroup_id, $old_ugroup_id);
            $this->getDB()->run($update_04, $new_ugroup_id, $old_ugroup_id);
            $this->getDB()->run($delete_01, $old_ugroup_id);
            $this->getDB()->run($delete_02, $old_ugroup_id);
            $this->getDB()->run($delete_03, $old_ugroup_id);
            $this->getDB()->run($delete_04, $old_ugroup_id);
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
        }

        $this->getDB()->commit();
    }

    public function duplicateDefaultFineGrainedPermissionsEnabled($template_project_id, $new_project_id)
    {
        $sql = 'INSERT INTO plugin_git_default_fine_grained_permissions_enabled (project_id)
                SELECT ?
                FROM plugin_git_default_fine_grained_permissions_enabled
                WHERE project_id = ?';

        $this->getDB()->run($sql, $template_project_id, $new_project_id);
    }

    public function disableAnonymousRegisteredAuthenticated($project_id)
    {
        $this->updatePermissions(
            $project_id,
            ProjectUGroup::PROJECT_MEMBERS,
            [ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED]
        );
    }

    public function disableAuthenticated($project_id)
    {
        $this->updatePermissions(
            $project_id,
            ProjectUGroup::REGISTERED,
            [ProjectUGroup::AUTHENTICATED]
        );
    }

    private function updatePermissions($project_id, $new_project_ugroup_id, array $old_ugroup_ids)
    {
        $this->getDB()->beginTransaction();
        try {
            $this->updateDefaultWritersPermission($project_id, $new_project_ugroup_id, $old_ugroup_ids);
            $this->updateDefaultRewindersPermission($project_id, $new_project_ugroup_id, $old_ugroup_ids);
            $this->updateRepositoryWritersPermission($project_id, $new_project_ugroup_id, $old_ugroup_ids);
            $this->updateRepositoryRewindersPermission($project_id, $new_project_ugroup_id, $old_ugroup_ids);
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
            throw $ex;
        }
        $this->getDB()->commit();
    }

    public function deleteDefaultPermissions($project_id, $permission_id_to_delete)
    {
        $delete_01 = 'DELETE dr
                      FROM plugin_git_default_fine_grained_permissions_rewinders AS dr
                        JOIN plugin_git_default_fine_grained_permissions AS perm ON (dr.permission_id = perm.id)
                      WHERE dr.permission_id = ?
                        AND perm.project_id = ?';

        $delete_02 = 'DELETE dw
                      FROM plugin_git_default_fine_grained_permissions_writers AS dw
                        JOIN plugin_git_default_fine_grained_permissions AS perm ON (dw.permission_id = perm.id)
                      WHERE dw.permission_id = ?
                        AND perm.project_id = ?';

        $delete_03 = 'DELETE FROM plugin_git_default_fine_grained_permissions
                      WHERE id = ?
                        AND project_id = ?';

        $this->getDB()->beginTransaction();

        try {
            $this->getDB()->run($delete_01, $permission_id_to_delete, $project_id);
            $this->getDB()->run($delete_02, $permission_id_to_delete, $project_id);
            $this->getDB()->run($delete_03, $permission_id_to_delete, $project_id);
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
            return false;
        }

        return $this->getDB()->commit();
    }

    private function updateDefaultWritersPermission($project_id, $new_project_ugroup_id, array $old_ugroup_ids)
    {
        $old_ugroup_ids_in_condition = EasyStatement::open()->in('?*', $old_ugroup_ids);

        $update = "UPDATE IGNORE plugin_git_default_fine_grained_permissions_writers AS dw
                    JOIN plugin_git_default_fine_grained_permissions AS perm ON (dw.permission_id = perm.id)
                    SET dw.ugroup_id = ?
                    WHERE dw.ugroup_id IN ($old_ugroup_ids_in_condition)
                        AND perm.project_id = ?";
        $update_params   = [$new_project_ugroup_id];
        $update_params   = array_merge($update_params, $old_ugroup_ids_in_condition->values());
        $update_params[] = $project_id;
        $this->getDB()->safeQuery($update, $update_params);

        $delete = "DELETE dw
                    FROM plugin_git_default_fine_grained_permissions_writers AS dw
                      JOIN plugin_git_default_fine_grained_permissions AS perm ON (dw.permission_id = perm.id)
                    WHERE dw.ugroup_id IN ($old_ugroup_ids_in_condition)
                      AND perm.project_id = ?";
        $delete_params   = $old_ugroup_ids_in_condition->values();
        $delete_params[] = $project_id;
        $this->getDB()->safeQuery($delete, $delete_params);
    }

    private function updateDefaultRewindersPermission($project_id, $new_project_ugroup_id, array $old_ugroup_ids)
    {
        $old_ugroup_ids_in_condition = EasyStatement::open()->in('?*', $old_ugroup_ids);

        $update = "UPDATE IGNORE plugin_git_default_fine_grained_permissions_rewinders AS dr
                    JOIN plugin_git_default_fine_grained_permissions AS perm ON (dr.permission_id = perm.id)
                    SET dr.ugroup_id = ?
                    WHERE dr.ugroup_id IN ($old_ugroup_ids_in_condition)
                        AND perm.project_id = ?";
        $update_params   = [$new_project_ugroup_id];
        $update_params   = array_merge($update_params, $old_ugroup_ids_in_condition->values());
        $update_params[] = $project_id;
        $this->getDB()->safeQuery($update, $update_params);

        $delete = "DELETE dr
                    FROM plugin_git_default_fine_grained_permissions_rewinders AS dr
                      JOIN plugin_git_default_fine_grained_permissions AS perm ON (dr.permission_id = perm.id)
                    WHERE dr.ugroup_id IN ($old_ugroup_ids_in_condition)
                      AND perm.project_id = ?";
        $delete_params   = $old_ugroup_ids_in_condition->values();
        $delete_params[] = $project_id;
        $this->getDB()->safeQuery($delete, $delete_params);
    }

    private function updateRepositoryWritersPermission($project_id, $new_project_ugroup_id, array $old_ugroup_ids)
    {
        $old_ugroup_ids_in_condition = EasyStatement::open()->in('?*', $old_ugroup_ids);

        $update = "UPDATE IGNORE plugin_git_repository_fine_grained_permissions_writers AS rw
                    JOIN plugin_git_repository_fine_grained_permissions AS perm ON (rw.permission_id = perm.id)
                    JOIN plugin_git ON (perm.repository_id = plugin_git.repository_id)
                    SET rw.ugroup_id = ?
                    WHERE rw.ugroup_id IN ($old_ugroup_ids_in_condition)
                        AND plugin_git.project_id = ?";
        $update_params   = [$new_project_ugroup_id];
        $update_params   = array_merge($update_params, $old_ugroup_ids_in_condition->values());
        $update_params[] = $project_id;
        $this->getDB()->safeQuery($update, $update_params);

        $delete = "DELETE rw
                    FROM plugin_git_repository_fine_grained_permissions_writers AS rw
                      JOIN plugin_git_repository_fine_grained_permissions AS perm ON (rw.permission_id = perm.id)
                      JOIN plugin_git ON (perm.repository_id = plugin_git.repository_id)
                    WHERE rw.ugroup_id IN ($old_ugroup_ids_in_condition)
                      AND plugin_git.project_id = ?";
        $delete_params   = $old_ugroup_ids_in_condition->values();
        $delete_params[] = $project_id;
        $this->getDB()->safeQuery($delete, $delete_params);
    }

    private function updateRepositoryRewindersPermission($project_id, $new_project_ugroup_id, array $old_ugroup_ids)
    {
        $old_ugroup_ids_in_condition = EasyStatement::open()->in('?*', $old_ugroup_ids);

        $update = "UPDATE IGNORE plugin_git_repository_fine_grained_permissions_rewinders AS rr
                    JOIN plugin_git_repository_fine_grained_permissions AS perm ON (rr.permission_id = perm.id)
                    JOIN plugin_git ON (perm.repository_id = plugin_git.repository_id)
                    SET rr.ugroup_id = ?
                    WHERE rr.ugroup_id IN ($old_ugroup_ids_in_condition)
                        AND plugin_git.project_id = ?";
        $update_params   = [$new_project_ugroup_id];
        $update_params   = array_merge($update_params, $old_ugroup_ids_in_condition->values());
        $update_params[] = $project_id;
        $this->getDB()->safeQuery($update, $update_params);

        $delete = "DELETE rr
                    FROM plugin_git_repository_fine_grained_permissions_rewinders AS rr
                      JOIN plugin_git_repository_fine_grained_permissions AS perm ON (rr.permission_id = perm.id)
                      JOIN plugin_git ON (perm.repository_id = plugin_git.repository_id)
                    WHERE rr.ugroup_id IN ($old_ugroup_ids_in_condition)
                      AND plugin_git.project_id = ?";
        $delete_params   = $old_ugroup_ids_in_condition->values();
        $delete_params[] = $project_id;
        $this->getDB()->safeQuery($delete, $delete_params);
    }

    public function deleteRepositoryPermissions($repository_id, $permission_id_to_delete)
    {
        $delete_01 = 'DELETE rr
                      FROM plugin_git_repository_fine_grained_permissions_rewinders AS rr
                        JOIN plugin_git_repository_fine_grained_permissions AS perm ON (perm.id = rr.permission_id)
                      WHERE rr.permission_id = ?
                        AND perm.repository_id = ?';

        $delete_02 = 'DELETE rw
                      FROM plugin_git_repository_fine_grained_permissions_writers AS rw
                        JOIN plugin_git_repository_fine_grained_permissions AS perm ON (perm.id = rw.permission_id)
                      WHERE rw.permission_id = ?
                        AND perm.repository_id = ?';

        $delete_03 = 'DELETE FROM plugin_git_repository_fine_grained_permissions
                      WHERE id = ?
                        AND repository_id = ?';

        $this->getDB()->beginTransaction();

        try {
            $this->getDB()->run($delete_01, $permission_id_to_delete, $repository_id);
            $this->getDB()->run($delete_02, $permission_id_to_delete, $repository_id);
            $this->getDB()->run($delete_03, $permission_id_to_delete, $repository_id);
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
            return false;
        }

        return $this->getDB()->commit();
    }

    public function updateRepositoryPermission($permission_id, array $writer_ids, array $rewinder_ids)
    {
        $this->getDB()->beginTransaction();
        try {
            $this->updatePermissionWriters($permission_id, $writer_ids);
            $this->updatePermissionRewinders($permission_id, $rewinder_ids);
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
            return false;
        }
        return $this->getDB()->commit();
    }

    private function updatePermissionWriters($permission_id, array $writer_ids)
    {
        $delete = 'DELETE FROM plugin_git_repository_fine_grained_permissions_writers
                   WHERE permission_id = ?';
        $this->getDB()->run($delete, $permission_id);
        $this->saveWriters($permission_id, $writer_ids);
    }

    private function updatePermissionRewinders($permission_id, array $rewinders_ids)
    {
        $delete = 'DELETE FROM plugin_git_repository_fine_grained_permissions_rewinders
                   WHERE permission_id = ?';
        $this->getDB()->run($delete, $permission_id);
        $this->saveRewinders($permission_id, $rewinders_ids);
    }

    public function updateDefaultPermission($permission_id, array $writer_ids, array $rewinder_ids)
    {
        $this->getDB()->beginTransaction();
        try {
            $this->updateDefaultPermissionWriters($permission_id, $writer_ids);
            $this->updateDefaultPermissionRewinders($permission_id, $rewinder_ids);
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
            return false;
        }
        return $this->getDB()->commit();
    }

    private function updateDefaultPermissionWriters($permission_id, array $writer_ids)
    {
        $delete = 'DELETE FROM plugin_git_default_fine_grained_permissions_writers
                   WHERE permission_id = ?';
        $this->getDB()->run($delete, $permission_id);
        $this->saveDefaultWriters($permission_id, $writer_ids);
    }

    private function updateDefaultPermissionRewinders($permission_id, array $rewinders_ids)
    {
        $delete = 'DELETE FROM plugin_git_default_fine_grained_permissions_rewinders
                   WHERE permission_id = ?';
        $this->getDB()->run($delete, $permission_id);
        $this->saveDefaultRewinders($permission_id, $rewinders_ids);
    }
}
