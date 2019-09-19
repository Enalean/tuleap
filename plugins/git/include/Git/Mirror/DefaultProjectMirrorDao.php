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

use Tuleap\DB\DataAccessObject;
use ParagonIE\EasyDB\EasyStatement;

class DefaultProjectMirrorDao extends DataAccessObject
{

    public function removeAllToProject($project_id)
    {
        $sql = 'DELETE FROM plugin_git_default_project_mirrors
                WHERE project_id = ?';

        try {
            $this->getDB()->run($sql, $project_id);
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }

    public function addDefaultMirrorsToProject($project_id, array $selected_mirror_ids)
    {
        $data_to_insert = [];
        foreach ($selected_mirror_ids as $mirror_id) {
            $data_to_insert[] = ['project_id' => $project_id, 'mirror_id' => $mirror_id];
        }

        try {
            $this->getDB()->insertMany('plugin_git_default_project_mirrors', $data_to_insert);
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }

    public function getDefaultMirrorIdsForProject($project_id)
    {
        $sql = 'SELECT mirror_id AS id
                FROM plugin_git_default_project_mirrors
                WHERE project_id = ?';

        $result     = $this->getDB()->run($sql, $project_id);
        $mirror_ids = [];
        foreach ($result as $row) {
            $mirror_ids[] = $row['id'];
        }

        return $mirror_ids;
    }

    public function deleteFromDefaultMirrors($deleted_mirror_id)
    {
        $sql = 'DELETE FROM plugin_git_default_project_mirrors
                WHERE mirror_id = ?';

        try {
            $this->getDB()->run($sql, $deleted_mirror_id);
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }

    public function deleteFromDefaultMirrorsInProjects($mirror_id, array $project_ids)
    {
        $project_ids_in_condition = EasyStatement::open()->in('?*', $project_ids);

        $sql = "DELETE FROM plugin_git_default_project_mirrors
                WHERE mirror_id = ?
                AND project_id IN ($project_ids_in_condition)";

        try {
            $parameters = array_merge([$mirror_id], $project_ids_in_condition->values());
            $this->getDB()->safeQuery($sql, $parameters);
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function duplicate($template_project_id, $new_project_id)
    {
        $sql = "INSERT INTO plugin_git_default_project_mirrors (project_id, mirror_id)
                SELECT ?, plugin_git_default_project_mirrors.mirror_id
                FROM plugin_git_default_project_mirrors
                    LEFT JOIN plugin_git_restricted_mirrors
                    ON (plugin_git_default_project_mirrors.mirror_id = plugin_git_restricted_mirrors.mirror_id)
                WHERE project_id = ?
                AND plugin_git_restricted_mirrors.mirror_id IS NULL";

        try {
            $this->getDB()->run($sql, $new_project_id, $template_project_id);
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }
}
