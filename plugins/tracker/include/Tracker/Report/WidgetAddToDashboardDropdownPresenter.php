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

use PFUser;
use Project;

class WidgetAddToDashboardDropdownPresenter
{
    public $is_admin;
    public $my_dashboard_url;
    public $project_dashboard_url;
    public $project_dashboard;
    public $my_dashboard;
    public $dashboard;
    public $cancel_label;
    public $has_user_dashboard;
    public $error_no_dashboard;
    public $has_only_one_user_dashboard;
    public $has_project_dashboard;
    public $has_only_one_project_dashboard;

    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var WidgetMyDashboardPresenter[]
     */
    public $user_dashboards_presenter;
    /**
     * @var WidgetMyDashboardPresenter[]
     */
    public $project_dashboards_presenter;

    public function __construct(
        PFUser $user,
        Project $project,
        $my_dashboard_url,
        $project_dashboard_url,
        array $user_dashboards_presenter,
        array $project_dashboards_presenter
    ) {
        $this->is_admin                       = $user->isAdmin($project->getID());
        $this->my_dashboard_url               = $my_dashboard_url;
        $this->project_dashboard_url          = $project_dashboard_url;
        $this->user_dashboards_presenter      = $user_dashboards_presenter;
        $this->has_user_dashboard             = count($user_dashboards_presenter) > 0;
        $this->has_only_one_user_dashboard    = count($user_dashboards_presenter) === 1;
        $this->project_dashboards_presenter   = $project_dashboards_presenter;
        $this->has_project_dashboard          = count($project_dashboards_presenter) > 0;
        $this->has_only_one_project_dashboard = count($project_dashboards_presenter) === 1;

        $this->my_dashboard      = dgettext('tuleap-tracker', 'Add to my dashboard');
        $this->project_dashboard = dgettext('tuleap-tracker', 'Add to project dashboard');
        $this->dashboard         = dgettext('tuleap-tracker', 'Add to dashboard');
    }
}
