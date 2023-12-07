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

use Project;
use Tuleap\Option\Option;

class ProjectDashboardRetriever implements IRetrieveDashboards
{
    /**
     * @var ProjectDashboardDao
     */
    private $dao;

    public function __construct(ProjectDashboardDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return ProjectDashboard[]
     */
    public function getAllProjectDashboards(Project $project): array
    {
        $project_dashboards = [];

        foreach ($this->dao->searchAllProjectDashboards($project->getID()) as $row) {
            $project_dashboards[] = $this->instantiateFromRow($row);
        }

        return $project_dashboards;
    }

    /**
     * @return Option<ProjectDashboard>
     */
    public function getProjectDashboardById(int $dashboard_id): Option
    {
        $dashboard_row = $this->dao->searchById($dashboard_id);
        if (! $dashboard_row) {
            return Option::nothing(ProjectDashboard::class);
        }

        return Option::fromValue($this->instantiateFromRow($dashboard_row));
    }

    /**
     * @return ProjectDashboard
     */
    private function instantiateFromRow(array $project_dashboards)
    {
        return new ProjectDashboard(
            $project_dashboards['id'],
            $project_dashboards['project_id'],
            $project_dashboards['name']
        );
    }
}
