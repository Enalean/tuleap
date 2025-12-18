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
use Tuleap\Option\Option;
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
        $this->getWidgetUserCanSee($user, $widget_id)
            ->orElse(
                fn () => throw new RestException(404, 'Widget not found')
            );
    }

    /**
     * @return Option<ProjectCrossTrackerWidget>|Option<UserCrossTrackerWidget>
     */
    public function getWidgetUserCanSee(PFUser $user, int $widget_id): Option
    {
        return $this->cross_tracker_widget_retriever->retrieveWidgetById($widget_id)->match(
            /**
             * @psalm-return Option<ProjectCrossTrackerWidget>|Option<UserCrossTrackerWidget>
             */
            function (ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget) use ($user): Option {
                try {
                    $this->validateUserAccessToWidget(
                        $user,
                        $widget,
                        fn(PFUser $user, \Project $project) => ProjectAuthorization::userCanAccessProject(
                            $user,
                            $project,
                            $this->url_verification
                        )
                    );
                    return Option::fromValue($widget);
                } catch (RestException) {
                    return Option::nothing(ProjectCrossTrackerWidget::class);
                }
            },
            fn () => Option::nothing(ProjectCrossTrackerWidget::class),
        );
    }

    /**
     * @throws RestException
     */
    public function checkUserIsAllowedToUpdateWidget(PFUser $user, int $widget_id): void
    {
        $this->cross_tracker_widget_retriever->retrieveWidgetById($widget_id)
            ->match(
                function (ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget) use ($user): void {
                    $this->validateUserAccessToWidget(
                        $user,
                        $widget,
                        fn (PFUser $user, \Project $project) => ProjectAuthorization::userCanAccessProjectAndIsProjectAdmin(
                            $user,
                            $project,
                            $this->url_verification
                        )
                    );
                },
                fn () => null
            );
    }

    private function validateUserAccessToWidget(
        PFUser $user,
        ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget,
        callable $authorization_check,
    ): void {
        if ($widget instanceof UserCrossTrackerWidget) {
            if ($widget->getUserId() !== (int) $user->getId()) {
                throw new RestException(404);
            }
            return;
        }

        $project = $this->project_manager->getProjectById($widget->getProjectId());
        try {
            $authorization_check($user, $project);
        } catch (RestException $e) {
            if ($e->getCode() === 403) {
                throw new RestException(404);
            }
            throw $e;
        }
    }
}
