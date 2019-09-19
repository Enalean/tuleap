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

class RestrictedPluginDao extends RestrictedResourceDao
{

    public function getResourceAllowedProjectsTableName()
    {
        return 'project_plugin';
    }

    public function getResourceFieldName()
    {
        return 'plugin_id';
    }

    public function isResourceRestricted($plugin_id)
    {
        $plugin_id = $this->da->escapeInt($plugin_id);

        $sql = "SELECT * FROM plugin WHERE id = $plugin_id";

        $row = $this->retrieveFirstRow($sql);

        if ($row['prj_restricted'] == 1) {
            return true;
        }

        return false;
    }

    public function setResourceRestricted($plugin_id)
    {
        $plugin_id = $this->da->escapeInt($plugin_id);

        $sql = "UPDATE plugin SET prj_restricted = 1 WHERE id = $plugin_id";

        return $this->update($sql);
    }

    public function unsetResourceRestricted($plugin_id)
    {
        $plugin_id = $this->da->escapeInt($plugin_id);

        $sql = "UPDATE plugin SET prj_restricted = 0 WHERE id = $plugin_id";

        if ($this->update($sql)) {
            return $this->revokeAllProjectsFromResource($plugin_id);
        }

        return false;
    }

    public function searchAllowedProjectsOnResource($plugin_id)
    {
        $plugin_id = $this->da->escapeInt($plugin_id);

        $sql = "SELECT *
                FROM groups g
                INNER JOIN project_plugin pp ON g.group_id = pp.project_id
                WHERE pp.plugin_id = $plugin_id";

        return $this->retrieve($sql);
    }

    public function isPluginAllowedForProject($plugin_id, $project_id)
    {
        $plugin_id = $this->da->escapeInt($plugin_id);
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT project_id
                FROM project_plugin
                WHERE project_id = $project_id
                    AND plugin_id = $plugin_id";

        $dar = $this->retrieve($sql);
        if ($dar && ! $dar->isError() && $dar->rowCount() > 0) {
            return true;
        }

        return false;
    }
}
