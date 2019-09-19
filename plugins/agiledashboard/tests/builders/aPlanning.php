<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

// This is an on going work to help developers to build more expressive tests
// please add the functions/methods below when needed.
// For further information about the Test Data Builder pattern
// @see http://nat.truemesh.com/archives/000727.html

require_once(dirname(__FILE__).'/../../include/Planning/Planning.class.php');

function aPlanning()
{
    return new Test_Planning_Builder();
}

class Test_Planning_Builder
{
    private $id                 = '1';
    private $name               = 'Test Planning';
    private $backlog_title      = 'Release Backlog';
    private $plan_title         = 'Sprint Plan';
    private $group_id           = '102';
    private $planning_tracker_id;
    private $planning_tracker;
    private $backlog_tracker_ids = array();
    private $backlog_trackers = array();

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function withName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function withBacklogTrackerId($backlog_tracker_id)
    {
        $this->backlog_tracker_ids[] = $backlog_tracker_id;
        return $this;
    }

    public function withGroupId($group_id)
    {
        $this->group_id = $group_id;
        return $this;
    }

    public function withPlanningTrackerId($planning_tracker_id)
    {
        $this->planning_tracker_id = $planning_tracker_id;
        return $this;
    }

    public function withPlanningTracker($tracker)
    {
        $this->planning_tracker    = $tracker;
        $this->planning_tracker_id = $tracker->getId();
        return $this;
    }

    public function withBacklogTracker($backlog_tracker)
    {
        $this->backlog_tracker_ids[] = $backlog_tracker->getId();
        $this->backlog_trackers[]    = $backlog_tracker;
        return $this;
    }

    public function build()
    {
        $planning = new Planning(
            $this->id,
            $this->name,
            $this->group_id,
            $this->backlog_title,
            $this->plan_title,
            $this->backlog_tracker_ids,
            $this->planning_tracker_id
        );

        if ($this->planning_tracker) {
            $planning->setPlanningTracker($this->planning_tracker);
        }
        if ($this->backlog_trackers) {
            $planning->setBacklogTrackers($this->backlog_trackers);
        }

        return $planning;
    }
}
