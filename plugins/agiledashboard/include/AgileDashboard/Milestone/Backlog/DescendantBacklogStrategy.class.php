<?php
/**
 * Copyright Enalean (c) 2013 - 2017. All rights reserved.
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
use Tuleap\AgileDashboard\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\ScrumForMonoMilestoneDao;

/**
 * I am the backlog of the first descendant of the current milestone
 */
class AgileDashboard_Milestone_Backlog_DescendantBacklogStrategy extends AgileDashboard_Milestone_Backlog_BacklogStrategy {

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker[] */
    private $descendant_trackers;

    private $limit;

    private $offset;

    private $milestone;

    private $content_size = 0;

    private $backlog_size = 0;

    /** @var Tracker[] */
    private $backlogitem_trackers;

    /** @var AgileDashboard_Milestone_Backlog_DescendantItemsFinder */
    private $items_finder;

    /**
     * @var ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        Planning_Milestone $milestone,
        array $item_names,
        array $descendant_trackers,
        AgileDashboard_BacklogItemDao $item_dao,
        ScrumForMonoMilestoneChecker $scrum_mono_milestone_checker,
        $limit  = null,
        $offset = null
    ) {
        $this->milestone                    = $milestone;
        $this->backlogitem_trackers         = $item_names;
        $this->dao                          = $item_dao;
        $this->descendant_trackers          = $descendant_trackers;
        $this->limit                        = $limit;
        $this->offset                       = $offset;
        $this->scrum_mono_milestone_checker = $scrum_mono_milestone_checker;

        $this->items_finder = new AgileDashboard_Milestone_Backlog_DescendantItemsFinder(
            $item_dao,
            $artifact_factory->getDao(),
            $artifact_factory,
            $milestone,
            $this->getDescendantTrackerIds()
        );
    }

    public function getDescendantTrackers() {
        return $this->descendant_trackers;
    }

    public function getDescendantTrackerIds() {
        $ids = array();
        foreach ($this->descendant_trackers as $tracker) {
            $ids[] = $tracker->getId();
        }

        return $ids;
    }

    public function getTotalContentSize() {
        return $this->content_size;
    }

    public function getTotalBacklogSize() {
        return $this->backlog_size;
    }

    /** @return Tracker[] */
    public function getItemTrackers() {
        return $this->backlogitem_trackers;
    }

    public function getBacklogItemName() {
        $descendant_trackers_names = array();

        foreach ($this->getDescendantTrackers() as $descendant_tracker) {
            $descendant_trackers_names[] = $descendant_tracker->getName();
        }

        return implode(', ', $descendant_trackers_names);
    }

    public function getMilestoneBacklogArtifactsTracker() {
        return $this->getDescendantTrackers();
    }

    private function getAddItemsToBacklogUrls(PFUser $user, Planning_Milestone $milestone, $redirect_to_self) {
        $submit_urls = array();

        foreach ($this->getDescendantTrackers() as $descendant_tracker) {
            if ($descendant_tracker->userCanSubmitArtifact($user)) {
                $submit_urls[] = array(
                    'tracker_type' => $descendant_tracker->getName(),
                    'tracker_id'   => $descendant_tracker->getId(),
                    'submit_url'   => $milestone->getArtifact()->getSubmitNewArtifactLinkedToMeUri($descendant_tracker).'&'.$redirect_to_self
                );
            }
        }

        return $submit_urls;
    }

    private function canUserPrioritizeBacklog(Planning_Milestone $milestone, PFUser $user) {
        $artifact_factory  = Tracker_ArtifactFactory::instance();
        $planning_factory  = PlanningFactory::build();
        $milestone_factory = new Planning_MilestoneFactory(
            $planning_factory,
            $artifact_factory,
            Tracker_FormElementFactory::instance(),
            TrackerFactory::instance(),
            new AgileDashboard_Milestone_MilestoneStatusCounter(
                $this->dao,
                new Tracker_ArtifactDao(),
                $artifact_factory
            ),
            new PlanningPermissionsManager(),
            new AgileDashboard_Milestone_MilestoneDao(),
            new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), $planning_factory)
        );

        return $milestone_factory->userCanChangePrioritiesInMilestone($milestone, $user);
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
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection $todo,
        AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection $done,
        AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection $inconsistent_collection,
        $redirect_to_self) {

        return new AgileDashboard_Milestone_Pane_Content_ContentPresenterDescendant(
            $todo,
            $done,
            $inconsistent_collection,
            $this->getBacklogItemName(),
            $this->getAddItemsToBacklogUrls($user, $milestone, $redirect_to_self),
            $this->descendant_trackers,
            $this->canUserPrioritizeBacklog($milestone, $user),
            $this->getTrackersWithoutInitialEffort(),
            $this->getSolveInconsistenciesUrl($milestone, $redirect_to_self),
            $milestone->getArtifactId()
        );
    }

    private function getSolveInconsistenciesUrl(Planning_Milestone $milestone, $redirect_to_self) {
        return  AGILEDASHBOARD_BASE_URL.
                "/?group_id=".$milestone->getGroupId().
                "&aid=".$milestone->getArtifactId().
                "&action=solve-inconsistencies".
                "&".$redirect_to_self;
    }

    /** @return AgileDashboard_Milestone_Backlog_DescendantItemsCollection */
    public function getArtifacts(PFUser $user) {
        if ($this->milestone instanceof Planning_VirtualTopMilestone) {
            if ($this->limit !== null || $this->offset !== null) {
                $artifacts_collection = $this->items_finder->getAllTopMilestoneContentItemsWithLimitAndOffset($user, $this->limit, $this->offset);
            } else {
                $artifacts_collection = $this->items_finder->getAllTopMilestoneContentItems($user);
            }
        } else {
            if ($this->limit !== null || $this->offset !== null) {
                $artifacts_collection = $this->items_finder->getAllMilestoneContentItemsWithLimitAndOffset($user, $this->limit, $this->offset);
            } else {
                $artifacts_collection = $this->items_finder->getAllUIMilestoneBacklogItems($user);
            }
        }

        return $artifacts_collection;
    }

    /** @return AgileDashboard_Milestone_Backlog_DescendantItemsCollection */
    public function getOpenUnplannedArtifacts(PFUser $user, $sub_milestone_ids) {
        if ($this->milestone instanceof Planning_VirtualTopMilestone) {
            if ($this->limit !== null || $this->offset !== null) {
                $artifacts_collection = $this->items_finder->getTopMilestoneOpenUnplannedBacklogItemsWithLimitAndOffset($user, $this->limit, $this->offset);
            } else {
                $artifacts_collection = $this->items_finder->getAllTopMilestoneOpenUnplannedBacklogItems($user, $sub_milestone_ids);
            }
        } else {
            if ($this->limit !== null || $this->offset !== null) {
                $artifacts_collection = $this->items_finder->getMilestoneOpenUnplannedBacklogItemsWithLimitAndOffset($user, $sub_milestone_ids, $this->limit, $this->offset);
            } else {
                $artifacts_collection = $this->items_finder->getAllMilestoneOpenUnplannedBacklogItems($user, $sub_milestone_ids);
            }
        }

        return $artifacts_collection;
    }

    /** @return AgileDashboard_Milestone_Backlog_DescendantItemsCollection */
    public function getUnplannedArtifacts(PFUser $user, $sub_milestone_ids)
    {
        if ($this->scrum_mono_milestone_checker->isMonoMilestoneEnabled($this->milestone->getProject()->getID()) === true) {
            return $this->getUnplannedArtifactsForMonoMilestoneConfiguration($user, $sub_milestone_ids);
        } else {
            return $this->getUnplannedArtifactsForMultiMilestoneConfiguration($user, $sub_milestone_ids);
        }
    }

    private function getUnplannedArtifactsForMonoMilestoneConfiguration(PFUser $user, $sub_milestone_ids)
    {
        if ($this->milestone instanceof Planning_VirtualTopMilestone) {
            return $this->items_finder->getAllTopMilestoneUnplannedBacklogItems($user);
        } else {
            return $this->items_finder->getOpenArtifactsForSubmilestonesForMonoMilestoneConfiguration($user, $sub_milestone_ids);
        }
    }

    private function getUnplannedArtifactsForMultiMilestoneConfiguration(PFUser $user, $sub_milestone_ids)
    {
        if ($this->milestone instanceof Planning_VirtualTopMilestone) {
            if ($this->limit !== null || $this->offset !== null) {
                $artifacts_collection = $this->items_finder->getTopMilestoneUnplannedBacklogItemsWithLimitAndOffset($user, $this->limit, $this->offset);
            } else {
                $artifacts_collection = $this->items_finder->getAllTopMilestoneUnplannedBacklogItems($user);
            }
        } else {
            if ($this->limit !== null || $this->offset !== null) {
                $artifacts_collection = $this->items_finder->getMilestoneUnplannedBacklogItemsWithLimitAndOffset($user, $sub_milestone_ids, $this->limit, $this->offset);
            } else {
                $artifacts_collection = $this->items_finder->getAllMilestoneUnplannedBacklogItems($user, $sub_milestone_ids);
            }
        }

        return $artifacts_collection;
    }
}
