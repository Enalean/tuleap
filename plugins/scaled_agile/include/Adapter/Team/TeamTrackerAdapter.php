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

namespace Tuleap\ScaledAgile\Adapter\Team;

use TrackerFactory;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\HierarchyException;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TeamCanOnlyHaveOneBacklogTrackerException;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TeamTrackerMustBeInPlannableTopBacklogException;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TeamTrackerNotFoundException;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TrackerDoesNotBelongToTeamException;
use Tuleap\ScaledAgile\Program\BuildPlanning;
use Tuleap\ScaledAgile\Team\BuildTeamTracker;
use Tuleap\ScaledAgile\Team\Creation\TeamStore;

final class TeamTrackerAdapter implements BuildTeamTracker
{
    /**
     * @var TeamStore
     */
    private $team_store;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var BuildPlanning
     */
    private $planning_adapter;

    public function __construct(
        TrackerFactory $tracker_factory,
        TeamStore $team_store,
        BuildPlanning $planning_adapter
    ) {
        $this->team_store       = $team_store;
        $this->tracker_factory  = $tracker_factory;
        $this->planning_adapter = $planning_adapter;
    }

    /**
     * @param int[]   $team_backlog_ids
     *
     * @throws HierarchyException
     */
    public function buildTeamTrackers(array $team_backlog_ids, \PFUser $user): void
    {
        $team_project_backlog_ids = [];
        foreach ($team_backlog_ids as $team_backlog_id) {
            $team_tracker = $this->tracker_factory->getTrackerById($team_backlog_id);
            if (! $team_tracker) {
                throw new TeamTrackerNotFoundException($team_backlog_id);
            }

            if (! $this->team_store->isATeam((int) $team_tracker->getGroupId())) {
                throw new TrackerDoesNotBelongToTeamException($team_backlog_id);
            }

            $planning_configuration = $this->planning_adapter->buildRootPlanning(
                $user,
                (int) $team_tracker->getGroupId()
            );

            if (! in_array($team_tracker->getId(), $planning_configuration->getPlannableTrackerIds())) {
                throw new TeamTrackerMustBeInPlannableTopBacklogException($team_tracker->getId());
            }

            if (isset($team_project_backlog_ids[$team_tracker->getGroupId()])) {
                throw new TeamCanOnlyHaveOneBacklogTrackerException($team_tracker->getGroupId());
            }

            $team_project_backlog_ids[$team_tracker->getGroupId()][] = $team_tracker->getId();
        }
    }
}
