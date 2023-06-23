<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation;
use Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation;
use Tuleap\AgileDashboard\REST\v1\PlanningRepresentation;
use Tuleap\Kanban\REST\v1\KanbanRepresentation;
use Tuleap\Kanban\REST\v1\KanbanColumnRepresentation;
use Tuleap\Kanban\REST\v1\KanbanItemPOSTRepresentation;
use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\Project\REST\ProjectResourceReference;

/**
 * Inject resource into restler
 */
class AgileDashboard_REST_ResourcesInjector
{
    public function populate(Luracast\Restler\Restler $restler)
    {
        $restler->addAPIClass('\\Tuleap\\AgileDashboard\\REST\\AgileDashboardProjectResource', ProjectRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\AgileDashboard\\REST\\v1\\MilestoneResource', MilestoneRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\AgileDashboard\\REST\\v1\\PlanningResource', PlanningRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\AgileDashboard\\REST\\v1\\BacklogItemResource', BacklogItemRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\Kanban\\REST\\v1\\KanbanResource', KanbanRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\Kanban\\REST\\v1\\KanbanColumnsResource', KanbanColumnRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\Kanban\\REST\\v1\\KanbanItemsResource', KanbanItemPOSTRepresentation::ROUTE);
    }

    public function declareProjectPlanningResource(array &$resources, Project $project)
    {
        $routes = [
            BacklogItemRepresentation::BACKLOG_ROUTE,
            MilestoneRepresentation::ROUTE,
            PlanningRepresentation::ROUTE,
        ];
        foreach ($routes as $route) {
            $resource_reference = new ProjectResourceReference();
            $resource_reference->build($project, $route);

            $resources[] = $resource_reference;
        }
    }
}
