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

namespace Tuleap\ScaledAgile\Program\Backlog\CreationCheck;

use PFUser;
use Tracker;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;

class ArtifactCreatorChecker
{
    /**
     * @var ProjectIncrementArtifactCreatorChecker
     */
    private $project_increment_artifact_creator_checker;
    /**
     * @var PlanningAdapter
     */
    private $planning_adapter;

    public function __construct(
        PlanningAdapter $planning_adapter,
        ProjectIncrementArtifactCreatorChecker $project_increment_artifact_creator_checker
    ) {
        $this->project_increment_artifact_creator_checker = $project_increment_artifact_creator_checker;
        $this->planning_adapter                           = $planning_adapter;
    }

    public function canCreateAnArtifact(PFUser $user, Tracker $tracker): bool
    {
        try {
            $root_planning = $this->planning_adapter->buildRootPlanning(
                $user,
                (int) $tracker->getProject()->getID()
            );
        } catch (TopPlanningNotFoundInProjectException $e) {
            return true;
        }

        if ($root_planning->getPlanningTracker()->getId() !== $tracker->getId()) {
            return true;
        }

        return $this->project_increment_artifact_creator_checker->canProjectIncrementBeCreated($root_planning, $user);
    }
}
