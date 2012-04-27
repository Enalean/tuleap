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

require_once 'common/include/Codendi_Request.class.php';

class TestPlanningCreationRequestBuilder {
    public function __construct() {
        $this->group_id            = '123';
        $this->planning_name       = 'My Planning';
        $this->planning_tracker_id = '1';
        $this->backlog_tracker_ids = array('2', '3');
    }
    
    public function withGroupId($group_id) {
        $this->group_id = $group_id;
        return $this;
    }
    
    public function withPlanningName($planning_name) {
        $this->planning_name = $planning_name;
        return $this;
    }
    
    public function withBacklogTrackerIds($backlog_tracker_ids) {
        $this->backlog_tracker_ids = $backlog_tracker_ids;
        return $this;
    }
    
    public function withPlanningTrackerId($planning_tracker_id) {
        $this->planning_tracker_id = $planning_tracker_id;
        return $this;
    }
    
    public function build() {
        return new Codendi_Request(array(
            'group_id'            => $this->group_id,
            'planning_name'       => $this->planning_name,
            'planning_tracker_id' => $this->planning_tracker_id,
            'backlog_tracker_ids' => $this->backlog_tracker_ids
        ));
    }
}

function aPlanningCreationRequest() {
    return new TestPlanningCreationRequestBuilder();
}

?>
