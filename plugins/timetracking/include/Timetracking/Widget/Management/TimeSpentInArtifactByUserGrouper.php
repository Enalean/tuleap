<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\Management;

use PFUser;
use WeakMap;

final readonly class TimeSpentInArtifactByUserGrouper implements GroupTimeSpentInArtifactByUser
{
    #[\Override]
    public function groupByUser(array $times): array
    {
        $grouped = new WeakMap();

        foreach ($times as $time) {
            $grouped[$time->user] ??= [];

            $project                                 = $time->artifact->getTracker()->getProject();
            $already_aggregated_minutes              = $grouped[$time->user][$project->getID()]->minutes ?? 0;
            $grouped[$time->user][$project->getID()] = new TimeSpentInProject(
                $project,
                $time->minutes + $already_aggregated_minutes
            );
        }

        $result = [];
        foreach ($grouped as $user => $project_times) {
            assert($user instanceof PFUser);
            $result[] = new UserTimes($user, array_values($project_times));
        }

        return $result;
    }
}
