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

namespace Tuleap\ProgramManagement\Adapter\Program;

use Tuleap\ProgramManagement\Adapter\ProgramManagementProjectAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\PlanningHasNoMilestoneTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlanningHasNoProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\PlanningNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\SecondPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\ProgramManagementProject;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class PlanningAdapter implements BuildPlanning, RetrievePlanningMilestoneTracker
{
    public function __construct(private \PlanningFactory $planning_factory, private RetrieveUser $retrieve_user)
    {
    }

    /**
     * @throws TopPlanningNotFoundInProjectException
     * @throws PlanningHasNoProgramIncrementException
     */
    public function getRootPlanning(UserIdentifier $user_identifier, int $project_id): \Planning
    {
        $user          = $this->retrieve_user->getUserWithId($user_identifier);
        $root_planning = $this->planning_factory->getRootPlanning(
            $user,
            $project_id
        );

        if (! $root_planning) {
            throw new TopPlanningNotFoundInProjectException($project_id);
        }

        if ($root_planning->getPlanningTracker() instanceof \NullTracker) {
            throw new PlanningHasNoProgramIncrementException($root_planning->getId());
        }

        return $root_planning;
    }

    public function getProjectFromPlanning(\Planning $root_planning): ProgramManagementProject
    {
        return ProgramManagementProjectAdapter::build($root_planning->getPlanningTracker()->getProject());
    }

    public function retrieveRootPlanningMilestoneTracker(ProgramManagementProject $project, UserIdentifier $user_identifier): TrackerReference
    {
        $root_planning = $this->getRootPlanning($user_identifier, $project->getId());
        return TrackerReferenceProxy::fromTracker($root_planning->getPlanningTracker());
    }

    /**
     * @throws PlanningNotFoundException
     * @throws TrackerRetrievalException
     */
    public function retrieveSecondPlanningMilestoneTracker(ProgramManagementProject $project, UserIdentifier $user_identifier): TrackerReference
    {
        $user          = $this->retrieve_user->getUserWithId($user_identifier);
        $root_planning = $this->planning_factory->getRootPlanning(
            $user,
            $project->getId()
        );

        if (! $root_planning) {
            throw new TopPlanningNotFoundInProjectException($project->getId());
        }

        $children_planning = $this->planning_factory->getChildrenPlanning($root_planning);
        if (! $children_planning) {
            throw new SecondPlanningNotFoundInProjectException($project->getId());
        }
        if ($children_planning->getPlanningTracker() instanceof \NullTracker) {
            throw new PlanningHasNoMilestoneTrackerException($children_planning->getId());
        }
        return TrackerReferenceProxy::fromTracker($children_planning->getPlanningTracker());
    }
}
