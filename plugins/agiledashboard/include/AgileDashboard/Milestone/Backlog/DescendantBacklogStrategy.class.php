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
 * I am the backlog of the first descendant of the current milestone
 */
class AgileDashboard_Milestone_Backlog_DescendantBacklogStrategy extends AgileDashboard_Milestone_Backlog_BacklogStrategy {

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker[] */
    private $descendant_trackers;

    public function __construct($milestone_backlog_artifacts, Tracker $item_name, array $descendant_trackers, AgileDashboard_BacklogItemDao $dao) {
        parent::__construct($milestone_backlog_artifacts, $item_name);
        $this->dao = $dao;
        $this->descendant_trackers = $descendant_trackers;
    }

    public function getDescendantTracker() {
        return $this->descendant_trackers[0];
    }

    /** @return Tracker_Artifact[] */
    public function getArtifacts(PFUser $user) {
        $artifact_factory = Tracker_ArtifactFactory::instance();
        $artifacts_finder = new AgileDashboard_Milestone_Backlog_ArtifactsFinder(
            $artifact_factory,
            $this->milestone_backlog_artifacts,
            $this->descendant_trackers
        );

        return $artifacts_finder->getArtifacts($user);
    }

    public function getBacklogItemName() {
        return $this->getDescendantTracker()->getName();
    }

    private function getBacklogParentElements(PFUser $user, $redirect_to_self) {
        $create_new = array();
        foreach ($this->milestone_backlog_artifacts as $artifact) {
            /* @var Tracker_Artifact $artifact */
            $create_new[] = new AgileDashboard_Milestone_Pane_Content_ContentNewPresenter(
                $artifact->getTitle(),
                $artifact->getSubmitNewArtifactLinkedToMeUri($this->getDescendantTracker()).'&'.$redirect_to_self
            );
        }
        return $create_new;
    }

    public function getMilestoneBacklogArtifactsTracker() {
        return $this->getDescendantTracker();
    }

    public function getPresenter(
        PFUser $user,
        Planning_ArtifactMilestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $todo,
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $done,
        $redirect_to_self) {

        return new AgileDashboard_Milestone_Pane_Content_ContentPresenterDescendant(
            $todo,
            $done,
            $this->getBacklogItemName(),
            $this->backlogitem_tracker->userCanSubmitArtifact($user),
            $milestone->getArtifact()->getSubmitNewArtifactLinkedToMeUri($this->getItemTracker()).'&'.$redirect_to_self,
            $this->getBacklogParentElements($user, $redirect_to_self),
            $this->descendant_trackers[0]->userCanSubmitArtifact($user)
        );
    }
}


?>
