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

namespace Tuleap\Tracker\Report;

use CSRFSynchronizerToken;
use PFUser;
use Project;
use Tracker_Report_Renderer;
use Tracker_Widget_MyRenderer;
use Tracker_Widget_ProjectRenderer;
use Tuleap\Dashboard\User\UserDashboardRetriever;

class WidgetAddToDashboardDropdownBuilder
{
    /**
     * @var UserDashboardRetriever
     */
    private $user_dashboard_retriever;

    public function __construct(UserDashboardRetriever $user_dashboard_retriever)
    {
        $this->user_dashboard_retriever = $user_dashboard_retriever;
    }

    public function build(PFUser $user, Project $project, Tracker_Report_Renderer $renderer)
    {
        $my_dashboards_presenters = $this->getAvailableDashboardsForUser($user);

        return new WidgetAddToDashboardDropdownPresenter(
            $user,
            $project,
            $this->getAddToMyDashboardURL($renderer, $user),
            $this->getAddToProjectDashboardURL($renderer, $project),
            $my_dashboards_presenters
        );
    }

    private function getAddToMyDashboardURL(Tracker_Report_Renderer $renderer, PFUser $user)
    {
        return $this->getAddToDashboardURL(
            $renderer,
            'u' . $user->getId(),
            Tracker_Widget_MyRenderer::ID
        );
    }

    private function getAddToDashboardURL(\Tracker_Report_Renderer $renderer, $owner_id, $widget_id)
    {
        $csrf = new CSRFSynchronizerToken('widget_management');

        return '/widgets/updatelayout.php?' . http_build_query(
            array(
                'owner'                     => $owner_id,
                'action'                    => 'widget',
                'renderer'                  => array(
                    'title'       => $renderer->name . ' for ' . $renderer->report->name,
                    'renderer_id' => $renderer->id
                ),
                'name'                      => array(
                    $widget_id => array(
                        'add' => 1
                    )
                ),
                $csrf->getTokenName() => $csrf->getToken()
            )
        );
    }

    private function getAddToProjectDashboardURL(Tracker_Report_Renderer $renderer, Project $project)
    {
        return $this->getAddToDashboardURL(
            $renderer,
            'g' . $project->getGroupId(),
            Tracker_Widget_ProjectRenderer::ID
        );
    }

    private function getAvailableDashboardsForUser(PFUser $user)
    {
        return $this->user_dashboard_retriever->getAllUserDashboards($user);
    }
}
