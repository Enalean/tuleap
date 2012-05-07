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

class Planning_Milestone {
    
    /**
     * @var int
     */
    private $group_id;
    
    /**
     * @var Planning
     */
    private $planning;
    
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    /**
     * @var TreeNode
     */
    private $assigned_artifacts;
    
    public function __construct(                 $group_id,
                                Planning         $planning,
                                Tracker_Artifact $artifact           = null,
                                TreeNode         $assigned_artifacts = null) {
        
        $this->group_id           = $group_id;
        $this->planning           = $planning;
        $this->artifact           = $artifact;
        $this->assigned_artifacts = $assigned_artifacts;
    }

    /**
     * @return Tracker_Artifact
     */
    public function getArtifact() {
        return $this->artifact;
    }
    
    public function getArtifactId() {
        return $this->artifact ? $this->artifact->getId() : null;
    }
    
    /**
     * @return TreeNode 
     */
    public function getAssignedArtifacts() {
        return $this->assigned_artifacts;
    }
}

class Planning_NoMilestone extends Planning_Milestone {
    
    public function __construct($group_id, Planning $planning) {
        parent::__construct($group_id, $planning, null);
    }
}
?>
