<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker;

final class ProjectIncrementsTrackerCollection
{
    /**
     * @var \Tracker[]
     * @psalm-readonly
     */
    private $project_increments;

    /**
     * @param \Tracker[] $project_increment_tracker
     */
    public function __construct(array $project_increment_tracker)
    {
        $this->project_increments = $project_increment_tracker;
    }

    /**
     * @return int[]
     * @psalm-mutation-free
     */
    public function getTrackerIds(): array
    {
        return array_map(
            static function (\Tracker $tracker) {
                return $tracker->getId();
            },
            $this->project_increments
        );
    }

    /**
     * @return \Tracker[]
     * @psalm-mutation-free
     */
    public function getProjectIncrementTrackers(): array
    {
        return $this->project_increments;
    }

    public function canUserSubmitAnArtifactInAllTrackers(\PFUser $user): bool
    {
        foreach ($this->project_increments as $project_increment_tracker) {
            if (! $project_increment_tracker->userCanSubmitArtifact($user)) {
                return false;
            }
        }
        return true;
    }
}
