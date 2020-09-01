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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone;

final class MilestoneTrackerCollection
{
    /**
     * @var \Tracker[]
     * @psalm-readonly
     */
    private $milestone_trackers;
    /**
     * @var \Tracker[]
     * @psalm-readonly
     */
    private $contributor_milestone_trackers;

    /**
     * @param \Tracker[] $milestone_trackers
     */
    public function __construct(\Project $aggregator_project, array $milestone_trackers)
    {
        $this->milestone_trackers             = $milestone_trackers;
        $this->contributor_milestone_trackers = self::extractContributorMilestoneTrackers(
            $aggregator_project,
            $milestone_trackers
        );
    }

    /**
     * @param \Tracker[] $milestone_trackers
     * @return \Tracker[]
     */
    private static function extractContributorMilestoneTrackers(\Project $aggregator_project, array $milestone_trackers): array
    {
        $contributor_trackers = [];
        foreach ($milestone_trackers as $milestone_tracker) {
            if ((int) $milestone_tracker->getGroupId() !== (int) $aggregator_project->getID()) {
                $contributor_trackers[] = $milestone_tracker;
            }
        }
        return $contributor_trackers;
    }

    /**
     * @return int[]
     * @psalm-mutation-free
     */
    public function getTrackerIds(): array
    {
        return array_map(
            function (\Tracker $tracker) {
                return (int) $tracker->getId();
            },
            $this->milestone_trackers
        );
    }

    /**
     * @return \Tracker[]
     * @psalm-mutation-free
     */
    public function getMilestoneTrackers(): array
    {
        return $this->milestone_trackers;
    }

    /**
     * @return \Tracker[]
     */
    public function getContributorMilestoneTrackers(): array
    {
        return $this->contributor_milestone_trackers;
    }

    public function canUserSubmitAnArtifactInAllContributorTrackers(\PFUser $user): bool
    {
        foreach ($this->contributor_milestone_trackers as $milestone_tracker) {
            if (! $milestone_tracker->userCanSubmitArtifact($user)) {
                return false;
            }
        }
        return true;
    }
}
