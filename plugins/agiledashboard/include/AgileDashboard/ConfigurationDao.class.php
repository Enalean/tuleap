<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

class AgileDashboard_ConfigurationDao extends DataAccessObject
{
    public function updateConfiguration(
        $project_id,
        $scrum_is_activated,
    ) {
        $project_id         = $this->da->escapeInt($project_id);
        $scrum_is_activated = $this->da->escapeInt($scrum_is_activated);

        $sql = "REPLACE INTO plugin_agiledashboard_configuration (project_id, scrum)
                VALUES ($project_id, $scrum_is_activated)";

        return $this->update($sql);
    }

    public function duplicate($project_id, $template_id)
    {
        $project_id  = $this->da->escapeInt($project_id);
        $template_id = $this->da->escapeInt($template_id);

        $sql = "INSERT INTO plugin_agiledashboard_configuration (project_id, scrum)
                SELECT $project_id, scrum
                FROM plugin_agiledashboard_configuration
                WHERE project_id = $template_id";

        return $this->update($sql);
    }

    public function isScrumActivated($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT scrum
                FROM plugin_agiledashboard_configuration
                WHERE project_id = $project_id";

        return $this->retrieve($sql);
    }

    public function getScrumTitle($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT scrum_title
                FROM plugin_agiledashboard_configuration
                WHERE project_id = $project_id";

        return $this->retrieveFirstRow($sql);
    }
}
