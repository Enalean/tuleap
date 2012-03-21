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
    
    function __construct($id, $name, $group_id, $backlog_tracker_ids = array(), $release_tracker_id = null) {
        $this->id = $id;
        $this->name = $name;
        $this->group_id = $group_id;
        $this->backlog_tracker_ids = $backlog_tracker_ids;
        $this->release_tracker_id  = $release_tracker_id;
    }
    public function getId () {
        return $this->id;
    }
    public function getName() {
        return $this->name;
    }
    
    public function getGroupId() {
        return $this->group_id;
    }
    
    public function getBacklogTrackerIds() {
        return $this->backlog_tracker_ids;
    }
    
    public function getReleaseTrackerId() {
        return $this->release_tracker_id;
    }
}


?>
