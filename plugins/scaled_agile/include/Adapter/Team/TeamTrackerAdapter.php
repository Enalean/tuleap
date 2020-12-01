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
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TeamTrackerNotFoundException;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TrackerDoesNotBelongToTeamException;
use Tuleap\ScaledAgile\Team\BuildTeamTracker;
use Tuleap\ScaledAgile\Team\Creation\TeamStore;
use Tuleap\ScaledAgile\Team\TeamTracker;

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

    public function __construct(TrackerFactory $tracker_factory, TeamStore $team_store)
    {
        $this->team_store      = $team_store;
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @throws TeamTrackerNotFoundException
     * @throws TrackerDoesNotBelongToTeamException
     */
    public function buildTeamTracker(int $team_backlog_id): TeamTracker
    {
        $team_tracker = $this->tracker_factory->getTrackerById($team_backlog_id);
        if (! $team_tracker) {
            throw new TeamTrackerNotFoundException($team_backlog_id);
        }

        if (! $this->team_store->isATeam((int) $team_tracker->getGroupId())) {
            throw new TrackerDoesNotBelongToTeamException($team_backlog_id);
        }

        return new TeamTracker($team_tracker->getId(), (int) $team_tracker->getGroupId());
    }
}
