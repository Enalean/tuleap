<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\SecondPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\ProgramManagementProject;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrievePlanningMilestoneTrackerStub implements RetrievePlanningMilestoneTracker
{
    /**
     * @var int[]
     */
    private array $tracker_ids;
    /**
     * @var ProgramTracker[]
     */
    private array $trackers;
    private bool $has_no_planning;

    /**
     * @param int[]      $tracker_ids
     * @param ProgramTracker[] $trackers
     */
    private function __construct(array $tracker_ids, array $trackers, bool $has_no_planning)
    {
        $this->tracker_ids     = $tracker_ids;
        $this->trackers        = $trackers;
        $this->has_no_planning = $has_no_planning;
    }

    public function retrieveRootPlanningMilestoneTracker(ProgramManagementProject $project, UserIdentifier $user_identifier): ProgramTracker
    {
        if ($this->has_no_planning) {
            throw new TopPlanningNotFoundInProjectException($project->getId());
        }
        if (count($this->trackers) > 0) {
            return array_shift($this->trackers);
        }
        if (count($this->tracker_ids) > 0) {
            $tracker_id = array_shift($this->tracker_ids);
            return ProgramTrackerStub::withId($tracker_id);
        }

        throw new \LogicException('No milestone tracker configured');
    }

    public function retrieveSecondPlanningMilestoneTracker(ProgramManagementProject $project, UserIdentifier $user_identifier): ProgramTracker
    {
        if ($this->has_no_planning) {
            throw new SecondPlanningNotFoundInProjectException($project->getId());
        }
        if (count($this->trackers) > 0) {
            return array_shift($this->trackers);
        }
        if (count($this->tracker_ids) > 0) {
            $tracker_id = array_shift($this->tracker_ids);
            return ProgramTrackerStub::withId($tracker_id);
        }

        throw new \LogicException('No milestone tracker configured');
    }

    public static function withValidTrackerIds(int ...$tracker_ids): self
    {
        return new self($tracker_ids, [], false);
    }

    public static function withValidTrackers(ProgramTracker ...$trackers): self
    {
        return new self([], $trackers, false);
    }

    public static function withNoPlanning(): self
    {
        return new self([], [], true);
    }
}
