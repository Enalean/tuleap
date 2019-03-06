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

use PFUser;
use Project;
use Tuleap\Dashboard\DashboardRepresentation;

class WidgetAddToDashboardDropdownRepresentation
{
    public $is_admin;
    public $my_dashboard_url;
    public $project_dashboard_url;
    public $project_dashboard;
    public $my_dashboard;
    public $dashboard;
    public $cancel_label;

    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var DashboardRepresentation[]
     */
    public $user_dashboards;
    /**
     * @var DashboardRepresentation[]
     */
    public $project_dashboards;

    public function __construct(
        PFUser $user,
        Project $project,
        $my_dashboard_url,
        $project_dashboard_url,
        array $user_dashboards_representation,
        array $project_dashboards_representation
    ) {
        $this->is_admin = $user->isAdmin($project->getID());

        if ($this->is_admin) {
            $this->project_dashboard_url = $project_dashboard_url;
            $this->project_dashboards    = $project_dashboards_representation;

            $this->project_dashboard = dgettext('tuleap-agiledashboard', 'Add to project dashboard');
        }

        $this->my_dashboard_url = $my_dashboard_url;
        $this->user_dashboards  = $user_dashboards_representation;

        $this->my_dashboard = dgettext('tuleap-agiledashboard', 'Add to my dashboard');
        $this->dashboard    = dgettext('tuleap-agiledashboard', 'Add to dashboard');
    }
}
