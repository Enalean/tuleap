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
use Luracast\Restler\RestException;
use PFUser;
use Planning_MilestoneFactory;
use Planning_NoPlanningsException;
use Planning_VirtualTopMilestone;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\Milestone\ParentTrackerRetriever;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Project\ProjectBackground\ProjectBackgroundDao;
use Tuleap\REST\Header;

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

        $this->milestone_factory = Planning_MilestoneFactory::build();

        $this->backlog_factory = new AgileDashboard_Milestone_Backlog_BacklogFactory(
            new AgileDashboard_BacklogItemDao(),
            $tracker_artifact_factory,
            $this->planning_factory,
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
        try {
            $top_milestone = $this->milestone_factory->getVirtualTopMilestone($user, $project);
        } catch (Planning_NoPlanningsException $exception) {
            throw new RestException(404, 'No top planning found for this project');
        }

        if ($limit == 0) {
            $backlog_items = [];
        } else {
            $backlog_items = $this->getBacklogItems($user, $top_milestone);
        }

        $backlog_item_representations        = [];
        $backlog_item_representation_factory = new BacklogItemRepresentationFactory(new ProjectBackgroundConfiguration(new ProjectBackgroundDao()));

        foreach ($backlog_items as $backlog_item) {
            $backlog_item_representations[] = $backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);
        }

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, count($backlog_items));

        $contents = array_slice($backlog_item_representations, $offset, $limit);

        $accepted_trackers                   = $this->getAcceptedTrackers($user, $project);
        $has_user_priority_change_permission = $this->hasUserPriorityChangePermission($user, $project);

        $parent_trackers = $this->parent_tracker_retriever->getCreatableParentTrackers($top_milestone, $user, $accepted_trackers);

        return BacklogRepresentation::build($contents, $accepted_trackers, $parent_trackers, $has_user_priority_change_permission);
    }

    private function hasUserPriorityChangePermission(PFUser $user, Project $project)
    {
        $root_planning = $this->planning_factory->getRootPlanning($user, $project->getId());

        if ($root_planning) {
            return $this->planning_permissions_manager->userHasPermissionOnPlanning($root_planning->getId(), $root_planning->getGroupId(), $user, PlanningPermissionsManager::PERM_PRIORITY_CHANGE);
        }

        return false;
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
            return [];
        }

        return $top_milestone->getPlanning()->getBacklogTrackers();
    }
}
