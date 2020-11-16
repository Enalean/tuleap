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

namespace Tuleap\ScaledAgile\Program\Plan;

use Tuleap\ScaledAgile\Adapter\Plan\PlannableTrackerCannotBeEmptyException;
use Tuleap\ScaledAgile\Adapter\Plan\PlanTrackerDoesNotBelongToProjectException;
use Tuleap\ScaledAgile\Adapter\Plan\PlanTrackerNotFoundException;
use Tuleap\ScaledAgile\Adapter\Plan\ProgramAccessException;
use Tuleap\ScaledAgile\Adapter\Plan\ProjectIsNotAProgramException;

final class PlanCreator implements CreatePlan
{
    /**
     * @var BuildProgram
     */
    private $program_build;
    /**
     * @var BuildTracker
     */
    private $build_tracker;
    /**
     * @var PlanStore
     */
    private $plan_store;

    public function __construct(BuildProgram $program_build, BuildTracker $build_tracker, PlanStore $plan_store)
    {
        $this->program_build = $program_build;
        $this->build_tracker = $build_tracker;
        $this->plan_store    = $plan_store;
    }

    /**
     * @throws PlanTrackerDoesNotBelongToProjectException
     * @throws PlanTrackerNotFoundException
     * @throws ProjectIsNotAProgramException
     * @throws ProgramAccessException
     * @throws PlannableTrackerCannotBeEmptyException
     * @throws CannotPlanIntoItselfException
     */
    public function create(\PFUser $user, int $project_id, int $program_increment_id, array $trackers_id): void
    {
        if (in_array($program_increment_id, $trackers_id)) {
            throw new CannotPlanIntoItselfException();
        }
        $program_project       = $this->program_build->buildProgramProject($project_id, $user);
        $program_tracker       = $this->build_tracker->buildProgramIncrementTracker(
            $program_increment_id,
            $program_project->getId()
        );
        $plannable_tracker_ids = $this->build_tracker->buildPlannableTrackers(
            $trackers_id,
            $program_project->getId()
        );

        $plan = new Plan($program_tracker, $plannable_tracker_ids);
        $this->plan_store->save($plan);
    }
}
