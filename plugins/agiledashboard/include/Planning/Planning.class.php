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
    
    function __construct($id, $name, $group_id, $backlog_tracker_ids = array(), $planning_tracker_id = null) {
        $this->id                  = $id;
        $this->name                = $name;
        $this->group_id            = $group_id;
        $this->backlog_tracker_ids = $backlog_tracker_ids;
        $this->planning_tracker_id  = $planning_tracker_id;
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
     * @return int The tracker id, the artifacts of which are supposed to be planned
     */
    public function getPlanningTrackerId() {
        return $this->planning_tracker_id;
    }
}


?>
