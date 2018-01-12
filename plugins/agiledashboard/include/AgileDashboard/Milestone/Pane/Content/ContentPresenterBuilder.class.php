<?php
/**
 * Copyright Enalean (c) 2013 - 2018. All rights reserved.
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

use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;


class AgileDashboard_Milestone_Pane_Content_ContentPresenterBuilder
{
    /** @var AgileDashboard_Milestone_Backlog_BacklogFactory */
    private $backlog_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $collection_factory;
    /**
     * @var AgileDashboard_BacklogItemDao
     */
    private $item_dao;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $collection_factory,
        AgileDashboard_BacklogItemDao $item_dao
    ) {
        $this->backlog_factory    = $backlog_factory;
        $this->collection_factory = $collection_factory;
        $this->item_dao           = $item_dao;
    }

    public function getMilestoneContentPresenter(PFUser $user, Planning_Milestone $milestone)
    {
        $redirect_paremeter = new Planning_MilestoneRedirectParameter();
        $backlog            = $this->backlog_factory->getBacklog($milestone);
        $redirect_to_self   = $redirect_paremeter->getPlanningRedirectToSelf($milestone, AgileDashboard_Milestone_Pane_Content_ContentPaneInfo::IDENTIFIER);

        $descendant_trackers = $backlog->getDescendantTrackers();

        return new AgileDashboard_Milestone_Pane_Content_ContentPresenter(
            $this->collection_factory->getTodoCollection($user, $milestone, $backlog, $redirect_to_self),
            $this->collection_factory->getDoneCollection($user, $milestone, $backlog, $redirect_to_self),
            $this->collection_factory->getInconsistentCollection($user, $milestone, $backlog, $redirect_to_self),
            $this->getAddItemsToBacklogUrls($descendant_trackers, $user, $milestone, $redirect_to_self),
            $descendant_trackers,
            $this->canUserPrioritizeBacklog($milestone, $user),
            $this->getTrackersWithoutInitialEffort($descendant_trackers),
            $this->getSolveInconsistenciesUrl($milestone, $redirect_to_self),
            $milestone->getArtifactId()
        );
    }

    private function getSolveInconsistenciesUrl(Planning_Milestone $milestone, $redirect_to_self)
    {
        return  AGILEDASHBOARD_BASE_URL.
            "/?group_id=".$milestone->getGroupId().
            "&aid=".$milestone->getArtifactId().
            "&action=solve-inconsistencies".
            "&".$redirect_to_self;
    }

    private function canUserPrioritizeBacklog(Planning_Milestone $milestone, PFUser $user)
    {
        $artifact_factory  = Tracker_ArtifactFactory::instance();
        $planning_factory  = PlanningFactory::build();
        $milestone_factory = new Planning_MilestoneFactory(
            $planning_factory,
            $artifact_factory,
            Tracker_FormElementFactory::instance(),
            TrackerFactory::instance(),
            new AgileDashboard_Milestone_MilestoneStatusCounter(
                $this->item_dao,
                new Tracker_ArtifactDao(),
                $artifact_factory
            ),
            new PlanningPermissionsManager(),
            new AgileDashboard_Milestone_MilestoneDao(),
            new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), $planning_factory)
        );

        return $milestone_factory->userCanChangePrioritiesInMilestone($milestone, $user);
    }

    private function getAddItemsToBacklogUrls(array $descendant_trackers, PFUser $user, Planning_Milestone $milestone, $redirect_to_self)
    {
        $submit_urls = array();

        foreach ($descendant_trackers as $descendant_tracker) {
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

    public function getTrackersWithoutInitialEffort(array $descendant_trackers)
    {
        $trackers_without_initial_effort_defined = array();
        foreach ($descendant_trackers as $descendant) {
            if (! AgileDashBoard_Semantic_InitialEffort::load($descendant)->getField()) {
                $trackers_without_initial_effort_defined[] = $descendant;
            }
        }

        return $trackers_without_initial_effort_defined;
    }
}
