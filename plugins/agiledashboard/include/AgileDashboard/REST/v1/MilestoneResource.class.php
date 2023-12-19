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
use BacklogItemReference;
use EventManager;
use Luracast\Restler\RestException;
use MilestoneParentLinker;
use PFUser;
use Planning_Milestone;
use Planning_MilestoneFactory;
use PlanningFactory;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_Artifact_PriorityManager;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElementFactory;
use Tracker_NoArtifactLinkFieldException;
use Tracker_NoChangeException;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\Milestone\ParentTrackerRetriever;
use Tuleap\AgileDashboard\Milestone\Request\MalformedQueryParameterException;
use Tuleap\AgileDashboard\Milestone\Request\SiblingMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\SubMilestoneRequest;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\AgileDashboard\REST\QueryToCriterionOnlyAllStatusConverter;
use Tuleap\AgileDashboard\REST\QueryToCriterionStatusConverter;
use Tuleap\AgileDashboard\REST\v1\Milestone\MilestoneElementMover;
use Tuleap\AgileDashboard\REST\v1\Milestone\MilestoneRepresentationBuilder;
use Tuleap\Tracker\REST\Helpers\ArtifactsRankOrderer;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Project\ProjectBackground\ProjectBackgroundDao;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdaterDataFormater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ItemListedTwiceException;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use Tuleap\Tracker\REST\Helpers\IdsFromBodyAreNotUniqueException;
use Tuleap\Tracker\REST\Helpers\OrderIdOutOfBoundException;
use Tuleap\Tracker\REST\Helpers\OrderRepresentation;
use URLVerification;
use UserManager;

/**
 * Wrapper for milestone related REST methods
 */
class MilestoneResource extends AuthenticatedResource
{
    /**
     * RESt call for cardwall GET
     *
     * Parameters:
     * 'version'   => API version
     * 'milestone' => Milestone on which cardwall is requested
     */
    public const AGILEDASHBOARD_EVENT_REST_GET_CARDWALL = 'agiledashboard_event_rest_get_cardwall';

    /**
     * RESt call for burndown GET
     *
     * Parameters:
     * 'version'   => API version
     * 'user'      => The user who resquest the burndown
     * 'milestone' => Milestone on which burndown is requested
     * 'burndown'  => OUT the \Tuleap\Tracker\REST\Artifact\BurndownRepresentation
     */
    public const AGILEDASHBOARD_EVENT_REST_GET_BURNDOWN = 'agiledashboard_event_rest_get_burndown';
    public const MAX_LIMIT                              = 100;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var MilestoneResourceValidator */
    private $milestone_validator;

    /** @var MilestoneContentUpdater */
    private $milestone_content_updater;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $backlog_item_collection_factory;

    /** @var EventManager */
    private $event_manager;

    /** @var ArtifactLinkUpdater */
    private $artifactlink_updater;

    /** @var AgileDashboard_Milestone_Backlog_BacklogFactory */
    private $backlog_factory;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var ResourcesPatcher */
    private $resources_patcher;

    /** @var \Tuleap\AgileDashboard\REST\v1\Milestone\MilestoneRepresentationBuilder */
    private $milestone_representation_builder;

    /** @var QueryToCriterionStatusConverter */
    private $query_to_criterion_converter;

    /**
     * @var ContentForMiletoneProvider
     */
    private $content_for_miletone_provider;
    /**
     * @var QueryToCriterionOnlyAllStatusConverter
     */
    private $query_to_criterion_only_all_status;

    public function __construct()
    {
        $planning_factory               = PlanningFactory::build();
        $this->tracker_artifact_factory = Tracker_ArtifactFactory::instance();

        $this->milestone_factory = Planning_MilestoneFactory::build();

        $this->backlog_factory = new AgileDashboard_Milestone_Backlog_BacklogFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->tracker_artifact_factory,
            $planning_factory,
        );

        $this->backlog_item_collection_factory = new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->tracker_artifact_factory,
            $this->milestone_factory,
            $planning_factory,
            new AgileDashboard_Milestone_Backlog_BacklogItemBuilder(),
            new RemainingEffortValueRetriever(Tracker_FormElementFactory::instance()),
            new ArtifactsInExplicitBacklogDao(),
            new Tracker_Artifact_PriorityDao()
        );

        $this->milestone_validator = new MilestoneResourceValidator(
            $planning_factory,
            $this->tracker_artifact_factory,
            $this->backlog_factory,
            $this->milestone_factory,
            $this->backlog_item_collection_factory,
        );

        $priority_manager = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            UserManager::instance(),
            $this->tracker_artifact_factory
        );

        $this->event_manager = EventManager::instance();

        $this->artifactlink_updater      = new ArtifactLinkUpdater($priority_manager, new ArtifactLinkUpdaterDataFormater());
        $this->milestone_content_updater = new MilestoneContentUpdater($this->artifactlink_updater);
        $this->resources_patcher         = new ResourcesPatcher(
            $this->artifactlink_updater,
            $this->tracker_artifact_factory,
            $priority_manager
        );

        $parent_tracker_retriever = new ParentTrackerRetriever($planning_factory);

        $sub_milestone_finder = new \AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder(
            \Tracker_HierarchyFactory::instance(),
            $planning_factory,
        );

        $this->milestone_representation_builder = new MilestoneRepresentationBuilder(
            $this->milestone_factory,
            $this->backlog_factory,
            $this->event_manager,
            $parent_tracker_retriever,
            $sub_milestone_finder,
            $planning_factory,
            new ProjectBackgroundConfiguration(new ProjectBackgroundDao())
        );

        $this->query_to_criterion_converter       = new QueryToCriterionStatusConverter();
        $this->query_to_criterion_only_all_status = new QueryToCriterionOnlyAllStatusConverter();

        $this->content_for_miletone_provider = ContentForMiletoneProvider::build($this->milestone_factory);
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        Header::allowOptions();
    }

    /**
     * Put children in a given milestone
     *
     * Put the new children of a given milestone.
     *
     * @url PUT {id}/milestones
     *
     * @param int $id    Id of the milestone
     * @param array $ids Ids of the new milestones {@from body}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function putSubmilestones($id, array $ids)
    {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $milestone->getProject()
        );

        try {
            $this->milestone_validator->validateSubmilestonesFromBodyContent($ids, $milestone, $user);
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (SubMilestoneAlreadyHasAParentException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (ElementCannotBeSubmilestoneException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (UserCannotReadSubMilestoneException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (UserCannotReadSubMilestoneException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (SubMilestoneDoesNotExistException $exception) {
            throw new RestException(404, $exception->getMessage());
        }

        try {
            $this->artifactlink_updater->update(
                $ids,
                $milestone->getArtifact(),
                $user,
                new FilterValidSubmilestones(
                    $this->milestone_factory,
                    $milestone
                ),
                Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD
            );
        } catch (ItemListedTwiceException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            //Do nothing
        }

        $this->sendAllowHeaderForSubmilestones();
    }

    /**
     * Patch children of a given milestone
     *
     * Add example:
     * <pre>
     * {
     *    "add": [
     *       { "id": 34 },
     *       { "id": 35 }
     *       ...
     *    ]
     * }
     * </pre>
     * Will add the submilestones 34 and 35 to the milestone
     *
     * @url PATCH {id}/milestones
     *
     * @param int   $id  Id of the milestone
     * @param array $add Submilestones to add in milestone {@from body}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function patchSubmilestones($id, ?array $add = null)
    {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $milestone->getProject()
        );

        try {
            if ($add) {
                $ids_to_add = [];
                foreach ($add as $submilestone) {
                    $ids_to_add[] = $submilestone['id'];
                }

                $this->milestone_validator->validateSubmilestonesFromBodyContent($ids_to_add, $milestone, $user);

                $this->resources_patcher->startTransaction();
                $this->artifactlink_updater->updateArtifactLinks(
                    $user,
                    $milestone->getArtifact(),
                    $ids_to_add,
                    [],
                    Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD
                );
                $this->resources_patcher->commit();
            }
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (SubMilestoneAlreadyHasAParentException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (ElementCannotBeSubmilestoneException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (UserCannotReadSubMilestoneException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (SubMilestoneDoesNotExistException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (ItemListedTwiceException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoArtifactLinkFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            //Do nothing
        }

        $this->sendAllowHeaderForSubmilestones();
    }

    /**
     * Get milestone
     *
     * Get the definition of a given the milestone
     *
     * Please note that the following fields are deprecated in favor of their
     * counterpart in 'resources':
     * <ul>
     *     <li>sub_milestones_uri</li>
     *     <li>backlog_uri</li>
     *     <li>content_uri</li>
     *     <li>cardwall_uri</li>
     *     <li>burndown_uri</li>
     * </ul>
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id Id of the milestone
     *
     * @return MilestoneRepresentation
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getId($id)
    {
        $this->checkAccess();
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $milestone->getProject()
        );

        $this->sendAllowHeadersForMilestone($milestone);

        $milestone_representation = $this->milestone_representation_builder->getMilestoneRepresentation(
            $milestone,
            $user,
            MilestoneRepresentation::ALL_FIELDS
        );

        return $milestone_representation;
    }

    /**
     * Return info about milestone if exists
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the milestone
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function optionsId($id)
    {
        Header::allowOptionsGet();
    }

    /**
     * @url OPTIONS {id}/milestones
     *
     * @param int $id ID of the milestone
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function optionsMilestones($id)
    {
        $this->sendAllowHeaderForSubmilestones();
    }

    /**
     * @url OPTIONS {id}/siblings
     *
     * @param int $id ID of the milestone
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function optionsSiblings($id)
    {
        $this->sendAllowHeaderForSiblings();
    }

    /**
     * Get Siblings
     *
     * Get siblings of the current milestone. The returned collection is ordered by id in reverse order.
     *
     * <p>
     * $query parameter is optional, by default we return all milestones. If <code>query={"status":"open"}</code>
     * then only open milestones are returned and if <code>query={"status":"closed"}</code> then only closed milestones
     * are returned.
     * </p>
     *
     * @url GET {id}/siblings
     * @access hybrid
     *
     * @param int    $id     Id of the milestone
     * @param string $query  JSON object of search criteria properties {@from path}
     * @param int    $limit  Number of elements displayed per page {@from path}{@min 1}{@max 10}
     * @param int    $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getSiblings($id, $query = '', $limit = 10, $offset = 0)
    {
        $this->checkAccess();
        $this->sendAllowHeaderForSubmilestones();
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $milestone->getProject()
        );

        try {
            $criterion = $this->query_to_criterion_converter->convert($query);
        } catch (MalformedQueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $request    = new SiblingMilestoneRequest($user, $milestone, $limit, $offset, $criterion);
        $milestones = $this->milestone_factory->getPaginatedSiblingMilestones($request);
        $this->sendPaginationHeaders($limit, $offset, $milestones->getTotalSize());

        $milestone_representations = $this->milestone_representation_builder->buildRepresentationsFromCollection(
            $milestones,
            $user,
            MilestoneRepresentation::SLIM
        );
        return $milestone_representations->getMilestonesRepresentations();
    }

    /**
     * Get sub-milestones
     *
     * Get the sub-milestones of a given milestone.
     * A sub-milestone is a decomposition of a milestone (for instance a Release has Sprints as submilestones)
     *
     * <p>
     * $query parameter is optional, by default we return all milestones. If
     * query={"status":"open"} then only open milestones are returned and if
     * query={"status":"closed"} then only closed milestones are returned.
     * </p>
     *
     * @url GET {id}/milestones
     * @access hybrid
     *
     * @param int    $id     Id of the milestone
     * @param string $fields Set of fields to return in the result {@choice all,slim}
     * @param string $query  JSON object of search criteria properties {@from path}
     * @param int    $limit  Number of elements displayed per page {@from path}
     * @param int    $offset Position of the first element to display {@from path}
     * @param string $order  In which order milestones are fetched. Default is asc {@from path}{@choice asc,desc}
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getMilestones(
        $id,
        $fields = MilestoneRepresentation::ALL_FIELDS,
        $query = '',
        $limit = 10,
        $offset = 0,
        $order = 'asc',
    ): array {
        $this->checkAccess();
        $this->sendAllowHeaderForSubmilestones();
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        try {
            $criterion = $this->query_to_criterion_converter->convert($query);
        } catch (MalformedQueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $request    = new SubMilestoneRequest($user, $milestone, $limit, $offset, $order, $criterion);
        $milestones = $this->milestone_factory->getPaginatedSubMilestones($request);
        $this->sendPaginationHeaders($limit, $offset, $milestones->getTotalSize());

        $milestone_representations = $this->milestone_representation_builder->buildRepresentationsFromCollection(
            $milestones,
            $user,
            $fields
        );
        return $milestone_representations->getMilestonesRepresentations();
    }

    /**
     * Get content
     *
     * Get the backlog items of a given milestone
     *
     * @url GET {id}/content
     * @access hybrid
     *
     * @param int $id     Id of the milestone
     * @param int $limit  Number of elements displayed per page {@min 0} {@max 100}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getContent($id, $limit = 10, $offset = 0)
    {
        $this->checkAccess();

        $milestone = $this->getMilestoneById($this->getCurrentUser(), $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->getCurrentUser(),
            $milestone->getProject()
        );

        $backlog_items = $this->content_for_miletone_provider->getContent(
            $milestone,
            $this->getCurrentUser(),
            $limit,
            $offset
        );

        $backlog_items_representations       = [];
        $backlog_item_representation_factory = $this->getBacklogItemRepresentationFactory();

        foreach ($backlog_items as $backlog_item) {
            $backlog_items_representations[] = $backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);
        }

        $this->sendAllowHeaderForContent();
        $this->sendPaginationHeaders($limit, $offset, $backlog_items->getTotalAvaialableSize());

        return $backlog_items_representations;
    }

    /**
     * @url OPTIONS {id}/content
     *
     * @param int $id Id of the milestone
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function optionsContent($id)
    {
        $this->sendAllowHeaderForContent();
    }

    /**
     * @throws RestException 403
     */
    private function checkIfUserCanChangePrioritiesInMilestone(Planning_Milestone $milestone, PFUser $user)
    {
        if (! $this->milestone_factory->userCanChangePrioritiesInMilestone($milestone, $user)) {
            throw new RestException(403, "User is not allowed to update this milestone because he can't change items' priorities");
        }
    }

    /**
     * Put content in a given milestone
     *
     * Put the new content of a given milestone.
     *
     * @url PUT {id}/content
     *
     * @param int $id    Id of the milestone
     * @param array $ids Ids of backlog items {@from body}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function putContent($id, array $ids)
    {
        $current_user = $this->getCurrentUser();
        $milestone    = $this->getMilestoneById($current_user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $milestone->getProject()
        );

        $this->checkIfUserCanChangePrioritiesInMilestone($milestone, $current_user);

        try {
            $this->milestone_validator->validateArtifactsFromBodyContent($ids, $milestone, $current_user);
            $this->milestone_content_updater->updateMilestoneContent($ids, $current_user, $milestone);
        } catch (ArtifactDoesNotExistException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (ArtifactIsNotInBacklogTrackerException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            //Do nothing
        }

        try {
            $this->artifactlink_updater->setOrderWithHistoryChangeLogging($ids, $id, $milestone->getProject()->getId());
        } catch (ItemListedTwiceException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $this->sendAllowHeaderForContent();
    }

    /**
     * Partial re-order of milestone content relative to one element
     *
     * <br>
     * Example:
     * <pre>
     * "order": {
     *   "ids" : [123, 789, 1001],
     *   "direction": "before",
     *   "compared_to": 456
     * }
     * </pre>
     *
     * <br>
     * Resulting order will be: <pre>[…, 123, 789, 1001, 456, …]</pre>
     *
     * <br>
     * Add example:
     * <pre>
     * "add": [
     *   {
     *     "id": 34
     *     "remove_from": 56
     *   },
     *   ...
     * ]
     * </pre>
     *
     * <br>
     * Will remove element id 34 from milestone 56 content and add it to current milestone content
     *
     * @url PATCH {id}/content
     *
     * @param int $id Id of the milestone
     * @param \Tuleap\Tracker\REST\Helpers\OrderRepresentation $order Order of the children {@from body}
     * @param array $add Ids to add/move to milestone content  {@from body} {@type BacklogAddRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 409
     */
    protected function patchContent($id, ?OrderRepresentation $order = null, ?array $add = null)
    {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);
        if (! $milestone) {
            throw new RestException(404, "Milestone not found");
        }

        $project = $milestone->getProject();
        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        $this->checkIfUserCanChangePrioritiesInMilestone($milestone, $user);

        try {
            if ($add) {
                $this->getMilestoneElementMover()->moveElementToMilestoneContent(
                    $milestone,
                    $user,
                    $add
                );
            }
        } catch (ArtifactDoesNotExistException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (ArtifactIsNotInBacklogTrackerException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            //Do nothing
        } catch (Tracker_NoArtifactLinkFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        try {
            if ($order) {
                $order->checkFormat();
                $this->milestone_validator->canOrderContent($user, $milestone, $order);
                $orderer = ArtifactsRankOrderer::build();
                $orderer->reorder($order, (string) $id, $project);
            }
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (OrderIdOutOfBoundException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }
        $this->sendAllowHeaderForContent();
    }

    /**
     * @url OPTIONS {id}/backlog
     *
     * @param int $id Id of the milestone
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function optionsBacklog($id)
    {
        $this->sendAllowHeaderForBacklog();
    }

    /**
     * Get backlog
     *
     * Get the backlog items of a given milestone that can be planned in a sub-milestone
     *
     * <p>
     * $query parameter is optional, by default we return only open milestones. If
     * <code>query={"status":"all"}</code> then open and closed milestones are returned.
     * </p>
     *
     * @url GET {id}/backlog
     * @access hybrid
     *
     * @param int $id     Id of the milestone
     * @param string $query  JSON object of search criteria properties {@from path}
     * @param int $limit  Number of elements displayed per page {@min 0} {@max 100}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getBacklog(int $id, string $query = '', int $limit = 10, int $offset = 0)
    {
        $this->checkAccess();

        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $milestone->getProject()
        );

        try {
            $criterion = $this->query_to_criterion_only_all_status->convert($query);
        } catch (MalformedQueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $paginated_backlog_item_representation_builder = new AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder(
            $this->getBacklogItemRepresentationFactory(),
            $this->backlog_item_collection_factory,
            $this->backlog_factory,
            new \Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao()
        );

        $paginated_backlog_items_representations = $paginated_backlog_item_representation_builder->getPaginatedBacklogItemsRepresentationsForMilestone($user, $milestone, $criterion, $limit, $offset);

        $this->sendAllowHeaderForBacklog();
        $this->sendPaginationHeaders($limit, $offset, $paginated_backlog_items_representations->getTotalSize());

        return $paginated_backlog_items_representations->getBacklogItemsRepresentations();
    }

    /**
     * Update backlog items priorities
     *
     * The array of ids given as argument will:
     * <ul>
     *  <li>update the priorities according to order in given array</li>
     * </ul>
     * <br />
     * <strong>WARNING:</strong> PUT will NOT add/remove element in backlog.
     * Remove from backlog doesn't make sense but add might be useful to deal
     * with inconsistent items. You can have a look at PATCH {id}/backlog for
     * add.
     *
     * @url PUT {id}/backlog
     *
     * @param int $id Id of the milestone
     * @param array $ids Ids of backlog items {@from body}{@type int}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function putBacklog($id, array $ids)
    {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $milestone->getProject()
        );

        $this->checkIfUserCanChangePrioritiesInMilestone($milestone, $user);

        try {
            $this->milestone_validator->validateArtifactIdsAreInUnplannedMilestone($ids, $milestone, $user);
        } catch (ArtifactIsNotInUnplannedBacklogItemsException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        try {
            $this->artifactlink_updater->setOrderWithHistoryChangeLogging($ids, $id, $milestone->getProject()->getId());
        } catch (ItemListedTwiceException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $this->sendAllowHeaderForBacklog();
    }

    /**
     * Partial re-order of milestone backlog relative to one element.
     *
     * <br>
     * Example:
     * <pre>
     * "order": {
     *   "ids" : [123, 789, 1001],
     *   "direction": "before",
     *   "compared_to": 456
     * },
     * "add": {
     *   [123]
     * }
     * </pre>
     *
     * <br>
     * Resulting order will be: <pre>[…, 123, 789, 1001, 456, …]</pre>
     *
     * <br>
     * Add example:
     * <pre>
     * "add": [
     *   {
     *     "id": 34
     *     "remove_from": 56
     *   },
     *   ...
     * ]
     * </pre>
     *
     * <br>
     * Will remove element id 34 from milestone 56 backlog and add it to current backlog
     *
     * @url PATCH {id}/backlog
     *
     * @param int                                                $id    Id of the milestone Item
     * @param \Tuleap\Tracker\REST\Helpers\OrderRepresentation $order Order of the children {@from body}
     * @param array                                              $add    Ids to add/move to milestone backlog {@from body} {@type BacklogAddRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 409
     */
    protected function patchBacklog($id, ?OrderRepresentation $order = null, ?array $add = null)
    {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);
        $project   = $milestone->getProject();

        if (! $milestone) {
            throw new RestException(404, "Milestone not found");
        }

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        $this->checkIfUserCanChangePrioritiesInMilestone($milestone, $user);

        $to_add = [];
        try {
            if ($add) {
                $to_add = $this->getMilestoneElementMover()->moveElement($user, $add, $milestone);
            }
        } catch (Tracker_NoChangeException $exception) {
            // nothing to do
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        try {
            if ($order) {
                $order->checkFormat();
                $this->milestone_validator->validateArtifactIdsAreInUnplannedMilestone(
                    $this->filterOutAddedElements($order, $to_add),
                    $milestone,
                    $user
                );

                $orderer = ArtifactsRankOrderer::build();
                $orderer->reorder($order, (string) $id, $project);
            }
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (ArtifactIsNotInUnplannedBacklogItemsException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function filterOutAddedElements(OrderRepresentation $order, ?array $to_add = null)
    {
        $ids_to_validate = array_merge($order->ids, [$order->compared_to]);
        if (is_array($to_add)) {
            return array_diff($ids_to_validate, $to_add);
        } else {
            return $ids_to_validate;
        }
    }

    /**
     * Add an item to the backlog of a milestone
     *
     * Add an item to the backlog of a milestone
     *
     * The item must  be of the allowed types (defined in the planning configuration).
     * The body of the request should be of the form :
     * {
     *      "artifact" : {
     *          "id" : 458
     *      }
     * }
     *
     * @url POST {id}/backlog
     * @status 201
     *
     * @param int                  $id   Id of the milestone
     * @param BacklogItemReference $item Reference of the Backlog Item {@from body} {@type BacklogItemReference}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function postBacklog($id, BacklogItemReference $item)
    {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $milestone->getProject()
        );

        $this->checkIfUserCanChangePrioritiesInMilestone($milestone, $user);

        $item_id  = $item->getArtifactId();
        $artifact = $this->getBacklogItemAsArtifact($user, $item_id);

        $allowed_trackers = $this->backlog_factory->getBacklog($milestone)->getDescendantTrackers();
        if (! $this->milestone_validator->canBacklogItemBeAddedToMilestone($artifact, $allowed_trackers)) {
            throw new RestException(400, "Item of type '" . $artifact->getTracker()->getName() . "' cannot be added.");
        }

        try {
            $this->milestone_content_updater->appendElementToMilestoneBacklog($item_id, $user, $milestone);
        } catch (Tracker_NoChangeException $e) {
        }

        $this->sendAllowHeaderForBacklog();
    }

    private function getBacklogItemAsArtifact($user, $artifact_id)
    {
        $artifact = $this->tracker_artifact_factory->getArtifactById($artifact_id);

        if (! $artifact) {
            throw new RestException(400, 'Item does not exist');
        }

        if (! $artifact->userCanView()) {
            throw new RestException(403, 'Cannot link this item');
        }

        return $artifact;
    }

    /**
     * Carwall options
     *
     * @url OPTIONS {id}/cardwall
     *
     * @param int $id Id of the milestone
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function optionsCardwall($id)
    {
        $this->sendAllowHeadersForCardwall();
    }

    /**
     * Get a Cardwall
     *
     * @url GET {id}/cardwall
     * @access hybrid
     *
     * @param int $id Id of the milestone
     *
     *
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getCardwall($id)
    {
        $this->checkAccess();

        $cardwall  = null;
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $milestone->getProject()
        );

        $this->event_manager->processEvent(
            self::AGILEDASHBOARD_EVENT_REST_GET_CARDWALL,
            [
                'version'   => 'v1',
                'milestone' => $milestone,
                'cardwall'  => &$cardwall,
            ]
        );

        return $cardwall;
    }

    /**
     * Options Burdown data
     *
     * @url OPTIONS {id}/burndown
     *
     * @param int $id Id of the milestone
     *
     * @return \Tuleap\Tracker\REST\Artifact\BurndownRepresentation
     */
    public function optionsBurndown($id)
    {
        $this->sendAllowHeadersForBurndown();
    }

    /**
     * Get Burdown data
     *
     * @url GET {id}/burndown
     * @access hybrid
     *
     * @param int $id Id of the milestone
     *
     * @return \Tuleap\Tracker\REST\Artifact\BurndownRepresentation
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getBurndown($id)
    {
        $this->checkAccess();

        $burndown  = null;
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $milestone->getProject()
        );

        $this->event_manager->processEvent(
            self::AGILEDASHBOARD_EVENT_REST_GET_BURNDOWN,
            [
                'version'   => 'v1',
                'user'      => $user,
                'milestone' => $milestone,
                'burndown'  => &$burndown,
            ]
        );
        return $burndown;
    }

    private function getMilestoneById(PFUser $user, $id)
    {
        try {
            $milestone = $this->milestone_factory->getValidatedBareMilestoneByArtifactId($user, $id);
        } catch (\MilestonePermissionDeniedException $e) {
            if ($this->is_authenticated) {
                throw new RestException(403);
            }
            throw new RestException(401);
        }

        if (! $milestone) {
            throw new RestException(404);
        }

        ProjectAuthorization::userCanAccessProject($user, $milestone->getProject(), new URLVerification());

        return $milestone;
    }

    private function getCurrentUser()
    {
        return UserManager::instance()->getCurrentUser();
    }

    private function sendAllowHeaderForContent()
    {
        Header::allowOptionsGetPutPatch();
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeaderForBacklog()
    {
        Header::allowOptionsGetPutPostPatch();
    }

    private function sendAllowHeaderForSubmilestones()
    {
        Header::allowOptionsGetPut();
    }

    private function sendAllowHeadersForMilestone($milestone)
    {
        $date = $milestone->getLastModifiedDate();
        Header::allowOptionsGet();
        Header::lastModified($date);
    }

    private function sendAllowHeadersForCardwall()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForBurndown()
    {
        Header::allowOptionsGet();
    }

    private function getBacklogItemRepresentationFactory()
    {
        $color_builder = new BackgroundColorBuilder(new BindDecoratorRetriever());
        return new BacklogItemRepresentationFactory(
            $color_builder,
            UserManager::instance(),
            new ProjectBackgroundConfiguration(new ProjectBackgroundDao())
        );
    }

    private function sendAllowHeaderForSiblings()
    {
        Header::allowOptionsGet();
    }

    private function getMilestoneElementMover(): MilestoneElementMover
    {
        $db_transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

        $milestone_parent_linker = new MilestoneParentLinker(
            $this->milestone_factory,
            $this->backlog_factory
        );

        return new MilestoneElementMover(
            $this->resources_patcher,
            $this->milestone_validator,
            $this->artifactlink_updater,
            $db_transaction_executor,
            $this->tracker_artifact_factory,
            $milestone_parent_linker,
            new ArtifactsInExplicitBacklogDao()
        );
    }
}
