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

namespace Tuleap\ScaledAgile\Program\Hierarchy;

use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\HierarchyException;
use Tuleap\ScaledAgile\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ScaledAgile\Adapter\Program\Plan\ProgramAccessException;
use Tuleap\ScaledAgile\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ScaledAgile\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ScaledAgile\Program\Plan\BuildProgram;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;

class HierarchyCreator implements CreateHierarchy
{
    /**
     * @var HierarchyStore
     */
    private $hierarchy_store;
    /**
     * @var BuildHierarchy
     */
    private $build_hierarchy;
    /**
     * @var BuildProgram
     */
    private $program_build;

    public function __construct(
        BuildProgram $program_build,
        BuildHierarchy $build_hierarchy,
        HierarchyStore $hierarchy_store
    ) {
        $this->program_build   = $program_build;
        $this->build_hierarchy = $build_hierarchy;
        $this->hierarchy_store = $hierarchy_store;
    }

    /**
     * @throws ProgramAccessException
     * @throws ProjectIsNotAProgramException
     * @throws TopPlanningNotFoundInProjectException
     * @throws HierarchyException
     * @throws PlanTrackerException
     * @throws ProgramTrackerException
     */
    public function create(\PFUser $user, int $program_id, int $program_tracker_id, array $team_tracker_ids): void
    {
        $program   = $this->program_build->buildExistingProgramProject($program_id, $user);
        $hierarchy = $this->build_hierarchy->buildHierarchy($user, $program, $program_tracker_id, $team_tracker_ids);

        $this->hierarchy_store->save($hierarchy);
    }
}
