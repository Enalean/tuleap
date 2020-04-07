<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Milestone\Backlog;

use PFUser;
use PlanningFactory;
use Project;
use Tracker_ArtifactFactory;

class TopBacklogElementsToAddChecker
{
    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(PlanningFactory $planning_factory, Tracker_ArtifactFactory $artifact_factory)
    {
        $this->planning_factory = $planning_factory;
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * @throws NoRootPlanningException
     * @throws ProvidedAddedIdIsNotInPartOfTopBacklogException
     */
    public function checkAddedIdsBelongToTheProjectTopBacklogTrackers(Project $project, PFUser $user, array $added_artifact_ids)
    {
        $root_planning = $this->planning_factory->getRootPlanning($user, (int) $project->getID());
        if (! $root_planning) {
            throw new NoRootPlanningException();
        }

        $ids_in_error  = [];
        foreach ($added_artifact_ids as $added_artifact_id) {
            $artifact = $this->artifact_factory->getArtifactById($added_artifact_id);

            if (
                $artifact !== null &&
                ! in_array($artifact->getTrackerId(), $root_planning->getBacklogTrackersIds())
            ) {
                $ids_in_error[] = $added_artifact_id;
            }
        }

        if (count($ids_in_error) > 0) {
            throw new ProvidedAddedIdIsNotInPartOfTopBacklogException($ids_in_error);
        }
    }
}
