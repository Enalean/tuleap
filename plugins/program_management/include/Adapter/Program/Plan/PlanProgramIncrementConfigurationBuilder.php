<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\PlanCheckException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ConfigurationUserCanNotSeeProgramException;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\ProgramTracker;

class PlanProgramIncrementConfigurationBuilder implements BuildPlanProgramIncrementConfiguration
{
    /**
     * @var PlanStore
     */
    private $plan_store;
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;

    public function __construct(PlanStore $plan_store, \TrackerFactory $tracker_factory)
    {
        $this->plan_store      = $plan_store;
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @throws PlanTrackerException
     * @throws ProgramTrackerNotFoundException
     * @throws PlanCheckException
     */
    public function buildTrackerProgramIncrementFromProjectId(int $project_id, \PFUser $user): ProgramTracker
    {
        $program_increment_tracker_id = $this->plan_store->getProgramIncrementTrackerId($project_id);
        if (! $program_increment_tracker_id) {
            throw new ProgramTrackerNotFoundException($project_id);
        }
        $program_increment_tracker = $this->getValidTracker(
            $program_increment_tracker_id
        );

        if (! $program_increment_tracker->userCanView($user)) {
            throw new ConfigurationUserCanNotSeeProgramException(
                (int) $user->getId(),
                $program_increment_tracker->getId()
            );
        }

        return new ProgramTracker($program_increment_tracker);
    }

    /**
     * @throws ProgramNotFoundException
     */
    private function getValidTracker(int $tracker_id): \Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);

        if (! $tracker) {
            throw new ProgramNotFoundException($tracker_id);
        }

        return $tracker;
    }
}
