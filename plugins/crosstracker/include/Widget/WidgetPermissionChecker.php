<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

use PFUser;
use ProjectManager;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;

final readonly class WidgetPermissionChecker
{
    public function __construct(
        private SearchCrossTrackerWidget $cross_tracker_dao,
        private ProjectManager $project_manager,
    ) {
    }

    public function isUserWidgetAdmin(PFUser $user, int $widget_id): bool
    {
        $widget = $this->cross_tracker_dao->searchCrossTrackerWidgetDashboardById($widget_id);

        if (isset($widget['dashboard_type']) && $widget['dashboard_type'] === UserDashboardController::DASHBOARD_TYPE) {
            return $widget['user_id'] === (int) $user->getId();
        }

        if (isset($widget['dashboard_type']) && $widget['dashboard_type'] === ProjectDashboardController::DASHBOARD_TYPE) {
            $project = $this->project_manager->getProject($widget['project_id']);
            return $user->isAdmin($project->getID());
        }

        return false;
    }
}
