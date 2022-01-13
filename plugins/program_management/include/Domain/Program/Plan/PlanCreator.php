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

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramUserGroupCollection;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveProject;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\RetrieveTracker;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyProjectPermission;

final class PlanCreator implements CreatePlan
{
    public function __construct(
        private CheckNewProgramIncrementTracker $program_increment_checker,
        private RetrieveTracker $tracker_retriever,
        private RetrieveProgramUserGroup $ugroup_retriever,
        private SavePlan $plan_store,
        private RetrieveProject $project_retriever,
        private VerifyIsTeam $team_verifier,
        private VerifyProjectPermission $permission_verifier,
    ) {
    }

    public function create(PlanChange $plan_change): void
    {
        $project           = $this->project_retriever->getProjectWithId($plan_change->project_id);
        $program           = ProgramForAdministrationIdentifier::fromProject(
            $this->team_verifier,
            $this->permission_verifier,
            $plan_change->user,
            $project
        );
        $program_tracker   = NewProgramIncrementTracker::fromId(
            $this->program_increment_checker,
            $plan_change->program_increment_change->tracker_id,
            $program
        );
        $iteration_tracker = null;
        if ($plan_change->iteration) {
            $iteration_tracker = IterationTracker::fromPlanIterationChange(
                $this->tracker_retriever,
                $plan_change->iteration,
                $program
            );
        }
        $plannable_tracker_collection = ProgramPlannableTrackerCollection::fromIds(
            $this->tracker_retriever,
            $plan_change->tracker_ids_that_can_be_planned,
            $program
        );
        $can_prioritize_user_groups   = ProgramUserGroupCollection::fromRawIdentifiers(
            $this->ugroup_retriever,
            $program,
            $plan_change->can_possibly_prioritize_ugroups
        );

        $plan = new Plan(
            $program_tracker,
            $program->id,
            $plannable_tracker_collection->trackers,
            $can_prioritize_user_groups,
            $plan_change->program_increment_change->label,
            $plan_change->program_increment_change->sub_label,
            $iteration_tracker
        );
        $this->plan_store->save($plan);
    }
}
