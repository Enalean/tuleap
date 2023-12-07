<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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

namespace Tuleap\Dashboard\Project;

use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\DB\DataAccessObject;

class ProjectDashboardDao extends DataAccessObject
{
    public function __construct(private readonly DashboardWidgetDao $widget_dao)
    {
        parent::__construct();
    }

    public function searchAllProjectDashboards($project_id)
    {
        $sql = "SELECT *
                FROM project_dashboards
                WHERE project_id = ?";

        return $this->getDB()->run($sql, $project_id);
    }

    public function save($project_id, $name): int
    {
        return (int) $this->getDB()->insertReturnId('project_dashboards', [
            'project_id' => $project_id,
            'name' => $name,
        ]);
    }

    public function searchById($dashboard_id): array
    {
        $sql = "SELECT *
                FROM project_dashboards
                WHERE id = ?";

        return $this->getDB()->row($sql, $dashboard_id);
    }

    public function searchByProjectIdAndName($project_id, $name): array
    {
        $sql = "SELECT *
                FROM project_dashboards
                WHERE project_id = ? AND name = ?";

        return $this->getDB()->run($sql, $project_id, $name);
    }

    public function edit($id, $name): void
    {
        $sql = "UPDATE
                project_dashboards
                SET name = ?
                WHERE id = ?";

        $this->getDB()->run($sql, $name, $id);
    }

    public function delete($project_id, $dashboard_id): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($project_id, $dashboard_id): void {
            $this->widget_dao->deleteAllWidgetsInProjectDashboardInsideTransaction($project_id, $dashboard_id);

            $this->getDB()->delete('project_dashboards', ['project_id' => $project_id, 'id' => $dashboard_id]);
        });
    }

    public function duplicateDashboard($template_id, $new_project_id, $template_dashboard_id): int
    {
        $sql = "INSERT INTO project_dashboards (project_id, name)
                SELECT ?, name
                FROM project_dashboards
                WHERE project_id = ?
                  AND id = ?";

        $this->getDB()->run($sql, $new_project_id, $template_id, $template_dashboard_id);

        return (int) $this->getDB()->lastInsertId();
    }
}
