<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Git_RestrictedMirrorDao extends RestrictedResourceDao
{

    public function getResourceAllowedProjectsTableName()
    {
        return 'plugin_git_restricted_mirrors_allowed_projects';
    }

    public function getResourceFieldName()
    {
        return 'mirror_id';
    }

    public function isResourceRestricted($mirror_id)
    {
        $mirror_id = $this->da->escapeInt($mirror_id);

        $sql = "SELECT * FROM plugin_git_restricted_mirrors WHERE mirror_id = $mirror_id";

        if ($this->retrieveFirstRow($sql)) {
            return true;
        }

        return false;
    }

    public function setResourceRestricted($mirror_id)
    {
        $mirror_id = $this->da->escapeInt($mirror_id);

        $sql = "REPLACE INTO plugin_git_restricted_mirrors VALUES ($mirror_id)";

        if ($this->update($sql)) {
            $sql = "REPLACE INTO plugin_git_restricted_mirrors_allowed_projects
                    SELECT DISTINCT $mirror_id, g.project_id
                    FROM plugin_git_repository_mirrors rm
                        INNER JOIN plugin_git g ON g.repository_id = rm.repository_id
                    WHERE rm.mirror_id = $mirror_id";

            return $this->update($sql);
        }

        return false;
    }

    public function unsetResourceRestricted($mirror_id)
    {
        $mirror_id = $this->da->escapeInt($mirror_id);

        $sql = "DELETE FROM plugin_git_restricted_mirrors WHERE mirror_id = $mirror_id";

        if ($this->update($sql)) {
            return $this->revokeAllProjectsFromResource($mirror_id);
        }

        return false;
    }

    public function searchAllowedProjectsOnResource($mirror_id)
    {
        $mirror_id = $this->da->escapeInt($mirror_id);

        $sql = "SELECT *
                FROM groups g
                INNER JOIN plugin_git_restricted_mirrors_allowed_projects rm ON g.group_id = rm.project_id
                WHERE rm.mirror_id = $mirror_id";

        return $this->retrieve($sql);
    }
}
