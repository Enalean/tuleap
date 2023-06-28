<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\Widget;

use AgileDashboard_Kanban;
use CSRFSynchronizerToken;
use PFUser;
use Project;
use Tuleap\Dashboard\DashboardRepresentation;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Dashboard\User\UserDashboardRetriever;

class WidgetAddToDashboardDropdownRepresentationBuilder
{
    public function __construct(
        private readonly UserDashboardRetriever $user_dashboard_retriever,
        private readonly ProjectDashboardRetriever $project_dashboard_retriever,
    ) {
    }

    public function build(AgileDashboard_Kanban $kanban, PFUser $user, Project $project): WidgetAddToDashboardDropdownRepresentation
    {
        $my_dashboards_presenters      = $this->getAvailableDashboardsForUser($user);
        $project_dashboards_presenters = $this->getAvailableDashboardsForProject($project);

        return new WidgetAddToDashboardDropdownRepresentation(
            $user,
            $project,
            $this->getAddToMyDashboardURL($kanban),
            $this->getAddToProjectDashboardURL($kanban, $project),
            $my_dashboards_presenters,
            $project_dashboards_presenters
        );
    }

    private function getAddToMyDashboardURL(AgileDashboard_Kanban $kanban): string
    {
        $csrf = new CSRFSynchronizerToken('/my/');
        return $this->getAddToDashboardURL(
            $csrf,
            $kanban,
            MyKanban::NAME,
            UserDashboardController::DASHBOARD_TYPE
        );
    }

    private function getAddToProjectDashboardURL(AgileDashboard_Kanban $kanban, Project $project): string
    {
        $csrf = new CSRFSynchronizerToken('/project/');
        return $this->getAddToDashboardURL(
            $csrf,
            $kanban,
            ProjectKanban::NAME,
            ProjectDashboardController::DASHBOARD_TYPE
        ) . '&group_id=' . urlencode((string) $project->getID());
    }

    private function getAddToDashboardURL(
        CSRFSynchronizerToken $csrf,
        AgileDashboard_Kanban $kanban,
        string $widget_id,
        string $type,
    ): string {
        return '/widgets/?' . http_build_query(
            [
                'dashboard-type'      => $type,
                'action'              => 'add-widget',
                'kanban'              => [
                    'title' => $kanban->getName(),
                    'id'    => $kanban->getId(),
                ],
                'widget-name'         => $widget_id,
                $csrf->getTokenName() => $csrf->getToken(),
            ]
        );
    }

    /**
     * @return DashboardRepresentation[]
     */
    private function getAvailableDashboardsForUser(PFUser $user): array
    {
        $user_dashboards_representation = [];
        $user_dashboards                = $this->user_dashboard_retriever->getAllUserDashboards($user);
        foreach ($user_dashboards as $user_dashboard) {
            $user_dashboards_representation[] = new DashboardRepresentation($user_dashboard->getId(), $user_dashboard->getName());
        }

        return $user_dashboards_representation;
    }

    /**
     * @return DashboardRepresentation[]
     */
    private function getAvailableDashboardsForProject(Project $project): array
    {
        $project_dashboards_representation = [];
        $project_dashboards                = $this->project_dashboard_retriever->getAllProjectDashboards($project);
        foreach ($project_dashboards as $user_dashboard) {
            $project_dashboards_representation[] = new DashboardRepresentation($user_dashboard->getId(), $user_dashboard->getName());
        }

        return $project_dashboards_representation;
    }
}
