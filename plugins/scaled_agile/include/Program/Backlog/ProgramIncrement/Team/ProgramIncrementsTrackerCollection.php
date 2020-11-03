<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Team;

use Tuleap\ScaledAgile\TrackerData;

final class ProgramIncrementsTrackerCollection
{
    /**
     * @var TrackerData[]
     * @psalm-readonly
     */
    private $program_increments_tracker_collection;

    /**
     * @param TrackerData[] $program_increment_tracker
     */
    public function __construct(array $program_increment_tracker)
    {
        $this->program_increments_tracker_collection = $program_increment_tracker;
    }

    /**
     * @return int[]
     * @psalm-mutation-free
     */
    public function getTrackerIds(): array
    {
        return array_map(
            static function (TrackerData $tracker) {
                return $tracker->getTrackerId();
            },
            $this->program_increments_tracker_collection
        );
    }

    /**
     * @return TrackerData[]
     * @psalm-mutation-free
     */
    public function getProgramIncrementTrackers(): array
    {
        return $this->program_increments_tracker_collection;
    }

    public function canUserSubmitAnArtifactInAllTrackers(\PFUser $user): bool
    {
        foreach ($this->program_increments_tracker_collection as $program_increment_tracker) {
            if (! $program_increment_tracker->userCanSubmitArtifact($user)) {
                return false;
            }
        }
        return true;
    }
}
