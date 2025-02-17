<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Tuleap\CrossTracker\Widget\SearchCrossTrackerWidget;
use Tuleap\include\CheckUserCanAccessProject;
use Tuleap\include\CheckUserCanAccessProjectAndIsAdmin;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\REST\ProjectAuthorization;

final readonly class UserIsAllowedToSeeWidgetChecker
{
    public function __construct(
        private SearchCrossTrackerWidget $cross_tracker_dao,
        private ProjectByIDFactory $project_manager,
        private CheckUserCanAccessProject&CheckUserCanAccessProjectAndIsAdmin $url_verification,
    ) {
    }

    /**
     * @throws RestException
     */
    public function checkUserIsAllowedToSeeWidget(PFUser $user, int $widget_id): void
    {
        $widget = $this->cross_tracker_dao->searchCrossTrackerWidgetDashboardById($widget_id);
        if ($widget !== null && $widget['dashboard_type'] === 'user' && $widget['user_id'] !== (int) $user->getId()) {
            throw new RestException(404);
        }

        if ($widget !== null && $widget['dashboard_type'] === 'project') {
            $project = $this->project_manager->getProjectById($widget['project_id']);
            try {
                ProjectAuthorization::userCanAccessProject($user, $project, $this->url_verification);
            } catch (RestException $e) {
                if ($e->getCode() === 403) {
                    throw new RestException(404);
                }
                throw $e;
            }
        }
    }

    /**
     * @throws RestException
     */
    public function checkUserIsAllowedToUpdateWidget(PFUser $user, int $widget_id): void
    {
        $widget = $this->cross_tracker_dao->searchCrossTrackerWidgetDashboardById($widget_id);
        if ($widget !== null && $widget['dashboard_type'] === 'user' && $widget['user_id'] !== (int) $user->getId()) {
            throw new RestException(404);
        }

        if ($widget !== null && $widget['dashboard_type'] === 'project') {
            $project = $this->project_manager->getProjectById($widget['project_id']);
            try {
                ProjectAuthorization::userCanAccessProjectAndIsProjectAdmin($user, $project, $this->url_verification);
            } catch (RestException $e) {
                if ($e->getCode() === 403) {
                    throw new RestException(404);
                }
                throw $e;
            }
        }
    }
}
