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

namespace Tuleap\AgileDashboard\Widget;

use CSRFSynchronizerToken;
use PFUser;
use Project;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Dashboard\User\UserDashboardRetriever;

class WidgetAddToDashboardDropdownBuilder
{
    /**
     * @var UserDashboardRetriever
     */
    private $user_dashboard_retriever;
    /**
     * @var ProjectDashboardRetriever
     */
    private $project_dashboard_retriever;

    public function __construct(
        UserDashboardRetriever $user_dashboard_retriever,
        ProjectDashboardRetriever $project_dashboard_retriever
    ) {
        $this->user_dashboard_retriever    = $user_dashboard_retriever;
        $this->project_dashboard_retriever = $project_dashboard_retriever;
    }

    public function build(PFUser $user, Project $project)
    {
        $my_dashboards_presenters      = $this->getAvailableDashboardsForUser($user);
        $project_dashboards_presenters = $this->getAvailableDashboardsForProject($project);

        return new WidgetAddToDashboardDropdownPresenter(
            $user,
            $project,
            $this->getAddToMyDashboardURL(),
            $this->getAddToProjectDashboardURL($project),
            $my_dashboards_presenters,
            $project_dashboards_presenters
        );
    }

    private function getAddToMyDashboardURL()
    {
        $csrf = new CSRFSynchronizerToken('/my/');
        return $this->getAddToDashboardURL(
            $csrf,
            MyKanban::NAME,
            UserDashboardController::DASHBOARD_TYPE
        );
    }

    private function getAddToProjectDashboardURL(Project $project)
    {
        $csrf = new CSRFSynchronizerToken('/project/');
        return $this->getAddToDashboardURL(
            $csrf,
            ProjectKanban::NAME,
            ProjectDashboardController::DASHBOARD_TYPE
        ) . '&group_id=' . urlencode($project->getID());
    }

    private function getAddToDashboardURL(
        CSRFSynchronizerToken $csrf,
        $widget_id,
        $type
    ) {
        return '/widgets/?' . http_build_query(
            array(
                'dashboard-type'      => $type,
                'action'              => 'add-widget',
                'widget-name'         => $widget_id,
                $csrf->getTokenName() => $csrf->getToken()
            )
        );
    }

    private function getAvailableDashboardsForUser(PFUser $user)
    {
        return $this->user_dashboard_retriever->getAllUserDashboards($user);
    }

    private function getAvailableDashboardsForProject(Project $project)
    {
        return $this->project_dashboard_retriever->getAllProjectDashboards($project);
    }
}
