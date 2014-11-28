<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class AgileDashboard_ConfigurationDao extends DataAccessObject {

    public function activateKanban($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "REPLACE INTO plugin_agiledashboard_configuration (project_id, kanban)
                VALUES ($project_id, 1)";

        return $this->update($sql);
    }

    public function deactivateKanban($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "REPLACE INTO plugin_agiledashboard_configuration (project_id, kanban)
                VALUES ($project_id, 0)";

        return $this->update($sql);

    }

    public function isKanbanActivated($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT kanban
                FROM plugin_agiledashboard_configuration
                WHERE project_id = $project_id";

        return $this->retrieve($sql);
    }

}
