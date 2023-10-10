<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\REST\v1;

use Project;
use Tracker;
use Tuleap\Tracker\RetrieveTrackersByProjectIdUserCanAdministrate;
use Tuleap\Tracker\RetrieveTrackersByProjectIdUserCanView;

final class ProjectTrackersRetriever
{
    public function __construct(
        private readonly RetrieveTrackersByProjectIdUserCanView $retrieve_trackers_user_can_see,
        private readonly RetrieveTrackersByProjectIdUserCanAdministrate $retrieve_trackers_user_can_administrate,
    ) {
    }

    /**
     * @return Tracker[]
     */
    public function getFilteredProjectTrackers(
        Project $project,
        \PFUser $user,
        bool $filter_on_tracker_administration_permission,
    ): array {
        if ($filter_on_tracker_administration_permission) {
            return $this->retrieve_trackers_user_can_administrate->getTrackersByProjectIdUserCanAdministrate(
                $project->getID(),
                $user,
            );
        }

        return $this->retrieve_trackers_user_can_see->getTrackersByProjectIdUserCanView(
            $project->getID(),
            $user
        );
    }
}
