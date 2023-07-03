<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder;
use AgileDashboard_BacklogItemDao;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemBuilder;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider;
use Luracast\Restler\RestException;
use PFUser;
use Planning_MilestoneFactory;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_Artifact_PriorityManager;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\AgileDashboard\Milestone\Backlog\NoRootPlanningException;
use Tuleap\AgileDashboard\Milestone\Backlog\ProvidedAddedIdIsNotInPartOfTopBacklogException;
use Tuleap\AgileDashboard\Milestone\Backlog\TopBacklogElementsToAddChecker;
use Tuleap\AgileDashboard\MonoMilestone\MonoMilestoneBacklogItemDao;
use Tuleap\AgileDashboard\MonoMilestone\MonoMilestoneItemsFinder;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\AgileDashboard\REST\v1\Milestone\MilestoneElementAdder;
use Tuleap\AgileDashboard\REST\v1\Milestone\MilestoneElementRemover;
use Tuleap\AgileDashboard\REST\v1\Milestone\ProvidedRemoveIdIsNotInExplicitBacklogException;
use Tuleap\AgileDashboard\REST\v1\Milestone\RemoveNotAvailableInClassicBacklogModeException;
use Tuleap\AgileDashboard\REST\v1\Rank\ArtifactsRankOrderer;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Project\ProjectBackground\ProjectBackgroundDao;
use Tuleap\REST\Header;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdaterDataFormater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ItemListedTwiceException;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use UserManager;

/**
 * Wrapper for backlog related REST methods
 */
class ProjectBacklogResource
{
    public const MAX_LIMIT              = 100;
    public const TOP_BACKLOG_IDENTIFIER = AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider::TOP_BACKLOG_IDENTIFIER;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var ArtifactLinkUpdater */
    private $artifactlink_updater;

    /** @var MilestoneResourceValidator */
    private $milestone_validator;

    /** @var ResourcesPatcher */
    private $resources_patcher;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var PlanningPermissionsManager */
    private $planning_permissions_manager;

    /** @var AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder */
    private $paginated_backlog_item_representation_builder;

    public function __construct()
    {
        $this->planning_factory             = PlanningFactory::build();
        $tracker_artifact_factory           = Tracker_ArtifactFactory::instance();
        $tracker_form_element_factory       = Tracker_FormElementFactory::instance();
        $event_manager                      = \EventManager::instance();
        $user_manager                       = UserManager::instance();
        $this->planning_permissions_manager = new PlanningPermissionsManager();

        $planning_factory             = PlanningFactory::build();
        $scrum_mono_milestone_checker = new ScrumForMonoMilestoneChecker(
            new ScrumForMonoMilestoneDao(),
            $planning_factory
        );

        $mono_milestone_items_finder = new MonoMilestoneItemsFinder(
            new MonoMilestoneBacklogItemDao(),
            $tracker_artifact_factory
        );

        $this->milestone_factory = Planning_MilestoneFactory::build();

        $backlog_factory = new AgileDashboard_Milestone_Backlog_BacklogFactory(
            new AgileDashboard_BacklogItemDao(),
            $tracker_artifact_factory,
            $this->planning_factory,
            $scrum_mono_milestone_checker,
            $mono_milestone_items_finder
        );

        $backlog_item_collection_factory = new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $tracker_artifact_factory,
            $this->milestone_factory,
            $this->planning_factory,
            new AgileDashboard_Milestone_Backlog_BacklogItemBuilder(),
            new RemainingEffortValueRetriever(
                $tracker_form_element_factory
            ),
            new ArtifactsInExplicitBacklogDao(),
            new Tracker_Artifact_PriorityDao()
        );

        $this->milestone_validator = new MilestoneResourceValidator(
            $this->planning_factory,
            $tracker_artifact_factory,
            $backlog_factory,
            $this->milestone_factory,
            $backlog_item_collection_factory,
            $scrum_mono_milestone_checker
        );

        $priority_manager = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            $user_manager,
            $tracker_artifact_factory
        );

        $this->artifactlink_updater = new ArtifactLinkUpdater($priority_manager, new ArtifactLinkUpdaterDataFormater());
        $this->resources_patcher    = new ResourcesPatcher(
            $this->artifactlink_updater,
            $tracker_artifact_factory,
            $priority_manager
        );

        $color_builder = new BackgroundColorBuilder(new BindDecoratorRetriever());
        $item_factory  = new BacklogItemRepresentationFactory(
            $color_builder,
            $user_manager,
            new ProjectBackgroundConfiguration(new ProjectBackgroundDao())
        );

        $this->paginated_backlog_item_representation_builder = new AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder(
            $item_factory,
            $backlog_item_collection_factory,
            $backlog_factory,
            new \Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao()
        );
    }

    /**
     * Get the backlog items that can be planned in a top-milestone of a given project
     */
    public function get(PFUser $user, Project $project, $limit, $offset)
    {
        $this->sendAllowHeaders();

        try {
            $top_milestone = $this->milestone_factory->getVirtualTopMilestone($user, $project);

            $paginated_backlog_items_representations = $this->paginated_backlog_item_representation_builder->getPaginatedBacklogItemsRepresentationsForTopMilestone($user, $top_milestone, $limit, $offset);

            $this->sendPaginationHeaders($limit, $offset, $paginated_backlog_items_representations->getTotalSize());

            return $paginated_backlog_items_representations->getBacklogItemsRepresentations();
        } catch (\Planning_NoPlanningsException $e) {
            $this->sendPaginationHeaders($limit, $offset, 0);
            return [];
        }
    }

    public function options(PFUser $user, Project $project, $limit, $offset)
    {
        $this->sendAllowHeaders();
    }

    public function put(PFUser $user, Project $project, array $ids)
    {
        $this->checkIfUserCanChangePrioritiesInMilestone($user, $project);

        $this->validateArtifactIdsAreInUnassignedTopBacklog($ids, $user, $project);

        try {
            $this->artifactlink_updater->setOrderWithHistoryChangeLogging($ids, (int) self::TOP_BACKLOG_IDENTIFIER, $project->getId());
        } catch (ItemListedTwiceException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $this->sendAllowHeaders();
    }

    /**
     * Action called when an artifact is added to backlog
     *
     * Explicit backlog
     *  => create new entry in db
     *
     * Standard backlog
     *  => reorder item to save it as first element of backlog
     *
     * @throws RestException
     * @throws \Throwable
     */
    public function patch(
        PFUser $user,
        Project $project,
        ?OrderRepresentation $order = null,
        ?array $add = null,
        ?array $remove = null,
    ) {
        $this->checkIfUserCanChangePrioritiesInMilestone($user, $project);
        if ($add) {
            try {
                $artifact_factory = Tracker_ArtifactFactory::instance();
                $adder            = new MilestoneElementAdder(
                    new ExplicitBacklogDao(),
                    new UnplannedArtifactsAdder(
                        new ExplicitBacklogDao(),
                        new ArtifactsInExplicitBacklogDao(),
                        new PlannedArtifactDao()
                    ),
                    $this->resources_patcher,
                    new TopBacklogElementsToAddChecker(
                        $this->planning_factory,
                        $artifact_factory
                    ),
                    $artifact_factory,
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                );
                $adder->addElementToBacklog($project, $add, $user);
            } catch (ProvidedAddedIdIsNotInPartOfTopBacklogException $exception) {
                throw new RestException(400, $exception->getMessage());
            } catch (NoRootPlanningException $exception) {
                throw new RestException(404, $exception->getMessage());
            }
        }

        if ($order) {
            $order->checkFormat();

            $all_ids = array_merge([$order->compared_to], $order->ids);
            $this->validateArtifactIdsAreInUnassignedTopBacklog($all_ids, $user, $project);

            $orderer = ArtifactsRankOrderer::build();
            $orderer->reorder($order, self::TOP_BACKLOG_IDENTIFIER, $project);
        }

        if ($remove) {
            try {
                $adder = new MilestoneElementRemover(
                    new ExplicitBacklogDao(),
                    new ArtifactsInExplicitBacklogDao(),
                    Tracker_ArtifactFactory::instance()
                );
                $adder->removeElementsFromBacklog($project, $user, $remove);
            } catch (RemoveNotAvailableInClassicBacklogModeException $exception) {
                throw new RestException(400, $exception->getMessage());
            } catch (ProvidedRemoveIdIsNotInExplicitBacklogException $exception) {
                throw new RestException(400, $exception->getMessage());
            }
        }
    }

    /**
     * @throws RestException
     */
    private function checkIfUserCanChangePrioritiesInMilestone(PFUser $user, Project $project)
    {
        $root_planning = $this->planning_factory->getRootPlanning($user, $project->getId());

        if (! $root_planning) {
            throw new RestException(403, "User does not have the permission to change items' priorities in this planning");
        }

        $user_has_permission = $this->planning_permissions_manager->userHasPermissionOnPlanning(
            $root_planning->getId(),
            $root_planning->getGroupId(),
            $user,
            PlanningPermissionsManager::PERM_PRIORITY_CHANGE
        );

        if (! $user_has_permission) {
            throw new RestException(403, "User does not have the permission to change items' priorities in this planning");
        }
    }

    /**
     * @throws RestException
     */
    private function validateArtifactIdsAreInUnassignedTopBacklog(array $ids, PFUser $user, Project $project)
    {
        try {
            $explicit_backlog_dao = new ExplicitBacklogDao();
            if ($explicit_backlog_dao->isProjectUsingExplicitBacklog((int) $project->getID()) === true) {
                $this->milestone_validator->validateIdsAreUnique($ids);
            } else {
                $this->milestone_validator->validateArtifactIdsAreInUnassignedTopBacklog($ids, $user, $project);
            }
        } catch (ArtifactIsNotInUnassignedTopBacklogItemsException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGetPut();
    }
}
