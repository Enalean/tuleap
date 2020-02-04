<?php
/**
 * Copyright (c) Enalean 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use PFUser;
use Planning;
use PlanningFactory;
use Tracker;

class PlanningTrackerBacklogChecker
{
    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    public function __construct(PlanningFactory $planning_factory)
    {
        $this->planning_factory = $planning_factory;
    }

    public function isTrackerBacklogOfProjectRootPlanning(Tracker $tracker, PFUser $user): bool
    {
        $root_planning = $this->planning_factory->getRootPlanning($user, $tracker->getGroupId());
        if (! $root_planning) {
            return false;
        }

        return $this->isTrackerBacklogOfProjectPlanning($root_planning, $tracker);
    }

    public function isTrackerBacklogOfProjectPlanning(Planning $planning, Tracker $tracker): bool
    {
        return in_array($tracker->getId(), $planning->getBacklogTrackersIds());
    }
}
