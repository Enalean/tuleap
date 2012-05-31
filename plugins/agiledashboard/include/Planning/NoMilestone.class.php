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

require_once 'Milestone.class.php';

/**
 * Null-object pattern for planning milestones.
 */
class Planning_NoMilestone extends Planning_Milestone {
    
    public function __construct($project, Planning $planning) {
        // not calling the super constructor allows us not enforce non nullity on the artifact
        $this->project           = $project;
        $this->planning          = $planning;
    }
    
    /**
     * @return string
     */
    public function getXRef() {
        return '';
    }
    
    /**
     * @return int
     */
    public function getArtifactId() {
        return null;
    }
    
    public function getArtifactTitle() {
        return null;
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
