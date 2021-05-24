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

use Tuleap\ProgramManagement\Domain\Program\ProgramForManagement;

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

    public function create(PlanChange $plan_change): void
    {
        $program_project   = ProgramForManagement::fromId(
            $this->program_build,
            $plan_change->project_id,
            $plan_change->user
        );
        $program_tracker   = ProgramIncrementTracker::buildProgramIncrementTracker(
            $this->build_tracker,
            $plan_change->program_increment_change->tracker_id,
            $program_project->id
        );
        $iteration_tracker = null;
        if ($plan_change->iteration) {
            $iteration_tracker = IterationTracker::fromPlanIterationChange(
                $this->build_tracker,
                $plan_change->iteration,
                $program_project->id
            );
        }
        $plannable_tracker_ids      = $this->build_tracker->buildPlannableTrackerList(
            $plan_change->tracker_ids_that_can_be_planned,
            $program_project->id
        );
        $can_prioritize_user_groups = $this->build_program_user_group->buildProgramUserGroups(
            $program_project,
            $plan_change->can_possibly_prioritize_ugroups
        );

        $plan = new Plan(
            $program_tracker,
            $program_project->id,
            $plannable_tracker_ids,
            $can_prioritize_user_groups,
            $plan_change->program_increment_change->label,
            $plan_change->program_increment_change->sub_label,
            $iteration_tracker
        );
        $this->plan_store->save($plan);
    }
}
