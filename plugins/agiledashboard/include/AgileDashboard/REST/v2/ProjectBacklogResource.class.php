<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v2;

use AgileDashboard_BacklogItemDao;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemBuilder;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use AgileDashboard_Milestone_MilestoneDao;
use AgileDashboard_Milestone_MilestoneStatusCounter;
use Luracast\Restler\RestException;
use PFUser;
use Planning_MilestoneFactory;
use Planning_NoPlanningsException;
use Planning_VirtualTopMilestone;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\Milestone\ParentTrackerRetriever;
use Tuleap\AgileDashboard\MonoMilestone\MonoMilestoneBacklogItemDao;
use Tuleap\AgileDashboard\MonoMilestone\MonoMilestoneItemsFinder;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\REST\Header;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

/**
 * Wrapper for backlog related REST methods
 */
class ProjectBacklogResource
{
    public const MAX_LIMIT = 50;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogFactory */
    private $backlog_factory;

    /** @var \AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $backlog_item_collection_factory;

    /** @var \PlanningFactory */
    private $planning_factory;

    /** @var \PlanningPermissionsManager */
    private $planning_permissions_manager;

    /**
     * @var ParentTrackerRetriever
     */
    private $parent_tracker_retriever;

    public function __construct()
    {
        $this->planning_factory             = PlanningFactory::build();
        $tracker_artifact_factory           = Tracker_ArtifactFactory::instance();
        $tracker_form_element_factory       = Tracker_FormElementFactory::instance();
        $this->planning_permissions_manager = new PlanningPermissionsManager();
        $status_counter                     = new AgileDashboard_Milestone_MilestoneStatusCounter(
            new AgileDashboard_BacklogItemDao(),
            new Tracker_ArtifactDao(),
            $tracker_artifact_factory
        );

        $mono_milestone_items_finder = new MonoMilestoneItemsFinder(
            new MonoMilestoneBacklogItemDao(),
            $tracker_artifact_factory
        );

        $scrum_mono_milestone_checker = new ScrumForMonoMilestoneChecker(
            new ScrumForMonoMilestoneDao(),
            $this->planning_factory
        );

        $this->milestone_factory      = new Planning_MilestoneFactory(
            $this->planning_factory,
            Tracker_ArtifactFactory::instance(),
            $tracker_form_element_factory,
            $status_counter,
            $this->planning_permissions_manager,
            new AgileDashboard_Milestone_MilestoneDao(),
            $scrum_mono_milestone_checker,
            new TimeframeBuilder(
                new SemanticTimeframeBuilder(new SemanticTimeframeDao(), $tracker_form_element_factory),
                \BackendLogger::getDefaultLogger()
            ),
            new MilestoneBurndownFieldChecker($tracker_form_element_factory)
        );

        $this->backlog_factory = new AgileDashboard_Milestone_Backlog_BacklogFactory(
            new AgileDashboard_BacklogItemDao(),
            $tracker_artifact_factory,
            $this->planning_factory,
            $scrum_mono_milestone_checker,
            $mono_milestone_items_finder
        );

        $this->backlog_item_collection_factory = new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $tracker_artifact_factory,
            $this->milestone_factory,
            $this->planning_factory,
            new AgileDashboard_Milestone_Backlog_BacklogItemBuilder(),
            new RemainingEffortValueRetriever(
                $tracker_form_element_factory
            ),
            new ArtifactsInExplicitBacklogDao(),
            new \Tracker_Artifact_PriorityDao()
        );

        $this->parent_tracker_retriever = new ParentTrackerRetriever($this->planning_factory);
    }

    /**
     * Get the backlog with the items that can be planned in a top-milestone of a given project
     */
    public function get(PFUser $user, Project $project, $limit, $offset)
    {
        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }

        try {
            $top_milestone = $this->milestone_factory->getVirtualTopMilestone($user, $project);
        } catch (Planning_NoPlanningsException $exception) {
            throw new RestException(404, 'No top planning found for this project');
        }

        if ($limit == 0) {
            $backlog_items = array();
        } else {
            $backlog_items = $this->getBacklogItems($user, $top_milestone);
        }

        $backlog_item_representations        = array();
        $backlog_item_representation_factory = new BacklogItemRepresentationFactory();

        foreach ($backlog_items as $backlog_item) {
            $backlog_item_representations[] = $backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);
        }

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, count($backlog_items));

        $backlog  = new BacklogRepresentation();
        $contents = array_slice($backlog_item_representations, $offset, $limit);

        $accepted_trackers                   = $this->getAcceptedTrackers($user, $project);
        $has_user_priority_change_permission = $this->hasUserPriorityChangePermission($user, $project);

        $parent_trackers = $this->parent_tracker_retriever->getCreatableParentTrackers($top_milestone, $user, $accepted_trackers);

        return $backlog->build($contents, $accepted_trackers, $parent_trackers, $has_user_priority_change_permission);
    }

    private function hasUserPriorityChangePermission(PFUser $user, Project $project)
    {
        $root_planning = $this->planning_factory->getRootPlanning($user, $project->getId());

        if ($root_planning) {
            return $this->planning_permissions_manager->userHasPermissionOnPlanning($root_planning->getId(), $root_planning->getGroupId(), $user, PlanningPermissionsManager::PERM_PRIORITY_CHANGE);
        }

        return false;
    }

    private function limitValueIsAcceptable($limit)
    {
        return $limit <= self::MAX_LIMIT;
    }

    public function options(PFUser $user, Project $project, $limit, $offset)
    {
        $this->sendAllowHeaders();
    }

    private function getBacklogItems(PFUser $user, Planning_VirtualTopMilestone $top_milestone)
    {
        $backlog_unassigned = $this->backlog_factory->getSelfBacklog($top_milestone);

        return $this->backlog_item_collection_factory->getUnassignedOpenCollection($user, $top_milestone, $backlog_unassigned, false);
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGet();
    }

    private function getAcceptedTrackers(PFUser $user, Project $project)
    {
        try {
            $top_milestone = $this->milestone_factory->getVirtualTopMilestone($user, $project);
        } catch (\Planning_NoPlanningsException $e) {
            return array();
        }

        return $top_milestone->getPlanning()->getBacklogTrackers();
    }
}
