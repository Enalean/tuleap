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

namespace Tuleap\ScaledAgile\Program\PlanningConfiguration;

class PlanningAdapter
{
    /**
     * @var \PlanningFactory
     */
    private $planning_factory;

    public function __construct(\PlanningFactory $planning_factory)
    {
        $this->planning_factory = $planning_factory;
    }

    /**
     * @throws TopPlanningNotFoundInProjectException
     */
    public function buildRootPlanning(\PFUser $user, int $project_id): PlanningData
    {
        $root_planning = $this->planning_factory->getRootPlanning(
            $user,
            $project_id
        );

        if (! $root_planning) {
            throw new TopPlanningNotFoundInProjectException($project_id);
        }

        return $this->buildFromPlanning($root_planning);
    }

    /**
     * @throws PlanningFotFoundException
     */
    public function buildPlanningById(int $id): PlanningData
    {
        $planning = $this->planning_factory->getPlanning($id);

        if (! $planning) {
            throw new PlanningFotFoundException($id);
        }

        return $this->buildFromPlanning($planning);
    }

    public function buildFromPlanning(\Planning $planning): PlanningData
    {
        return new PlanningData(
            $planning->getPlanningTracker(),
            $planning->getId(),
            $planning->getName(),
            $planning->getBacklogTrackersIds()
        );
    }
}
