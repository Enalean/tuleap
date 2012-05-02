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

require_once(dirname(__FILE__).'/../include/Tracker/TrackerManager.class.php');

function aTracker() {
    return new Test_Tracker_Builder();
}

class Test_Tracker_Builder {
    private $id;
    private $project;
    private $project_id;
    private $name;
    
    public function withId($id) {
        $this->id = $id;
        return $this;
    }
    
    public function withProject(Project $project) {
        $this->project    = $project;
        $this->project_id = $project->getId();
        return $this;
    }
    
    public function withProjectId($project_id) {
        $this->project_id = $project_id;
        return $this;
    }
    
    public function withName($name) {
        $this->name = $name;
        return $this;
    }
        
    /**
     * @return \Tracker 
     */
    public function build() {
        $tracker = new Tracker($this->id, $this->project_id, $this->name, null, null, null, null, null, null, null, null, null);
        if ($this->project) {
            $tracker->setProject($this->project);
        }
        return $tracker;
    }
}

?>
