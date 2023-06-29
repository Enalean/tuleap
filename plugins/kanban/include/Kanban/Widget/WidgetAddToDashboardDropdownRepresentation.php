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

use PFUser;
use Project;
use Tuleap\Dashboard\DashboardRepresentation;

class WidgetAddToDashboardDropdownRepresentation
{
    public bool $is_admin;
    public string $project_dashboard;
    public string $my_dashboard;
    public string $dashboard;

    /**
     * @var DashboardRepresentation[]
     */
    public array $project_dashboards = [];

    /**
     * @param DashboardRepresentation[] $user_dashboards
     * @param DashboardRepresentation[] $project_dashboards_representation
     */
    public function __construct(
        PFUser $user,
        Project $project,
        public readonly string $my_dashboard_url,
        public string $project_dashboard_url,
        public readonly array $user_dashboards,
        array $project_dashboards_representation,
    ) {
        $this->is_admin = $user->isAdmin((int) $project->getID());

        if ($this->is_admin) {
            $this->project_dashboards = $project_dashboards_representation;

            $this->project_dashboard = dgettext('tuleap-kanban', 'Add to project dashboard');
        } else {
            $this->project_dashboard_url = '';
        }

        $this->my_dashboard = dgettext('tuleap-kanban', 'Add to my dashboard');
        $this->dashboard    = dgettext('tuleap-kanban', 'Add to dashboard');
    }
}
