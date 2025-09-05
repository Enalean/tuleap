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

use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TeamPlanning;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\PlanningHasNoMilestoneTrackerException;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredIterationTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class PlanningAdapter implements BuildPlanning, RetrieveMirroredProgramIncrementTracker, RetrieveMirroredIterationTracker
{
    public function __construct(private \PlanningFactory $planning_factory, private RetrieveUser $retrieve_user)
    {
    }

    /**
     * @throws TopPlanningNotFoundInProjectException
     * @throws PlanningHasNoMilestoneTrackerException
     */
    #[\Override]
    public function getRootPlanning(UserIdentifier $user_identifier, int $project_id): TeamPlanning
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
            throw new PlanningHasNoMilestoneTrackerException($root_planning->getId());
        }

        return TeamPlanningProxy::fromPlanning($root_planning);
    }

    #[\Override]
    public function retrieveRootPlanningMilestoneTracker(
        ProjectReference $project,
        UserIdentifier $user_identifier,
        ?ConfigurationErrorsCollector $errors_collector,
    ): ?TrackerReference {
        try {
            $root_planning = $this->getRootPlanning($user_identifier, $project->getId());
            return $root_planning->getPlanningTracker();
        } catch (TopPlanningNotFoundInProjectException $exception) {
            $errors_collector?->addTeamMilestonePlanningNotFoundOrNotAccessible($project);
        }

        return null;
    }

    #[\Override]
    public function retrieveSecondPlanningMilestoneTracker(
        ProjectReference $project,
        UserIdentifier $user,
        ?ConfigurationErrorsCollector $errors_collector,
    ): ?TrackerReference {
        $pfuser        = $this->retrieve_user->getUserWithId($user);
        $root_planning = $this->planning_factory->getRootPlanning(
            $pfuser,
            $project->getId()
        );

        if (! $root_planning) {
            $errors_collector?->addTeamMilestonePlanningNotFoundOrNotAccessible($project);
            return null;
        }

        $children_planning = $this->planning_factory->getChildrenPlanning($pfuser, $root_planning);
        if (! $children_planning) {
            $errors_collector?->addTeamSprintPlanningNotFoundOrNotAccessible($project);
            return null;
        }
        if ($children_planning->getPlanningTracker() instanceof \NullTracker) {
            throw new PlanningHasNoMilestoneTrackerException($children_planning->getId());
        }
        return TrackerReferenceProxy::fromTracker($children_planning->getPlanningTracker());
    }
}
