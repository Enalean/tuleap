<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Velocity\Semantic;

class ChildrenRequiredTrackerCollection
{
    /**
     * @var array
     */
    private $children_trackers = [];

    /**
     * []
     */
    private $children_misconfigured_trackers = [];

    /**
     * @var bool
     */
    private $is_not_a_top_backlog_planning = false;

    public function addChildrenRequiredTracker(ChildrenRequiredTracker $children_tracker)
    {
        $this->children_trackers[] = $children_tracker->getTracker();

        if ($children_tracker->isVelocitySemanticMissing()) {
            $this->children_misconfigured_trackers[] = [
                "name"        => $children_tracker->getTracker()->getName(),
                "tracker_url" => TRACKER_BASE_URL . "?" . http_build_query(
                    [
                        "tracker"  => $children_tracker->getTracker()->getId(),
                        "func"     => "admin-semantic",
                        "semantic" => "velocity"
                    ]
                ),
            ];
        }
    }

    /**
     * @return ChildrenRequiredTracker[]
     */
    public function getChildrenTrackers()
    {
        return $this->children_trackers;
    }

    public function hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers()
    {
        if ($this->is_not_a_top_backlog_planning) {
            return true;
        }

        if (count($this->children_trackers) === 0) {
            return true;
        }

        return count($this->children_trackers) > $this->getNbTrackersWithoutVelocitySemantic();
    }

    /**
     * @return int
     */
    public function getNbTrackersWithoutVelocitySemantic()
    {
        return count($this->children_misconfigured_trackers);
    }

    /**
     * @return array
     */
    public function getChildrenMisconfiguredTrackers()
    {
        return $this->children_misconfigured_trackers;
    }

    /**
     * @return bool
     */
    public function isNotATopBacklogPlanning()
    {
        return $this->is_not_a_top_backlog_planning;
    }

    /**
     * @param bool $is_not_a_top_backlog_planning
     */
    public function setIsNotATopBacklogPlanning($is_not_a_top_backlog_planning)
    {
        $this->is_not_a_top_backlog_planning = $is_not_a_top_backlog_planning;
    }
}
