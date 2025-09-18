<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Tracker;

use PlanningFactory;
use Project;
use Tracker_Hierarchy;
use TrackerFactory;
use Tuleap\Tracker\Tracker;

readonly class HierarchyChecker
{
    public function __construct(
        private PlanningFactory $planning_factory,
        private TrackerFactory $tracker_factory,
    ) {
    }

    public function isPartOfScrumHierarchy(Tracker $tracker): bool
    {
        $project   = $tracker->getProject();
        $hierarchy = $tracker->getHierarchy();

        return $this->checkHierarchyContainsGivenTrackerIds($hierarchy, $this->getScrumTrackerIds($project));
    }

    private function getScrumTrackerIds(Project $project): array
    {
        $planning_tracker_ids = $this->planning_factory->getPlanningTrackerIdsByGroupId($project->getID());
        $backlog_tracker_ids  = $this->planning_factory->getBacklogTrackerIdsByGroupId($project->getID());

        return array_unique(array_merge($planning_tracker_ids, $backlog_tracker_ids));
    }

    private function checkHierarchyContainsGivenTrackerIds(Tracker_Hierarchy $hierarchy, array $tracker_ids): bool
    {
        foreach ($hierarchy->flatten() as $tracker_id) {
            if (in_array($tracker_id, $tracker_ids)) {
                return true;
            }
        }

        return false;
    }

    public function getADTrackerIdsByProjectId(int $project_id): array
    {
        $planning_tracker_ids       = $this->planning_factory->getPlanningTrackerIdsByGroupId($project_id);
        $backlog_tracker_ids        = $this->planning_factory->getBacklogTrackerIdsByGroupId($project_id);
        $agiledashboard_tracker_ids = array_unique(
            array_merge($planning_tracker_ids, $backlog_tracker_ids)
        );
        $hierarchy_factory          = $this->tracker_factory->getHierarchyFactory();
        $trackers_hierarchy         = $hierarchy_factory->getHierarchy($agiledashboard_tracker_ids);
        $hierarchy_tracker_ids      = $trackers_hierarchy->flatten();

        return array_unique(array_merge($agiledashboard_tracker_ids, $hierarchy_tracker_ids));
    }
}
