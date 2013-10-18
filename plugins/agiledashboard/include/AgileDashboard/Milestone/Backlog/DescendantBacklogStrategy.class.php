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

    public function __construct($milestone_backlog_artifacts, array $item_names, array $descendant_trackers, AgileDashboard_BacklogItemDao $dao) {
        parent::__construct($milestone_backlog_artifacts, $item_names);
        $this->dao = $dao;
        $this->descendant_trackers = $descendant_trackers;
    }

    public function getDescendantTrackers() {
        return $this->descendant_trackers;
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
        $descendant_trackers_names = array();

        foreach ($this->getDescendantTrackers() as $descendant_tracker) {
            $descendant_trackers_names[] = $descendant_tracker->getName();
        }

        return implode(', ', $descendant_trackers_names);
    }

    private function getBacklogParentElements(PFUser $user, $redirect_to_self) {
        $create_new = array();
        foreach ($this->milestone_backlog_artifacts as $artifact) {
            /* @var Tracker_Artifact $artifact */
            $create_new[] = new AgileDashboard_Milestone_Pane_Content_ContentNewPresenter(
                $artifact->getTitle(),
                $artifact->getSubmitNewArtifactLinkedToMeUri($this->getDescendantTrackers()).'&'.$redirect_to_self
            );
        }
        return $create_new;
    }

    public function getMilestoneBacklogArtifactsTracker() {
        return $this->getDescendantTrackers();
    }

    private function getAddItemsToBacklogUrls(PFUser $user, Planning_ArtifactMilestone $milestone, $redirect_to_self) {
        $submit_urls = array();

        foreach ($this->getDescendantTrackers() as $descendant_tracker) {
            if ($descendant_tracker->userCanSubmitArtifact($user)) {
                $submit_urls[] = array(
                    'tracker_type' => $descendant_tracker->getName(),
                    'submit_url'   => $milestone->getArtifact()->getSubmitNewArtifactLinkedToMeUri($descendant_tracker).'&'.$redirect_to_self
                );
            }
        }

        return $submit_urls;
    }

    private function canUserPrioritizeBacklog(PFUser $user) {
        $can_prioritize = true;

        foreach ($this->descendant_trackers as $descendant_tracker) {
            $can_prioritize = $can_prioritize && $descendant_tracker->userCanSubmitArtifact($user);
        }

        return $can_prioritize;
    }

    public function getTrackersWithoutInitialEffort() {
        $trackers_without_initial_effort_defined = array();
        foreach ($this->descendant_trackers as $descendant) {
            if (! AgileDashBoard_Semantic_InitialEffort::load($descendant)->getField()) {
                $trackers_without_initial_effort_defined[] = $descendant;
            }
        }

        return $trackers_without_initial_effort_defined;
    }

    public function getPresenter(
        PFUser $user,
        Planning_ArtifactMilestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $todo,
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $done,
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $inconsistent_collection,
        $redirect_to_self) {

        return new AgileDashboard_Milestone_Pane_Content_ContentPresenterDescendant(
            $todo,
            $done,
            $inconsistent_collection,
            $this->getBacklogItemName(),
            $this->getAddItemsToBacklogUrls($user, $milestone, $redirect_to_self),
            $this->descendant_trackers,
            $this->canUserPrioritizeBacklog($user),
            $this->getTrackersWithoutInitialEffort(),
            $this->getSolveInconsistenciesUrl($milestone, $redirect_to_self)
        );
    }

    private function getSolveInconsistenciesUrl(Planning_ArtifactMilestone $milestone, $redirect_to_self) {
        return  AGILEDASHBOARD_BASE_URL.
                "/?group_id=".$milestone->getGroupId().
                "&aid=".$milestone->getArtifactId().
                "&action=solve-inconsistencies".
                "&".$redirect_to_self;
    }
}


?>
