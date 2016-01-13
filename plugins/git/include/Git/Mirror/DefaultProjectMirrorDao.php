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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class DefaultProjectMirrorDao extends DataAccessObject {

    public function removeAllToProject($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "DELETE FROM plugin_git_default_project_mirrors
                WHERE project_id = $project_id";

        return $this->update($sql);
    }

    public function addDefaultMirrorsToProject($project_id, array $selected_mirror_ids) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "INSERT INTO plugin_git_default_project_mirrors (project_id, mirror_id)
                VALUES ";

        $values = array();
        foreach ($selected_mirror_ids as $mirror_id) {
            $mirror_id = $this->da->escapeInt($mirror_id);
            $values[]  = "($project_id, $mirror_id)";
        }

        $sql .= implode(', ', $values);

        return $this->update($sql);
    }

    public function getDefaultMirrorIdsForProject($project_id) {
         $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT mirror_id AS id
                FROM plugin_git_default_project_mirrors
                WHERE project_id = $project_id";

        return $this->retrieveIds($sql);
    }

}