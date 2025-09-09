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
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\RetrieveCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;
use Tuleap\include\CheckUserCanAccessProject;
use Tuleap\include\CheckUserCanAccessProjectAndIsAdmin;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\REST\ProjectAuthorization;

final readonly class UserIsAllowedToSeeWidgetChecker
{
    public function __construct(
        private ProjectByIDFactory $project_manager,
        private CheckUserCanAccessProject&CheckUserCanAccessProjectAndIsAdmin $url_verification,
        private RetrieveCrossTrackerWidget $cross_tracker_widget_retriever,
    ) {
    }

    /**
     * @throws RestException
     */
    public function checkUserIsAllowedToSeeWidget(PFUser $user, int $widget_id): void
    {
        $widget = $this->cross_tracker_widget_retriever->retrieveWidgetById($widget_id);
        if ($widget === null) {
            return;
        }

        if ($widget instanceof UserCrossTrackerWidget && $widget->getUserId() !== (int) $user->getId()) {
            throw new RestException(404);
        }

        if ($widget instanceof ProjectCrossTrackerWidget) {
            $project = $this->project_manager->getProjectById($widget->getProjectId());
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
        $widget = $this->cross_tracker_widget_retriever->retrieveWidgetById($widget_id);
        if ($widget === null) {
            return;
        }
        if ($widget instanceof UserCrossTrackerWidget && $widget->getUserId() !== (int) $user->getId()) {
            throw new RestException(404);
        }

        if ($widget instanceof ProjectCrossTrackerWidget) {
            $project = $this->project_manager->getProjectById($widget->getProjectId());
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
