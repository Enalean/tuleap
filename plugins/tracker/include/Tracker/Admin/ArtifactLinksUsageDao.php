<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin;

use DataAccessObject;

class ArtifactLinksUsageDao extends DataAccessObject
{
    public function isProjectUsingArtifactLinkTypes($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT NULL
                FROM plugin_tracker_projects_use_artifactlink_types
                WHERE project_id = $project_id";

        $this->retrieve($sql);

        return $this->foundRows() > 0;
    }

    public function activateForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "REPLACE INTO plugin_tracker_projects_use_artifactlink_types
                VALUES ($project_id)";

        return $this->update($sql);
    }

    public function deactivateForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "DELETE FROM plugin_tracker_projects_use_artifactlink_types
                WHERE project_id = $project_id";

        return $this->update($sql);
    }

    public function isTypeDisabledInProject($project_id, $type_shortname)
    {
        $project_id     = $this->da->escapeInt($project_id);
        $type_shortname = $this->da->quoteSmart($type_shortname);

        $sql = "SELECT NULL
                FROM plugin_tracker_projects_unused_artifactlink_types
                WHERE project_id = $project_id
                  AND type_shortname = $type_shortname";

        $this->retrieve($sql);

        return $this->foundRows() > 0;
    }

    public function disableTypeInProject($project_id, $type_shortname)
    {
        $project_id     = $this->da->escapeInt($project_id);
        $type_shortname = $this->da->quoteSmart($type_shortname);

        $sql = "REPLACE INTO plugin_tracker_projects_unused_artifactlink_types (project_id, type_shortname)
                VALUES ($project_id, $type_shortname)";

        return $this->update($sql);
    }

    public function enableTypeInProject($project_id, $type_shortname)
    {
        $project_id     = $this->da->escapeInt($project_id);
        $type_shortname = $this->da->quoteSmart($type_shortname);

        $sql = "DELETE FROM plugin_tracker_projects_unused_artifactlink_types
                WHERE project_id = $project_id
                  AND type_shortname = $type_shortname";

        return $this->update($sql);
    }

    public function duplicate($template_id, $project_id)
    {
        if (
            ! $this->activateForProject($project_id) ||
            ! $this->duplicateTypesUsageInProject($template_id, $project_id)
        ) {
            return false;
        }
        return true;
    }

    private function duplicateTypesUsageInProject($template_id, $project_id)
    {
        $template_id = $this->da->escapeInt($template_id);
        $project_id  = $this->da->escapeInt($project_id);

        $sql = "INSERT INTO plugin_tracker_projects_unused_artifactlink_types (project_id, type_shortname)
                SELECT $project_id, type_shortname
                FROM plugin_tracker_projects_unused_artifactlink_types
                WHERE project_id = $template_id";

        return $this->update($sql);
    }
}
