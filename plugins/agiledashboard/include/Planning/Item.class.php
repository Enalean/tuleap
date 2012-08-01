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

require_once 'Planning.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/Artifact/Tracker_Artifact.class.php';

/**
 * An item to be displayed in a planning.
 * 
 * Given a planning was configured to move stories from the product backlog to
 * the selected release:
 * 
 *     Product Backlog | Release 1.0
 *     ----------------+-------------
 *     + Epic 2        | + Epic 1
 *       + Story 2     |   + Story 1
 *         + Task 2    |     + Task 1
 * 
 * Epics, stories and tasks all need to be displayed in the planning. They are
 * all "planning items".
 * 
 * Any item from the backlog can be planned for Release 1.0.
 * But only root items of the release can be moved back to the backlog.
 * 
 * The Planning_Item::isPlannifiable() method allows one to know whether an
 * item can be planned (e.g. Epic2, Story 2, Task 2 or Epic 1).
 */
class Planning_Item {
    
    /**
     * @var Planning
     */
    private $planning;
    
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    /**
     * @param Tracker_Artifact $artifact The underlying artifact to be planned.
     * @param Planning         $planning The planning this item belongs to.
     */
    public function __construct(Tracker_Artifact $artifact, Planning $planning) {
        $this->planning = $planning;
        $this->artifact = $artifact;
    }
    
    /**
     * An URL pointing to the edit page of this item.
     * 
     * TODO: move to presenter ?
     * 
     * @return string
     */
    public function getEditUri() {
        return $this->artifact->getUri();
    }
    
    /**
     * A human-friendly unique identifier.
     * 
     * @return string
     */
    public function getXRef() {
        return $this->artifact->getXRef();
    }
    
    /**
     * A title for this item.
     * 
     * @return string
     */
    public function getTitle() {
        return $this->artifact->getTitle();
    }
    
    /**
     * A machine-friendly unique identifier. 
     * 
     * @return int
     */
    public function getId() {
        return $this->artifact->getId();
    }
    
    /**
     * Get the underlying backlog tracker.
     * 
     * @return Tracker
     */
    public function getTracker() {
        return $this->artifact->getTracker();
    }
    
    /**
     * @return Tracker_Artifact
     */
    public function getArtifact() {
        return $this->artifact;
    }
    
    /** 
     * Checks whether or not this item can be assigned to a milestone.
     * 
     * @return bool
     */
    public function isPlannifiable() {
        return ($this->getTracker()->getId() == $this->planning->getBacklogTrackerId());
    }

    /**
     * @see Tracker_Artifact::getAllowedChildrenTypes()
     */
    public function getAllowedChildrenTypes() {
        return $this->appendBacklogTrackerIfRoot($this->artifact->getAllowedChildrenTypes());
    }

    /**
     * Allow to create artifacts when the item comes from the planning tracker and if
     * the backlog tracker is at the root of the hierarchy.
     *
     * Example:
     * Given I have a planning Epic -> Release
     * And Epic has not parent (root of hierarchy)
     * Then I can create an Epic right in the release
     *
     * Counter example:
     * Given I have a planning Story -> Sprint
     * And I have a hierarchy Epic -> Story
     * Then I cannot create Story directly below Sprint
     *
     * @param array $allowed_trackers
     * @return type
     */
    private function appendBacklogTrackerIfRoot(array $allowed_trackers) {
        $backlog_tracker = array();
        if ($this->getTracker() == $this->planning->getPlanningTracker()) {
            $backlog_hierarchy = $this->planning->getBacklogTracker()->getHierarchy();
            if ($backlog_hierarchy->isRoot($this->planning->getBacklogTrackerId())) {
                $backlog_tracker = array($this->planning->getBacklogTracker());
            }
        }
        return array_merge($allowed_trackers, $backlog_tracker);
    }
}

?>
