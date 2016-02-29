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
 * Presents everything needed to render a link to the planning view of a
 * milestone.
 */
class Planning_MilestoneLinkPresenter {
    
    /**
     * @var Planning_Milestone
     */
    protected $milestone;
    
    public function __construct(Planning_Milestone $milestone) {
        $this->milestone = $milestone;
    }
    
    /**
     * @return string The URL to the planning view of this milestone.
     */
    public function getUri() {
        $group_id    = $this->milestone->getGroupId();
        $planning_id = $this->milestone->getPlanningId();
        $artifact_id = $this->milestone->getArtifactId();
        
        return "/plugins/agiledashboard/?group_id=$group_id&action=show&planning_id=$planning_id&aid=$artifact_id&pane=planning-v2";
    }
    
    /**
     * @return string The cross-reference of this milestone's underlying artifact.
     */
    public function getXref() {
        return $this->milestone->getXref();
    }

    /**
     * @return string The milestone title (i.e. the artifact's one).
     */
    public function getTitle() {
        return $this->milestone->getArtifactTitle();
    }
}
?>
