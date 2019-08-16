<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\FormElement\Burnup;

use Tuleap\DB\DataAccessObject;

class ProjectsCountModeDao extends DataAccessObject
{
    public function isBurnupInCountMode(int $project_id): bool
    {
        $sql = "SELECT COUNT(*) FROM plugin_agiledashboard_burnup_projects_count_mode WHERE project_id = ?";

        return $this->getDB()->single($sql, [$project_id]) > 0;
    }

    public function enableBurnupCountMode(int $project_id): void
    {
        $sql = "INSERT INTO plugin_agiledashboard_burnup_projects_count_mode (project_id) VALUES (?)
                ON DUPLICATE KEY UPDATE project_id = ?";

        $this->getDB()->cell($sql, $project_id, $project_id);
    }

    public function disableBurnupCountMode(int $project_id): void
    {
        $sql = "DELETE FROM plugin_agiledashboard_burnup_projects_count_mode WHERE project_id = ?";

        $this->getDB()->single($sql, [$project_id]);
    }

    public function inheritBurnupCountMode(int $template_project_id, int $project_id): void
    {
        $sql = "INSERT INTO plugin_agiledashboard_burnup_projects_count_mode
                SELECT ?
                FROM plugin_agiledashboard_burnup_projects_count_mode
                WHERE project_id = ?";

        $this->getDB()->single($sql, [$project_id, $template_project_id]);
    }
}
