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
use Tuleap\ProgramManagement\Domain\Workspace\VerifyProjectPermission;

final class PlanCreator implements CreatePlan
{
    private BuildTracker $build_tracker;
    private PlanStore $plan_store;
    private RetrieveProgramUserGroup $ugroup_retriever;
    private RetrieveProject $project_retriever;
    private VerifyIsTeam $team_verifier;
    private VerifyProjectPermission $permission_verifier;

    public function __construct(
        BuildTracker $build_tracker,
        RetrieveProgramUserGroup $ugroup_retriever,
        PlanStore $plan_store,
        RetrieveProject $project_retriever,
        VerifyIsTeam $team_verifier,
        VerifyProjectPermission $permission_verifier
    ) {
        $this->build_tracker       = $build_tracker;
        $this->ugroup_retriever    = $ugroup_retriever;
        $this->plan_store          = $plan_store;
        $this->project_retriever   = $project_retriever;
        $this->team_verifier       = $team_verifier;
        $this->permission_verifier = $permission_verifier;
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
        $program_tracker   = ProgramIncrementTracker::buildProgramIncrementTracker(
            $this->build_tracker,
            $plan_change->program_increment_change->tracker_id,
            $program->id
        );
        $iteration_tracker = null;
        if ($plan_change->iteration) {
            $iteration_tracker = IterationTracker::fromPlanIterationChange(
                $this->build_tracker,
                $plan_change->iteration,
                $program->id
            );
        }
        $plannable_tracker_ids      = $this->build_tracker->buildPlannableTrackerList(
            $plan_change->tracker_ids_that_can_be_planned,
            $program->id
        );
        $can_prioritize_user_groups = ProgramUserGroupCollection::fromRawIdentifiers(
            $this->ugroup_retriever,
            $program,
            $plan_change->can_possibly_prioritize_ugroups
        );

        $plan = new Plan(
            $program_tracker,
            $program->id,
            $plannable_tracker_ids,
            $can_prioritize_user_groups,
            $plan_change->program_increment_change->label,
            $plan_change->program_increment_change->sub_label,
            $iteration_tracker
        );
        $this->plan_store->save($plan);
    }
}
