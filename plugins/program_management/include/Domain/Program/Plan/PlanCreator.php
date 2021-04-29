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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException;

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
    /**
     * @var BuildProgramUserGroup
     */
    private $build_program_user_group;

    public function __construct(
        BuildProgram $program_build,
        BuildTracker $build_tracker,
        BuildProgramUserGroup $build_program_user_group,
        PlanStore $plan_store
    ) {
        $this->program_build            = $program_build;
        $this->build_tracker            = $build_tracker;
        $this->build_program_user_group = $build_program_user_group;
        $this->plan_store               = $plan_store;
    }

    /**
     * @param int[] $trackers_id
     * @param non-empty-list<string> $can_possibly_prioritize_ugroups
     *
     * @throws CannotPlanIntoItselfException
     * @throws PlanTrackerException
     * @throws ProgramAccessException
     * @throws ProjectIsNotAProgramException
     * @throws ProgramTrackerException
     * @throws InvalidProgramUserGroup
     */
    public function create(
        \PFUser $user,
        int $project_id,
        int $program_increment_id,
        array $trackers_id,
        array $can_possibly_prioritize_ugroups,
        ?string $custom_label,
        ?string $custom_sub_label
    ): void {
        if (in_array($program_increment_id, $trackers_id, true)) {
            throw new CannotPlanIntoItselfException();
        }
        $program_project            = $this->program_build->buildExistingProgramProjectForManagement($project_id, $user);
        $program_tracker            = ProgramIncrementTracker::buildProgramIncrementTracker(
            $this->build_tracker,
            $program_increment_id,
            $program_project->id
        );
        $plannable_tracker_ids      = $this->build_tracker->buildPlannableTrackerList(
            $trackers_id,
            $program_project->id
        );
        $can_prioritize_user_groups = $this->build_program_user_group->buildProgramUserGroups(
            $program_project,
            $can_possibly_prioritize_ugroups
        );

        $plan = new Plan($program_tracker, $plannable_tracker_ids, $can_prioritize_user_groups, $custom_label, $custom_sub_label);
        $this->plan_store->save($plan);
    }
}
