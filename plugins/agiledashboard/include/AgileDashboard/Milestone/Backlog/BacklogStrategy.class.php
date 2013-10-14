<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 * I retrieve the content of the backlog
 */
abstract class AgileDashboard_Milestone_Backlog_BacklogStrategy {

    /** @var Tracker_Artifact[] */
    protected $milestone_backlog_artifacts;

    /** @var Tracker[] */
    protected $backlogitem_trackers;

    /**
     * @param array $milestone_backlog_artifacts
     * @param Tracker[] $backlogitem_trackers
     */
    public function __construct($milestone_backlog_artifacts, array $backlogitem_trackers) {
        $this->milestone_backlog_artifacts  = $milestone_backlog_artifacts;
        $this->backlogitem_trackers         = $backlogitem_trackers;
    }

    /** @return Tracker[] */
    public function getItemTrackers() {
        return $this->backlogitem_trackers;
    }

    /** @return Tracker_Artifact[] */
    public abstract function getArtifacts(PFUser $user);

    /**
     * Return the string representation of the backlog tracker
     *
     * @return String
     */
    public abstract function getBacklogItemName();

    /**
     * @return Tracker the Tracker to which the artifacts belong
     */
    public abstract function getMilestoneBacklogArtifactsTracker();
}
?>
