<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class Git_Mirror_MirrorDao extends \Tuleap\DB\DataAccessObject
{
    /**
     * @return int | false
     */
    public function save($url, $hostname, $name)
    {
        $sql = 'INSERT INTO plugin_git_mirrors (url, hostname, name)
                VALUES(?, ?, ?)';

        try {
            $this->getDB()->run($sql, $url, $hostname, $name);
        } catch (PDOException $ex) {
            return false;
        }

        return (int) $this->getDB()->lastInsertId();
    }

    public function fetchAll()
    {
        return $this->getDB()->run('SELECT * FROM plugin_git_mirrors ORDER BY id');
    }

    public function fetchByIds($selected_mirror_ids)
    {
        $mirror_ids_in_condition = \ParagonIE\EasyDB\EasyStatement::open()->in('?*', $selected_mirror_ids);
        $sql                     = "SELECT * FROM plugin_git_mirrors WHERE id IN ($mirror_ids_in_condition)";

        return $this->getDB()->safeQuery($sql, $mirror_ids_in_condition->values());
    }

    /**
     * @return DataAccessObject
     */
    public function fetchAllRepositoryMirrors($repository_id)
    {
        $sql = 'SELECT plugin_git_mirrors.*
                FROM plugin_git_mirrors
                  INNER JOIN plugin_git_repository_mirrors ON plugin_git_mirrors.id = plugin_git_repository_mirrors.mirror_id
                  INNER JOIN plugin_git USING (repository_id)
                  LEFT JOIN plugin_git_restricted_mirrors ON plugin_git_restricted_mirrors.mirror_id = plugin_git_mirrors.id
                  LEFT JOIN plugin_git_restricted_mirrors_allowed_projects ON (
                     plugin_git_restricted_mirrors_allowed_projects.mirror_id = plugin_git_restricted_mirrors.mirror_id
                     AND plugin_git.project_id = plugin_git_restricted_mirrors_allowed_projects.project_id
                  )
                WHERE plugin_git_repository_mirrors.repository_id = ?
                  AND (plugin_git_restricted_mirrors.mirror_id IS NULL
                   OR plugin_git_restricted_mirrors_allowed_projects.project_id IS NOT NULL)
                ORDER BY id';

        return $this->getDB()->run($sql, $repository_id);
    }

    public function fetchAllProjectRepositoriesForMirror($mirror_id, $project_ids)
    {
        $project_ids_in_condition = \ParagonIE\EasyDB\EasyStatement::open()->in('?*', $project_ids);

        $sql = "SELECT DISTINCT g.*
                FROM plugin_git_repository_mirrors rm
                    INNER JOIN plugin_git g ON g.repository_id = rm.repository_id
                WHERE rm.mirror_id = ?
                AND g.project_id IN ($project_ids_in_condition)
                AND g.repository_deletion_date = '0000-00-00 00:00:00'";

        $parameters = array_merge([$mirror_id], $project_ids_in_condition->values());
        return $this->getDB()->safeQuery($sql, $parameters);
    }

    public function fetchAllProjectIdsConcernedByMirroring()
    {
        $sql = "SELECT DISTINCT g.project_id
                FROM plugin_git g
                  INNER JOIN plugin_git_repository_mirrors rm ON g.repository_id = rm.repository_id";

        return $this->getDB()->run($sql);
    }

    public function fetchAllProjectIdsConcernedByAMirror($mirror_id)
    {
        $sql = "SELECT DISTINCT g.project_id
                FROM plugin_git g
                  INNER JOIN plugin_git_repository_mirrors rm ON g.repository_id = rm.repository_id
                  INNER JOIN plugin_git_mirrors gm ON gm.id = rm.mirror_id
                WHERE rm.mirror_id = ?
                AND g.repository_deletion_date = '0000-00-00 00:00:00'";

        return $this->getDB()->run($sql, $mirror_id);
    }

    public function getNumberOfMirrorByHostname($hostname)
    {
        $sql = 'SELECT COUNT(*)
                FROM plugin_git_mirrors
                WHERE hostname = ?';

        return $this->getDB()->single($sql, [$hostname]);
    }

    public function getNumberOfMirrorByHostnameExcludingGivenId($hostname, $id)
    {
        $sql = 'SELECT COUNT(*)
                FROM plugin_git_mirrors
                WHERE hostname = ?
                AND id <> ?';

        return $this->getDB()->single($sql, [$hostname, $id]);
    }

    public function fetchAllForProject($project_id)
    {
        $sql = 'SELECT plugin_git_mirrors.*
                FROM plugin_git_mirrors
                    LEFT JOIN plugin_git_restricted_mirrors ON plugin_git_restricted_mirrors.mirror_id = plugin_git_mirrors.id
                    LEFT JOIN plugin_git_restricted_mirrors_allowed_projects ON (
                        plugin_git_restricted_mirrors_allowed_projects.mirror_id = plugin_git_restricted_mirrors.mirror_id
                        AND project_id = ?
                    )
                WHERE plugin_git_restricted_mirrors.mirror_id IS NULL
                   OR plugin_git_restricted_mirrors_allowed_projects.project_id IS NOT NULL
                ORDER BY id';

        return $this->getDB()->run($sql, $project_id);
    }

    public function fetchAllRepositoryMirroredByMirror($mirror_id)
    {
        $sql = 'SELECT plugin_git_repository_mirrors.repository_id, plugin_git.*, groups.group_name, groups.group_id
                FROM plugin_git_repository_mirrors
                    INNER JOIN plugin_git
                        ON plugin_git_repository_mirrors.repository_id = plugin_git.repository_id
                    INNER JOIN groups
                        ON plugin_git.project_id = groups.group_id
                    LEFT JOIN plugin_git_restricted_mirrors ON plugin_git_restricted_mirrors.mirror_id = plugin_git_repository_mirrors.mirror_id
                    LEFT JOIN plugin_git_restricted_mirrors_allowed_projects ON (
                        plugin_git_restricted_mirrors_allowed_projects.mirror_id = plugin_git_restricted_mirrors.mirror_id
                        AND plugin_git_restricted_mirrors_allowed_projects.project_id = plugin_git.project_id
                    )
                WHERE plugin_git_repository_mirrors.mirror_id = ?
                    AND plugin_git.repository_deletion_date IS NULL
                    AND (plugin_git_restricted_mirrors.mirror_id IS NULL
                         OR plugin_git_restricted_mirrors_allowed_projects.project_id IS NOT NULL)
                ORDER BY groups.group_name, plugin_git.repository_name';

        return $this->getDB()->run($sql, $mirror_id);
    }

    public function fetchAllRepositoryMirroredInProject($project_id)
    {
        $sql = 'SELECT plugin_git_repository_mirrors.*
                FROM plugin_git_repository_mirrors
                    INNER JOIN plugin_git
                        ON (
                            plugin_git_repository_mirrors.repository_id = plugin_git.repository_id
                            AND plugin_git.project_id = ?
                        )';

        return $this->getDB()->run($sql, $project_id);
    }

    public function unmirrorRepository($repository_id)
    {
        $sql = 'DELETE FROM plugin_git_repository_mirrors WHERE repository_id = ?';

        try {
            $this->getDB()->run($sql, $repository_id);
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }

    public function mirrorRepositoryTo($repository_id, $selected_mirror_ids)
    {
        $data_to_insert = [];
        foreach ($selected_mirror_ids as $mirror_id) {
            $data_to_insert[] = ['repository_id' => $repository_id, 'mirror_id' => $mirror_id];
        }

        try {
            $this->getDB()->insertMany('plugin_git_repository_mirrors', $data_to_insert);
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }

    public function fetch($id)
    {
        return $this->getDB()->row('SELECT * FROM plugin_git_mirrors WHERE id = ?', $id);
    }

    /**
     * @return bool
     */
    public function updateMirror($id, $url, $hostname, $name)
    {
        $sql = 'UPDATE plugin_git_mirrors
                SET url = ?, hostname = ?, name = ?
                WHERE id = ?';

        try {
            $this->getDB()->run($sql, $url, $hostname, $name, $id);
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function delete($id)
    {
        $sql = 'DELETE FROM plugin_git_mirrors
                WHERE id = ?';

        try {
            $this->getDB()->run($sql, $id);
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }
}
