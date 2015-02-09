<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
namespace Tuleap\AgileDashboard\REST\v1;

use PFUser;
use Project;
use PlanningFactory;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use TrackerFactory;
use Planning_MilestoneFactory;
use AgileDashboard_Milestone_Backlog_BacklogStrategyFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemBuilder;
use AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider;
use AgileDashboard_BacklogItemDao;
use AgileDashboard_Milestone_MilestoneStatusCounter;
use Tracker_ArtifactDao;
use IdsFromBodyAreNotUniqueException;
use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityManager;
use Tracker_Artifact_PriorityHistoryDao;
use UserManager;
use Tuleap\REST\v1\OrderRepresentationBase;
use PlanningPermissionsManager;

/**
 * Wrapper for backlog related REST methods
 */
class ProjectBacklogResource {
    const MAX_LIMIT = 50;
    const TOP_BACKLOG_IDENTIFIER = AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider::TOP_BACKLOG_IDENTIFIER;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory */
    private $backlog_strategy_factory;

    /** @var \AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $backlog_item_collection_factory;

    /** @var ArtifactLinkUpdater */
    private $artifactlink_updater;

    /** @var MilestoneResourceValidator */
    private $milestone_validator;

    /** @var ResourcesPatcher */
    private $resources_patcher;

    public function __construct() {
        $planning_factory             = PlanningFactory::build();
        $tracker_artifact_factory     = Tracker_ArtifactFactory::instance();
        $tracker_form_element_factory = Tracker_FormElementFactory::instance();
        $status_counter               = new AgileDashboard_Milestone_MilestoneStatusCounter(
            new AgileDashboard_BacklogItemDao(),
            new Tracker_ArtifactDao(),
            $tracker_artifact_factory
        );

        $this->milestone_factory = new Planning_MilestoneFactory(
            PlanningFactory::build(),
            Tracker_ArtifactFactory::instance(),
            Tracker_FormElementFactory::instance(),
            TrackerFactory::instance(),
            $status_counter,
            new PlanningPermissionsManager()
        );

        $this->backlog_strategy_factory = new AgileDashboard_Milestone_Backlog_BacklogStrategyFactory(
            new AgileDashboard_BacklogItemDao(),
            $tracker_artifact_factory,
            $planning_factory
        );

        $this->backlog_item_collection_factory = new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $tracker_artifact_factory,
            $tracker_form_element_factory,
            $this->milestone_factory,
            $planning_factory,
            new AgileDashboard_Milestone_Backlog_BacklogItemBuilder()
        );

        $this->milestone_validator = new MilestoneResourceValidator(
            $planning_factory,
            $tracker_artifact_factory,
            $tracker_form_element_factory,
            $this->backlog_strategy_factory,
            $this->milestone_factory,
            $this->backlog_item_collection_factory
        );

        $priority_manager = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            UserManager::instance(),
            $tracker_artifact_factory
        );

        $this->artifactlink_updater      = new ArtifactLinkUpdater($priority_manager);
        $this->milestone_content_updater = new MilestoneContentUpdater($tracker_form_element_factory, $this->artifactlink_updater);
        $this->resources_patcher         = new ResourcesPatcher(
            $this->artifactlink_updater,
            $tracker_artifact_factory,
            $priority_manager
        );
    }

    /**
     * Get the backlog items that can be planned in a top-milestone of a given project
     */
    public function get(PFUser $user, Project $project, $limit, $offset) {
        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }

        $backlog_items                       = $this->getBacklogItems($user, $project);
        $backlog_item_representations        = array();
        $backlog_item_representation_factory = new BacklogItemRepresentationFactory();

        foreach($backlog_items as $backlog_item) {
            $backlog_item_representations[] = $backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);
        }

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, count($backlog_items));

        return array_slice($backlog_item_representations, $offset, $limit);
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    public function options(PFUser $user, Project $project, $limit, $offset) {
        $this->sendAllowHeaders();
    }

    public function put(PFUser $user, Project $project, array $ids) {
        $this->validateArtifactIdsAreInOpenAndUnassignedTopBacklog($ids, $user, $project);

        try {
            $this->artifactlink_updater->setOrderWithHistoryChangeLogging($ids, self::TOP_BACKLOG_IDENTIFIER, $project->getId());
        } catch (ItemListedTwiceException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $this->sendAllowHeaders();
    }

    public function patch(PFUser $user, Project $project, OrderRepresentationBase $order, array $add = null) {
        if ($add) {
            try {
                $this->resources_patcher->removeArtifactFromSource($user, $add);
            } catch (\Exception $exception) {
                throw new RestException(400, $exception->getMessage());
            }
        }

        $all_ids = array_merge(array($order->compared_to), $order->ids);
        $this->validateArtifactIdsAreInOpenAndUnassignedTopBacklog($all_ids, $user, $project);

        try {
            $this->resources_patcher->updateArtifactPriorities($order, self::TOP_BACKLOG_IDENTIFIER, $project->getId());
        } catch (Tracker_Artifact_Exception_CannotRankWithMyself $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function validateArtifactIdsAreInOpenAndUnassignedTopBacklog($ids, $user, $project) {
        try {
            $this->milestone_validator->validateArtifactIdsAreInOpenAndUnassignedTopBacklog($ids, $user, $project);
        } catch (ArtifactIsNotInOpenAndUnassignedTopBacklogItemsException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function getBacklogItems(PFUser $user, Project $project) {
        $top_milestone       = $this->milestone_factory->getVirtualTopMilestone($user, $project);
        $strategy_unassigned = $this->backlog_strategy_factory->getSelfBacklogStrategy($top_milestone);

        return $this->backlog_item_collection_factory->getUnassignedOpenCollection($user, $top_milestone, $strategy_unassigned, false);
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeaders() {
        Header::allowOptionsGetPut();
    }
}