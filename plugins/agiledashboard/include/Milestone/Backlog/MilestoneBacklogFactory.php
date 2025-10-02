<?php
/*
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Backlog;

use PFUser;
use Planning_Milestone;
use PlanningFactory;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\BacklogItemDao;
use Tuleap\Tracker\Tracker;

/**
 * I build AgileDashboard_Milestone_Backlog_Backlog
 */
class MilestoneBacklogFactory
{
    public function __construct(
        private readonly BacklogItemDao $dao,
        private readonly Tracker_ArtifactFactory $artifact_factory,
        private readonly PlanningFactory $planning_factory,
        private readonly \Tuleap\Tracker\Artifact\Dao\ArtifactDao $artifact_dao,
    ) {
    }

    public function getBacklog(PFUser $user, Planning_Milestone $milestone, ?int $limit = null, ?int $offset = null): \Tuleap\AgileDashboard\Milestone\Backlog\MilestoneBacklog
    {
        $backlog_trackers_children_can_manage = [];
        $first_child_backlog_trackers         = $this->getFirstChildBacklogTracker($user, $milestone);
        if ($first_child_backlog_trackers !== null) {
            $backlog_trackers_children_can_manage = array_merge($backlog_trackers_children_can_manage, $first_child_backlog_trackers);
        } else {
            $backlog_trackers_children_can_manage = array_merge($backlog_trackers_children_can_manage, $milestone->getPlanning()->getBacklogTrackers());
        }
        return $this->instantiateBacklog($milestone, $backlog_trackers_children_can_manage, $limit, $offset);
    }

    public function getSelfBacklog(Planning_Milestone $milestone, ?int $limit = null, ?int $offset = null): MilestoneBacklog
    {
        return $this->instantiateBacklog(
            $milestone,
            $milestone->getPlanning()->getBacklogTrackers(),
            $limit,
            $offset
        );
    }

    private function instantiateBacklog(
        Planning_Milestone $milestone,
        array $backlog_trackers_children_can_manage,
        ?int $limit = null,
        ?int $offset = null,
    ): MilestoneBacklog {
        return new MilestoneBacklog(
            $this->artifact_factory,
            $milestone,
            $milestone->getPlanning()->getBacklogTrackers(),
            $backlog_trackers_children_can_manage,
            $this->dao,
            $this->artifact_dao,
            $limit,
            $offset
        );
    }

    /**
     * @return Tracker[]|null
     */
    private function getFirstChildBacklogTracker(PFUser $user, Planning_Milestone $milestone): ?array
    {
        $backlog_tracker_children = $milestone->getPlanning()->getPlanningTracker()->getChildren();
        if ($backlog_tracker_children) {
            $first_child_tracker  = current($backlog_tracker_children);
            $first_child_planning = $this->planning_factory->getPlanningByPlanningTracker($user, $first_child_tracker);
            if ($first_child_planning) {
                return $first_child_planning->getBacklogTrackers();
            }
        }
        return null;
    }
}
