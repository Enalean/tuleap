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

namespace Tuleap\ScaledAgile\Adapter\Program\Hierarchy;

use PFUser;
use Tuleap\ScaledAgile\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ScaledAgile\Adapter\Program\Tracker\ProgramTrackerAdapter;
use Tuleap\ScaledAgile\Program\BuildPlanning;
use Tuleap\ScaledAgile\Program\Hierarchy\BuildHierarchy;
use Tuleap\ScaledAgile\Program\Hierarchy\Hierarchy;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\ScaledAgile\Team\BuildTeamTracker;

final class HierarchyAdapter implements BuildHierarchy
{
    /**
     * @var BuildPlanning
     */
    private $planning_adapter;
    /**
     * @var BuildTeamTracker
     */
    private $team_tracker_adapter;
    /**
     * @var ProgramTrackerAdapter
     */
    private $program_tracker_adapter;

    public function __construct(
        BuildPlanning $planning_adapter,
        BuildTeamTracker $team_tracker_adapter,
        ProgramTrackerAdapter $program_tracker_adapter
    ) {
        $this->planning_adapter        = $planning_adapter;
        $this->team_tracker_adapter    = $team_tracker_adapter;
        $this->program_tracker_adapter = $program_tracker_adapter;
    }

    /**
     * @throws TeamTrackerMustBeInPlannableTopBacklogException
     * @throws TeamTrackerNotFoundException
     * @throws TrackerDoesNotBelongToTeamException
     * @throws PlanTrackerException
     * @throws \Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException
     * @throws \Tuleap\ScaledAgile\Adapter\Program\Tracker\ProgramTrackerException
     */
    public function buildHierarchy(
        PFUser $user,
        Program $program,
        int $program_tracker_id,
        int $team_backlog_id
    ): Hierarchy {
        $this->program_tracker_adapter->buildPlannableProgramTracker($program_tracker_id, $program->getId());
        $team_tracker = $this->team_tracker_adapter->buildTeamTracker($team_backlog_id);

        $planning_configuration = $this->planning_adapter->buildRootPlanning(
            $user,
            (int) $team_tracker->getProjectId()
        );

        if (! in_array($team_tracker->getTeamTrackerId(), $planning_configuration->getPlannableTrackerIds())) {
            throw new TeamTrackerMustBeInPlannableTopBacklogException($team_tracker->getTeamTrackerId());
        }

        return new Hierarchy($program_tracker_id, $team_backlog_id);
    }
}
