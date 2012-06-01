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

interface Planning_Milestone {

    /**
     * @return int The project identifier.
     */
    function getGroupId();
    
    /**
     * @return Project
     */
    function getProject();

        /**
     * @return Tracker_Artifact
     */
    function getArtifact();
    
    /**
     * @return array of Planning_Milestone
     */
    function getSubMilestones();
    
    /**
     * @return Boolean True if milestone has at least 1 sub-milestone.
     */
    function hasSubMilestones();
    
    /**
     * Adds some sub-milestones. Ignores milestones which are already a
     * sub-milestone of the current one.
     * 
     * @param array $new_sub_milestones 
     */
    function addSubMilestones(array $new_sub_milestones);
    
    /**
     * @return Boolean
     */
    function userCanView(User $user);
    
    /**
     * @return int
     */
    function getArtifactId();
    
    /**
     * @return string
     */
    function getArtifactTitle();

    /**
     * @return string
     */
    function getXRef();
    

    /**
     * @return Planning
     */
    function getPlanning();
    
    /**
     * @return int
     */
    function getPlanningId();
    
    /**
     * @return TreeNode
     */
    function getPlannedArtifacts();
    
    /**
     * All artifacts linked by either the root artifact or any of the artifacts in plannedArtifacts() 
     * @param User $user
     * @return Array of Tracker_Artifact
     */
    function getLinkedArtifacts(User $user);
    
}

?>
