<?php
/**
 * Copyright (c) Enalean, 2014-2015. All rights reserved
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

class Git_Mirror_MirrorDao extends DataAccessObject{

    /**
     * @return int | false
     */
    public function save($url, $name) {
        $url  = $this->da->quoteSmart($url);
        $name = $this->da->quoteSmart($name);

        $sql = "INSERT INTO plugin_git_mirrors (url, name)
                VALUES($url, $name)";

        return $this->updateAndGetLastId($sql);
    }

    /**
     * @return DataAccessObject
     */
    public function fetchAll() {
        $sql = "SELECT * FROM plugin_git_mirrors ORDER BY id";

        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessObject
     */
    public function fetchByIds($selected_mirror_ids) {
        $sql = "SELECT * FROM plugin_git_mirrors WHERE id IN (" . $this->da->escapeIntImplode($selected_mirror_ids) . ")";

        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessObject
     */
    public function fetchAllRepositoryMirrors($repository_id) {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT plugin_git_mirrors.*
                FROM plugin_git_mirrors
                  INNER JOIN plugin_git_repository_mirrors ON plugin_git_mirrors.id = plugin_git_repository_mirrors.mirror_id
                  INNER JOIN plugin_git USING (repository_id)
                  LEFT JOIN plugin_git_restricted_mirrors ON plugin_git_restricted_mirrors.mirror_id = plugin_git_mirrors.id
                  LEFT JOIN plugin_git_restricted_mirrors_allowed_projects ON (
                     plugin_git_restricted_mirrors_allowed_projects.mirror_id = plugin_git_restricted_mirrors.mirror_id
                     AND plugin_git.project_id = plugin_git_restricted_mirrors_allowed_projects.project_id
                  )
                WHERE plugin_git_repository_mirrors.repository_id = $repository_id
                  AND (plugin_git_restricted_mirrors.mirror_id IS NULL
                   OR plugin_git_restricted_mirrors_allowed_projects.project_id IS NOT NULL)
                ORDER BY id";

        return $this->retrieve($sql);
    }

    public function fetchAllForProject($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT plugin_git_mirrors.*
                FROM plugin_git_mirrors
                    LEFT JOIN plugin_git_restricted_mirrors ON plugin_git_restricted_mirrors.mirror_id = plugin_git_mirrors.id
                    LEFT JOIN plugin_git_restricted_mirrors_allowed_projects ON (
                        plugin_git_restricted_mirrors_allowed_projects.mirror_id = plugin_git_restricted_mirrors.mirror_id
                        AND project_id = $project_id
                    )
                WHERE plugin_git_restricted_mirrors.mirror_id IS NULL
                   OR plugin_git_restricted_mirrors_allowed_projects.project_id IS NOT NULL
                ORDER BY id";

        return $this->retrieve($sql);
    }

    public function fetchAllRepositoryMirroredByMirror($mirror_id) {
        $mirror_id = $this->da->escapeInt($mirror_id);

        $sql = "SELECT plugin_git_repository_mirrors.repository_id, plugin_git.*, groups.group_name, groups.group_id
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
                WHERE plugin_git_repository_mirrors.mirror_id = $mirror_id
                    AND plugin_git.repository_deletion_date IS NULL
                    AND (plugin_git_restricted_mirrors.mirror_id IS NULL
                         OR plugin_git_restricted_mirrors_allowed_projects.project_id IS NOT NULL)
                ORDER BY groups.group_name, plugin_git.repository_name";

        return $this->retrieve($sql);
    }

    public function unmirrorRepository($repository_id) {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "DELETE FROM plugin_git_repository_mirrors WHERE repository_id = " . $repository_id;

        return $this->update($sql);
    }

    public function mirrorRepositoryTo($repository_id, $selected_mirror_ids) {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = 'INSERT INTO plugin_git_repository_mirrors (repository_id, mirror_id) VALUES ';

        $values = array();
        foreach ($selected_mirror_ids as $selected_mirror_id) {
            $selected_mirror_id = $this->da->escapeInt($selected_mirror_id);
            $values[]           = "($repository_id, $selected_mirror_id)";
        }

        $sql .= implode(', ', $values);

        return $this->update($sql);
    }

    /**
     * @return DataAccessObject
     */
    public function fetch($id) {
        $id  = $this->da->escapeInt($id);

        $sql = "SELECT * FROM plugin_git_mirrors WHERE id = $id";
        return $this->retrieveFirstRow($sql);
    }

    /**
     * @return bool
     */
    public function updateMirror($id, $url, $name) {
        $url  = $this->da->quoteSmart($url);
        $name = $this->da->quoteSmart($name);

        $sql = "UPDATE plugin_git_mirrors
                SET url = $url, name = $name
                WHERE id = $id";

        return $this->update($sql);
    }

    /**
     * @return bool
     */
    public function delete($id) {
        $id  = $this->da->escapeInt($id);

        $sql = "DELETE FROM plugin_git_mirrors
                WHERE id = $id";

        return $this->update($sql);
    }
}
