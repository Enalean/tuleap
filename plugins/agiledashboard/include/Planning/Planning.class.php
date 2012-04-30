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
     * @var Array of int
     */
    private $backlog_tracker_ids;
    
    /**
     * @var int
     */
    private $planning_tracker_id;
    
    /**
     * @var Tracker
     */
    private $planning_tracker;
    
    function __construct($id, $name, $group_id, $backlog_tracker_ids = array(), $planning_tracker_id = null) {
        $this->id                  = $id;
        $this->name                = $name;
        $this->group_id            = $group_id;
        $this->backlog_tracker_ids = $backlog_tracker_ids;
        $this->planning_tracker_id = $planning_tracker_id;
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
     * @return array A list of tracker ids defined as backlog trackers
     */
    public function getBacklogTrackerIds() {
        return $this->backlog_tracker_ids;
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
     * @return array of Tracker
     */
    public function getRootBacklogTrackers() {
        return array();
    }
}


?>
