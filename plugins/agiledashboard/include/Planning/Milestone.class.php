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
 * A planning milestone (e.g.: Sprint, Release...)
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
    private $planned_artifacts;
    
    public function __construct(                 $group_id,
                                Planning         $planning,
                                Tracker_Artifact $artifact          = null,
                                TreeNode         $planned_artifacts = null) {
        
        $this->group_id          = $group_id;
        $this->planning          = $planning;
        $this->artifact          = $artifact;
        $this->planned_artifacts = $planned_artifacts;
    }

    /**
     * @return Tracker_Artifact
     */
    public function getArtifact() {
        return $this->artifact;
    }
    
    /**
     * @return Tracker
     */
    public function userCanView(User $user) {
        return $this->artifact->getTracker()->userCanView($user);
    }
    
    /**
     * @return int
     */
    public function getArtifactId() {
        return $this->artifact ? $this->artifact->getId() : null;
    }
    
    /**
     * @return TreeNode
     */
    public function getPlannedArtifacts() {
        return $this->planned_artifacts;
    }
}

/**
 * Null-object pattern for planning milestones.
 */
class Planning_NoMilestone extends Planning_Milestone {
    
    /**
     * Instanciates a null-object compatible with the Planning_Milestone
     * interface.
     * 
     * TODO:
     *   - Rename to NullMilestone ?
     *   - Use a NullPlanning object ?
     *   - $group_id must die
     * 
     * @param int      $group_id
     * @param Planning $planning 
     */
    public function __construct($group_id, Planning $planning) {
        parent::__construct($group_id, $planning, null);
    }
    
    /**
     * @param User $user
     * @return boolean 
     */
    public function userCanView(User $user) {
        return true; // User can view milestone content, since it's empty.
    }
}
?>
