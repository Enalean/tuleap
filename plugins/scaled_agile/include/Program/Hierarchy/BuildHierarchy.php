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

use PFUser;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TeamTrackerMustBeInPlannableTopBacklogException;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TeamTrackerNotFoundException;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TrackerDoesNotBelongToTeamException;
use Tuleap\ScaledAgile\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ScaledAgile\Program\Program;

interface BuildHierarchy
{
    /**
     * @throws TeamTrackerMustBeInPlannableTopBacklogException
     * @throws TeamTrackerNotFoundException
     * @throws TrackerDoesNotBelongToTeamException
     * @throws PlanTrackerException
     * @throws \Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException
     * @throws \Tuleap\ScaledAgile\Adapter\Program\Tracker\ProgramTrackerException
     *
     * @param int[] $team_backlog_ids
     */
    public function buildHierarchy(
        PFUser $user,
        Program $program,
        int $program_tracker_id,
        array $team_backlog_ids
    ): Hierarchy;
}
