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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use Project;

class AllowedProjectsDao extends \DataAccessObject {

    public function create(Project $project) {
        $project_id = $this->da->escapeInt($project->getId());

        $sql = "REPLACE INTO plugin_tracker_artifactlink_natures_allowed_projects (project_id) VALUES ($project_id)";

        return $this->update($sql);
    }

    public function removeByProjectIds(array $project_ids) {
        $project_ids = $this->da->escapeIntImplode($project_ids);

        $sql = "DELETE FROM plugin_tracker_artifactlink_natures_allowed_projects WHERE project_id IN ($project_ids)";

        return $this->update($sql);
    }

    public function searchAll() {
        $sql = "SELECT * FROM plugin_tracker_artifactlink_natures_allowed_projects";

        return $this->retrieve($sql);
    }
}
