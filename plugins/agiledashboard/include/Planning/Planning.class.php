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

/**
 * This allows to define a planning
 * A planning is composed of a list of tracker ids (eg: Sprints, Tasks...) that represent what is in the backlog
 * It is also composed of a tracker id (eg: Releases tracker), the artifacts (eg: Release 1, Release 2...)of which will be planified
 */
class Planning {
    
    /**
     * @var int
     */
    private $id;
    
    /**
     * @var string
     */
    private $name;
    
    /**
     * @var int
     */
    private $group_id;
    
    /**
     * @var string
     */
    private $backlog_title;
    
    /**
     * @var string
     */
    private $plan_title;
    
    /**
     * @var Array of int
     */
    private $backlog_tracker_id;
    
    /**
     * @var int
     */
    private $planning_tracker_id;
    
    /**
     * @var Tracker
     */
    private $planning_tracker;
    
    /**
     * @var Tracker
     */
    private $backlog_tracker;
    
    function __construct($id, $name, $group_id, $backlog_title, $plan_title, $backlog_tracker_id = null, $planning_tracker_id = null) {
        $this->id                  = $id;
        $this->name                = $name;
        $this->group_id            = $group_id;
        $this->plan_title          = $plan_title;
        $this->backlog_title       = $backlog_title;
        $this->backlog_tracker_id  = $backlog_tracker_id;
        $this->planning_tracker_id = $planning_tracker_id;
        $this->backlog_tracker     = new NullTracker();
        $this->planning_tracker    = new NullTracker();
    }
    
    /**
     * @return int the planning id
     */
    public function getId () {
        return $this->id;
    }
    
    /**
     * @return String the planning name
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * @return int the group_id the planning belongs to
     */
    public function getGroupId() {
        return $this->group_id;
    }
    
    /**
     * @return String the title of the backlog
     */
    public function getBacklogTitle() {
        return $this->backlog_title;
    }
    
    /**
     * @return String the title of the plan
     */
    public function getPlanTitle() {
        return $this->plan_title;
    }
    
    /**
     * @return int The id as the tracker used as backlog
     */
    public function getBacklogTrackerId() {
        return $this->backlog_tracker_id;
    }
    
    /**
     * @return int The id of the tracker used as planning destination
     */
    public function getPlanningTrackerId() {
        return $this->planning_tracker_id;
    }
    
    /**
     * @return Tracker The tracker used as planning destination
     */
    public function getPlanningTracker() {
        return $this->planning_tracker;
    }
    
    /**
     * TODO: Pass the planning tracker at instanciation, and remove this setter.
     * 
     * @param Tracker $planning_tracker The tracker used as planning destination
     */
    public function setPlanningTracker(Tracker $planning_tracker) {
        $this->planning_tracker = $planning_tracker;
    }
    
    /**
     * @param Tracker $backlog_tracker The tracker used as a backlog
     */
    public function setBacklogTracker(Tracker $backlog_tracker) {
        $this->backlog_tracker = $backlog_tracker;
        $this->backlog_tracker_id = $backlog_tracker->getId();
    }
    
    /**
     * @return Tracker
     */
    public function getBacklogTracker() {
        return $this->backlog_tracker;
    }
}


?>
