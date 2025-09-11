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

final readonly class WidgetPermissionChecker
{
    public function __construct(
        private ProjectManager $project_manager,
        private RetrieveCrossTrackerWidget $cross_tracker_widget_retriever,
    ) {
    }

    public function isUserWidgetAdmin(PFUser $user, int $widget_id): bool
    {
        return $this->cross_tracker_widget_retriever->retrieveWidgetById($widget_id)
            ->match(
                fn (ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget) => $this->hasWidgetAdminRights($widget, $user),
                fn (): bool => false
            );
    }

    private function hasWidgetAdminRights(ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget, PFUser $user): bool
    {
        if ($widget instanceof UserCrossTrackerWidget) {
            return $widget->getUserId() === (int) $user->getId();
        }

        if ($widget instanceof ProjectCrossTrackerWidget) {
            $project = $this->project_manager->getProject($widget->getProjectId());
            return $user->isAdmin($project->getID());
        }

        return false;
    }
}
