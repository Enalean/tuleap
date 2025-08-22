<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Kanban\REST\v1;

use BackendLogger;
use DateTime;
use EventManager;
use Exception;
use Luracast\Restler\RestException;
use PFUser;
use ReferenceManager;
use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_Changeset_CommentDao;
use Tracker_Artifact_Changeset_NewChangesetFieldsValidator;
use Tracker_Artifact_PriorityHistoryChange;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElementFactory;
use Tracker_NoChangeException;
use Tracker_Permission_PermissionRetrieveAssignee;
use Tracker_Permission_PermissionsSerializer;
use Tracker_Report;
use Tracker_ReportFactory;
use Tracker_Workflow_GlobalRulesViolationException;
use Tracker_Workflow_Transition_InvalidConditionForTransitionException;
use TrackerFactory;
use TransitionFactory;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Kanban\ColumnIdentifier;
use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanActionsChecker;
use Tuleap\Kanban\KanbanCannotAccessException;
use Tuleap\Kanban\KanbanColumnDao;
use Tuleap\Kanban\KanbanColumnFactory;
use Tuleap\Kanban\KanbanColumnManager;
use Tuleap\Kanban\KanbanCumulativeFlowDiagramDao;
use Tuleap\Kanban\KanbanDao;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Kanban\KanbanItemDao;
use Tuleap\Kanban\KanbanItemManager;
use Tuleap\Kanban\KanbanNotFoundException;
use Tuleap\Kanban\KanbanPermissionsManager;
use Tuleap\Kanban\KanbanSemanticStatusAllColumnIdsNotProvidedException;
use Tuleap\Kanban\KanbanSemanticStatusBasedOnASharedFieldException;
use Tuleap\Kanban\KanbanSemanticStatusColumnIdsNotInOpenSemanticException;
use Tuleap\Kanban\KanbanSemanticStatusNotBoundToStaticValuesException;
use Tuleap\Kanban\KanbanSemanticStatusNotDefinedException;
use Tuleap\Kanban\KanbanStatisticsAggregator;
use Tuleap\Kanban\KanbanUserNotAdminException;
use Tuleap\Kanban\KanbanUserPreferences;
use Tuleap\Kanban\RealTime\KanbanRealtimeStructureMessageSender;
use Tuleap\Kanban\RealTimeMercure\KanbanStructureRealTimeMercure;
use Tuleap\Kanban\RecentlyVisited\RecentlyVisitedKanbanDao;
use Tuleap\Kanban\REST\v1\CumulativeFlowDiagram\DiagramRepresentation;
use Tuleap\Kanban\REST\v1\CumulativeFlowDiagram\DiagramRepresentationBuilder;
use Tuleap\Kanban\REST\v1\CumulativeFlowDiagram\OrderedColumnRepresentationsBuilder;
use Tuleap\Kanban\REST\v1\CumulativeFlowDiagram\TooManyPointsException;
use Tuleap\Kanban\REST\v1\TrackerReport\FilteredDiagramRepresentationBuilder;
use Tuleap\Kanban\REST\v1\TrackerReport\FilteredItemCollectionRepresentationBuilder;
use Tuleap\Kanban\TrackerReport\ReportFilterFromWhereBuilder;
use Tuleap\Kanban\TrackerReport\TrackerReportDao;
use Tuleap\Kanban\TrackerReport\TrackerReportUpdater;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\RealTimeMercure\Client;
use Tuleap\RealTimeMercure\ClientBuilder;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetFieldValueSaver;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetPostProcessor;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetValidator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsQueuer;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\Dao\PriorityDao;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Permission\SubmissionPermissionVerifier;
use Tuleap\Tracker\REST\Helpers\ArtifactsRankOrderer;
use Tuleap\Tracker\REST\Helpers\IdsFromBodyAreNotUniqueException;
use Tuleap\Tracker\REST\Helpers\OrderIdOutOfBoundException;
use Tuleap\Tracker\REST\Helpers\OrderRepresentation;
use Tuleap\Tracker\REST\Helpers\OrderValidator;
use Tuleap\Tracker\REST\v1\Report\MatchingIdsOrderer;
use Tuleap\Tracker\REST\v1\ReportArtifactFactory;
use Tuleap\Tracker\Rule\FirstValidValueAccordingToDependenciesRetriever;
use Tuleap\Tracker\Semantic\Status\CachedSemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Status\SemanticStatusClosedValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\SemanticStatusFieldRetriever;
use Tuleap\Tracker\Semantic\Status\SemanticStatusNotDefinedException;
use Tuleap\Tracker\Semantic\Status\SemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Status\StatusSemanticDAO;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory;
use Tuleap\Tracker\Semantic\Title\CachedSemanticTitleFieldRetriever;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\ValidValuesAccordingToTransitionsRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use UserManager;
use Workflow_Transition_ConditionFactory;

final class KanbanResource extends AuthenticatedResource
{
    public const MAX_LIMIT        = 100;
    public const HTTP_CLIENT_UUID = 'HTTP_X_CLIENT_UUID';

    private KanbanFactory $kanban_factory;
    private KanbanItemDao $kanban_item_dao;
    private KanbanDao $kanban_dao;
    private TrackerFactory $tracker_factory;
    private Tracker_ArtifactFactory $artifact_factory;
    private KanbanColumnFactory $kanban_column_factory;
    private Tracker_FormElementFactory $form_element_factory;
    private KanbanPermissionsManager $permissions_manager;
    private KanbanUserPreferences $user_preferences;
    private KanbanStatisticsAggregator $statistics_aggregator;
    private KanbanColumnManager $kanban_column_manager;
    private KanbanItemManager $kanban_item_manager;
    private KanbanActionsChecker $kanban_actions_checker;
    private NodeJSClient $node_js_client;
    private Tracker_Permission_PermissionsSerializer $permissions_serializer;
    private QueryParameterParser $query_parser;
    private ItemCollectionRepresentationBuilder $item_collection_builder;
    private FilteredItemCollectionRepresentationBuilder $filtered_item_collection_builder;
    private TimeInfoFactory $time_info_factory;
    private Tracker_ReportFactory $report_factory;
    private DiagramRepresentationBuilder $diagram_builder;
    private FilteredDiagramRepresentationBuilder $filtered_diagram_builder;
    private ItemRepresentationBuilder $item_representation_builder;
    private TrackerReportUpdater $tracker_report_updater;
    private Client $mercure_client;
    private KanbanStructureRealTimeMercure $structure_realtime_kanban;
    private KanbanRepresentationBuilder $kanban_representation_builder;

    public function __construct()
    {
        $this->kanban_item_dao     = new KanbanItemDao();
        $this->kanban_item_manager = new KanbanItemManager($this->kanban_item_dao);
        $this->tracker_factory     = TrackerFactory::instance();

        $this->kanban_dao     = new KanbanDao();
        $this->kanban_factory = new KanbanFactory(
            $this->tracker_factory,
            $this->kanban_dao
        );

        $this->user_preferences      = new KanbanUserPreferences();
        $status_semantic_dao         = new StatusSemanticDAO();
        $this->kanban_column_factory = new KanbanColumnFactory(
            new KanbanColumnDao(),
            $this->user_preferences,
            new SemanticStatusRetriever(
                new SemanticStatusFieldRetriever($status_semantic_dao, Tracker_FormElementFactory::instance()),
                $status_semantic_dao,
            ),
        );

        $this->artifact_factory = Tracker_ArtifactFactory::instance();

        $this->form_element_factory = Tracker_FormElementFactory::instance();
        $this->permissions_manager  = new KanbanPermissionsManager();

        $this->kanban_actions_checker = new KanbanActionsChecker(
            $this->tracker_factory,
            $this->permissions_manager,
            $this->form_element_factory,
            SubmissionPermissionVerifier::instance(),
            CachedSemanticTitleFieldRetriever::instance(),
            CachedSemanticStatusRetriever::instance(),
        );

        $this->kanban_representation_builder = new KanbanRepresentationBuilder(
            $this->user_preferences,
            $this->kanban_column_factory,
            $this->kanban_actions_checker
        );

        $this->kanban_column_manager = new KanbanColumnManager(
            new KanbanColumnDao(),
            new BindStaticValueDao(),
            $this->kanban_actions_checker
        );

        $this->statistics_aggregator = new KanbanStatisticsAggregator(EventManager::instance());

        $this->node_js_client            = new NodeJSClient(
            HttpClientFactory::createClientForInternalTuleapUse(),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            BackendLogger::getDefaultLogger()
        );
        $this->mercure_client            = ClientBuilder::build(ClientBuilder::DEFAULTPATH, new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()));
        $this->structure_realtime_kanban = new KanbanStructureRealTimeMercure($this->mercure_client);
        $this->permissions_serializer    = new Tracker_Permission_PermissionsSerializer(
            new Tracker_Permission_PermissionRetrieveAssignee(UserManager::instance())
        );
        $this->query_parser              = new QueryParameterParser(
            new JsonDecoder()
        );

        $this->time_info_factory = new TimeInfoFactory(
            $this->kanban_item_dao
        );

        $color_builder                     = new BackgroundColorBuilder(new BindDecoratorRetriever());
        $this->item_representation_builder = new ItemRepresentationBuilder(
            $this->kanban_item_manager,
            $this->time_info_factory,
            UserManager::instance(),
            $color_builder
        );

        $this->item_collection_builder = new ItemCollectionRepresentationBuilder(
            $this->kanban_item_dao,
            $this->artifact_factory,
            $this->item_representation_builder
        );

        $this->report_factory = Tracker_ReportFactory::instance();

        $report_artifact_factory = new ReportArtifactFactory(
            $this->artifact_factory,
            new MatchingIdsOrderer(new PriorityDao()),
        );

        $report_from_where_builder = new ReportFilterFromWhereBuilder();

        $this->filtered_item_collection_builder = new FilteredItemCollectionRepresentationBuilder(
            $report_from_where_builder,
            $report_artifact_factory,
            $this->item_representation_builder,
        );

        $ordered_column_representation_builder = new OrderedColumnRepresentationsBuilder(
            $this->kanban_column_factory,
            $this->artifact_factory
        );

        $cumulative_flow_diagram_dao = new KanbanCumulativeFlowDiagramDao();

        $this->diagram_builder = new DiagramRepresentationBuilder(
            $cumulative_flow_diagram_dao,
            $ordered_column_representation_builder
        );

        $this->filtered_diagram_builder = new FilteredDiagramRepresentationBuilder(
            $cumulative_flow_diagram_dao,
            $ordered_column_representation_builder
        );

        $this->tracker_report_updater = new TrackerReportUpdater(new TrackerReportDao());
    }

    /**
     * @url OPTIONS
     */
    public function options(): void
    {
        Header::allowOptionsGetPatchDelete();
    }

    /**
     * Get kanban
     *
     * Get the definition of a given kanban
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id Id of the kanban
     *
     * @return KanbanRepresentation
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getId($id)
    {
        $this->checkAccess();
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $this->getKanbanProject($kanban)
        );

        $kanban_representation = $this->kanban_representation_builder->build($kanban, $user);

        Header::allowOptionsGetPatchDelete();
        return $kanban_representation;
    }

    /**
     * Patch kanban
     *
     * Patch properties of a given kanban
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * <br>
     * To update the label of a kanban:
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"label": "The new label"<br>
     * }
     * </pre>
     *
     * <br>
     * To collapse a column (will be saved in current user preferences):
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"collapse_column": {<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;"column_id": 1337,<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;"value": true      // false to expand<br>
     * &nbsp;&nbsp;}<br>
     * }
     * </pre>
     *
     * <br>
     * To collapse the backlog (the same for archive):
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"collapse_backlog": true  // false to expand<br>
     * }
     * </pre>
     *
     * @url PATCH {id}
     * @access protected
     *
     * @param int                                                       $id               Id of the kanban
     * @param string                                                    $label            The new label {@from body}
     * @param bool                                                      $is_promoted      Is the kanban promoted? {@from body} {@required false}
     * @param \Tuleap\Kanban\REST\v1\KanbanCollapseColumnRepresentation $collapse_column  The column to collapse (save in user prefs) {@from body}
     * @param bool                                                      $collapse_archive True to collapse the archive (save in user prefs) {@from body}
     * @param bool                                                      $collapse_backlog True to collapse the backlog (save in user prefs) {@from body}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function patchId(
        $id,
        $label = null,
        $is_promoted = null,
        ?KanbanCollapseColumnRepresentation $collapse_column = null,
        $collapse_archive = null,
        $collapse_backlog = null,
    ): void {
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $this->getKanbanProject($kanban)
        );

        if ($label) {
            $this->checkUserCanUpdateKanban($user, $kanban);
            $this->kanban_dao->updateLabel($id, $label);
            $this->statistics_aggregator->addKanbanRenamingHit(
                $this->getProjectIdForKanban($kanban)
            );

            $kanban_sender = new KanbanRealtimeStructureMessageSender(
                $this->tracker_factory,
                $this->structure_realtime_kanban,
                $this->node_js_client,
                $this->permissions_serializer
            );
            $kanban_sender->sendMessageStructure($kanban, 'kanban:edit', $user, \HTTPRequest::instance(), $label);
        }

        if ($is_promoted !== null) {
            $this->checkUserCanUpdateKanban($user, $kanban);
            $this->kanban_dao->updatePromotion($id, $is_promoted);
            $this->statistics_aggregator->addKanbanPromotedHit(
                $this->getProjectIdForKanban($kanban)
            );
        }

        if ($collapse_column) {
            if (! $this->columnIsInTracker($kanban, $user, $collapse_column->column_id)) {
                throw new RestException(404, 'Cannot collapse unknown column');
            }
            if ($collapse_column->value == true) {
                $this->user_preferences->closeColumn($kanban, $collapse_column->column_id, $user);
            } else {
                $this->user_preferences->openColumn($kanban, $collapse_column->column_id, $user);
            }
            $this->statistics_aggregator->addExpandCollapseColumnHit(
                $this->getProjectIdForKanban($kanban)
            );
        }

        if ($collapse_archive !== null) {
            if ($collapse_archive) {
                $this->user_preferences->closeArchive($kanban, $user);
            } else {
                $this->user_preferences->openArchive($kanban, $user);
            }
            $this->statistics_aggregator->addExpandCollapseColumnHit(
                $this->getProjectIdForKanban($kanban)
            );
        }

        if ($collapse_backlog !== null) {
            if ($collapse_backlog) {
                $this->user_preferences->closeBacklog($kanban, $user);
            } else {
                $this->user_preferences->openBacklog($kanban, $user);
            }
            $this->statistics_aggregator->addExpandCollapseColumnHit(
                $this->getProjectIdForKanban($kanban)
            );
        }

        Header::allowOptionsGetPatchDelete();
    }

    /**
     * Return info about milestone if exists
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the Kanban
     */
    public function optionsId($id): void
    {
        Header::allowOptionsGetPatchDelete();
    }

    /**
     * Get backlog
     *
     * Get the backlog of a given kanban
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * <p><b>query</b> is optional. When filled, it is a json object with:</p>
     * <p>an integer "tracker_report_id" to filter kanban items corresponding to the
     *      given Tracker report id. <br></p>
     *
     *      Example: <pre>{"tracker_report_id":41}</pre>
     *
     * <br>
     * <p>Reports using the field bound to the "Status" semantic may not filter items
     *      the way you expect them to. For example, using a Tracker report with a "Status"
     *      criteria with "Status" = "On going" will return an empty column. Items in
     *      the Backlog column have an empty value for "Status", they can't have "On going"
     *      and "empty" values at the same time.</p>
     *
     * @url GET {id}/backlog
     * @access hybrid
     *
     * @param int $id       Id of the kanban
     * @param string $query Search string in json format
     * @param int $limit    Number of elements displayed per page
     * @param int $offset   Position of the first element to display
     *
     * @return ItemCollectionRepresentation
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getBacklog($id, $query = '', $limit = 10, $offset = 0)
    {
        $this->checkAccess();
        $user              = $this->getCurrentUser();
        $kanban            = $this->getKanban($user, $id);
        $column_identifier = new ColumnIdentifier(ColumnIdentifier::BACKLOG_COLUMN);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $this->getKanbanProject($kanban)
        );

        if ($query !== '') {
            $tracker_report_id    = $this->getTrackerReportIdFromQuery($query);
            $report               = $this->getReport($user, $kanban, $tracker_report_id);
            $items_representation = $this->filtered_item_collection_builder->build(
                $column_identifier,
                $user,
                $report,
                $limit,
                $offset
            );
        } else {
            $items_representation = $this->item_collection_builder->build(
                $column_identifier,
                $user,
                $kanban,
                $limit,
                $offset
            );
        }

        Header::allowOptionsGet();
        Header::sendPaginationHeaders($limit, $offset, $items_representation->total_size, self::MAX_LIMIT);

        return $items_representation;
    }

    private function getTrackerReportIdFromQuery(string $query): int
    {
        try {
            $tracker_report_id = $this->query_parser->getInt($query, 'tracker_report_id');
        } catch (QueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        return $tracker_report_id;
    }

    private function checkUserCanUpdateKanban(PFUser $user, Kanban $kanban): void
    {
        if (! $this->isUserAdmin($user, $kanban)) {
            throw new RestException(403);
        }
    }

    private function isUserAdmin(PFUser $user, Kanban $kanban): bool
    {
        $tracker = $this->getTrackerForKanban($kanban);

        return $this->permissions_manager->userCanAdministrate(
            $user,
            $tracker->getProject()
        );
    }

    /**
     * Partial re-order of Kanban backlog items
     *
     * Partial re-order of Kanban backlog items
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url PATCH {id}/backlog
     *
     * @param int                                              $id          Id of the Kanban
     * @param \Tuleap\Tracker\REST\Helpers\OrderRepresentation $order       Order of the children {@from body}
     * @param \Tuleap\Kanban\REST\v1\KanbanAddRepresentation   $add         Ids to add to Kanban backlog {@from body}
     * @param string                                           $from_column Id of the column the item is coming from (when moving an item) {@from body}
     *
     * @throws RestException 400
     * @throws RestException 403
     */
    protected function patchBacklog(
        $id,
        ?OrderRepresentation $order = null,
        ?KanbanAddRepresentation $add = null,
        $from_column = null,
    ): void {
        try {
            $current_user = UserManager::instance()->getCurrentUser();
            $kanban       = $this->kanban_factory->getKanban($current_user, $id);
        } catch (KanbanNotFoundException $exception) {
            throw new RestException(404);
        } catch (KanbanCannotAccessException $exception) {
            throw new RestException(403);
        }

        $project = $this->getKanbanProject($kanban);
        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        if ($add) {
            $add->checkFormat();
            $this->validateIdsInAddAreInKanbanTracker($kanban, $add);

            try {
                $this->moveArtifactsInBacklog($kanban, $current_user, $add);
            } catch (Tracker_NoChangeException $exception) {
            } catch (Exception $exception) {
                throw new RestException(500, $exception->getMessage());
            }
        }

        if ($order) {
            $order->checkFormat();
            $kanban_backlog_items = $this->getKanbanBacklogItemIds($kanban->getTrackerId());
            $order_validator      = new OrderValidator($kanban_backlog_items);

            try {
                $order_validator->validate($order);
            } catch (IdsFromBodyAreNotUniqueException $exception) {
                throw new RestException(409, $exception->getMessage());
            } catch (OrderIdOutOfBoundException $exception) {
                throw new RestException(409, $exception->getMessage());
            } catch (Exception $exception) {
                throw new RestException(500, $exception->getMessage());
            }

            $orderer = ArtifactsRankOrderer::build();
            $orderer->reorder($order, Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT, $project);
        }

        if ($add || $order) {
            $this->statistics_aggregator->addCardDragAndDropHit(
                $this->getProjectIdForKanban($kanban)
            );
        }
    }

    private function getStatusField(Kanban $kanban, PFUser $user): ListField
    {
        $tracker      = $this->getTrackerForKanban($kanban);
        $status_field = $tracker->getStatusField();

        if (! $status_field) {
            throw new RestException(403);
        }

        if (! $status_field->userCanRead($user)) {
            throw new RestException(403);
        }

        return $status_field;
    }

    private function moveArtifactsInBacklog(
        Kanban $kanban,
        PFUser $user,
        KanbanAddRepresentation $add,
    ): void {
        $this->moveArtifactsInColumn($kanban, $user, $add, Tracker_FormElement_Field_List_Bind::NONE_VALUE);
    }

    private function moveArtifactsInArchive(
        Kanban $kanban,
        PFUser $user,
        KanbanAddRepresentation $add,
    ): void {
        try {
            foreach ($add->ids as $artifact_id) {
                $artifact     = $this->artifact_factory->getArtifactById($artifact_id);
                $status_field = $this->getStatusField($kanban, $user);

                if (! $artifact) {
                    continue;
                }

                try {
                    $closed_value = $this->getStatusValueRetriever()->getFirstClosedValueUserCanRead(
                        $user,
                        $artifact
                    );
                } catch (NoPossibleValueException $e) {
                    throw new RestException(400, $e->getMessage());
                }

                $this->moveArtifact($artifact, $user, $status_field, $closed_value->getId());
            }
        } catch (SemanticStatusNotDefinedException $exception) {
            throw new RestException(403);
        } catch (SemanticStatusClosedValueNotFoundException $exception) {
            throw new RestException(500, 'Cannot found any suitable value corresponding to the [archive] column.');
        }
    }

    private function moveArtifactsInColumn(
        Kanban $kanban,
        PFUser $user,
        KanbanAddRepresentation $add,
        int $column_id,
    ): void {
        foreach ($add->ids as $artifact_id) {
            $artifact     = $this->artifact_factory->getArtifactById($artifact_id);
            $status_field = $this->getStatusField($kanban, $user);

            if (! $artifact) {
                continue;
            }

            if (! $artifact->userCanView($user)) {
                throw new RestException(403, 'You cannot access this kanban item.');
            }

            $this->moveArtifact($artifact, $user, $status_field, $column_id);
        }
    }

    private function validateIdsInAddAreInKanbanTracker(
        Kanban $kanban,
        KanbanAddRepresentation $add,
    ): bool {
        $all_kanban_item_ids = [];
        foreach ($this->kanban_item_dao->getAllKanbanItemIds($kanban->getTrackerId()) as $row) {
            $all_kanban_item_ids[] = $row['id'];
        }
        return count(array_diff($add->ids, $all_kanban_item_ids)) === 0;
    }

    /**
     * @return array<int, bool>
     */
    private function getKanbanBacklogItemIds(int $tracker_id): array
    {
        $backlog_item_ids = [];
        foreach ($this->kanban_item_dao->getKanbanBacklogItemIds($tracker_id) as $artifact) {
            $backlog_item_ids[$artifact['id']] = true;
        }

        return $backlog_item_ids;
    }

    /**
     * @url OPTIONS {id}/backlog
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @param string $id Id of the Kanban
     */
    public function optionsBacklog($id): void
    {
        Header::allowOptionsGetPatch();
    }

    /**
     * Get archive
     *
     * Get the archived items of a given kanban
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * <p><b>query</b> is optional. When filled, it is a json object with:</p>
     * <p>an integer "tracker_report_id" to filter kanban items corresponding to the
     *      given Tracker report id. <br></p>
     *
     *      Example: <pre>{"tracker_report_id":41}</pre>
     *
     * <br>
     * <p>Reports using the field bound to the "Status" semantic may not filter items
     *      the way you expect them to. For example, using a Tracker report with a "Status"
     *      criteria with "Status" = "On going" will return an empty column. Items in
     *      the Archive column have one of the "closed" values for "Status", they can't
     *      have "On going" and "closed" values at the same time.</p>
     *
     * @url GET {id}/archive
     * @access hybrid
     *
     * @param int $id       Id of the kanban
     * @param string $query Search string in json format
     * @param int $limit    Number of elements displayed per page
     * @param int $offset   Position of the first element to display
     *
     * @return ItemCollectionRepresentation
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getArchive($id, $query = '', $limit = 10, $offset = 0)
    {
        $this->checkAccess();
        $user              = $this->getCurrentUser();
        $kanban            = $this->getKanban($user, $id);
        $column_identifier = new ColumnIdentifier(ColumnIdentifier::ARCHIVE_COLUMN);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $this->getKanbanProject($kanban)
        );

        if ($query !== '') {
            $tracker_report_id    = $this->getTrackerReportIdFromQuery($query);
            $report               = $this->getReport($user, $kanban, $tracker_report_id);
            $items_representation = $this->filtered_item_collection_builder->build(
                $column_identifier,
                $user,
                $report,
                $limit,
                $offset
            );
        } else {
            $items_representation = $this->item_collection_builder->build(
                $column_identifier,
                $user,
                $kanban,
                $limit,
                $offset
            );
        }

        Header::allowOptionsGet();
        Header::sendPaginationHeaders($limit, $offset, $items_representation->total_size, self::MAX_LIMIT);

        return $items_representation;
    }

    /**
     * Partial re-order of Kanban archive items
     *
     * Partial re-order of Kanban archive items
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url PATCH {id}/archive
     *
     * @param int                                                $id          Id of the Kanban
     * @param \Tuleap\Tracker\REST\Helpers\OrderRepresentation $order       Order of the children {@from body}
     * @param \Tuleap\Kanban\REST\v1\KanbanAddRepresentation     $add         Ids to add to Kanban backlog {@from body}
     * @param string                                             $from_column Id of the column the item is coming from (when moving an item) {@from body}
     *
     * @throws RestException 400
     * @throws RestException 403
     */
    protected function patchArchive(
        $id,
        ?OrderRepresentation $order = null,
        ?KanbanAddRepresentation $add = null,
        $from_column = null,
    ): void {
        try {
            $current_user = UserManager::instance()->getCurrentUser();
            $kanban       = $this->kanban_factory->getKanban($current_user, $id);
        } catch (KanbanNotFoundException $exception) {
            throw new RestException(404);
        } catch (KanbanCannotAccessException $exception) {
            throw new RestException(403);
        }

        $project = $this->getKanbanProject($kanban);
        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        if ($add) {
            $add->checkFormat();
            $this->validateIdsInAddAreInKanbanTracker($kanban, $add);

            try {
                $this->moveArtifactsInArchive($kanban, $current_user, $add);
            } catch (Tracker_NoChangeException $exception) {
            } catch (Exception $exception) {
                throw new RestException(500, $exception->getMessage());
            }
        }

        if ($order) {
            $order->checkFormat();
            $kanban_archive_items = $this->getKanbanArchiveItemIds($kanban->getTrackerId());
            $order_validator      = new OrderValidator($kanban_archive_items);

            try {
                $order_validator->validate($order);
            } catch (IdsFromBodyAreNotUniqueException $exception) {
                throw new RestException(409, $exception->getMessage());
            } catch (OrderIdOutOfBoundException $exception) {
                throw new RestException(409, $exception->getMessage());
            } catch (Exception $exception) {
                throw new RestException(500, $exception->getMessage());
            }

            $orderer = ArtifactsRankOrderer::build();
            $orderer->reorder($order, Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT, $project);
        }

        if ($add || $order) {
            $this->statistics_aggregator->addCardDragAndDropHit(
                $this->getProjectIdForKanban($kanban)
            );
        }
    }

    /**
     * @return array<int, bool>
     */
    private function getKanbanArchiveItemIds(int $tracker_id): array
    {
        $archive_item_ids = [];
        foreach ($this->kanban_item_dao->getKanbanArchiveItemIds($tracker_id) as $artifact) {
            $archive_item_ids[$artifact['id']] = true;
        }

        return $archive_item_ids;
    }

    /**
     * @url OPTIONS {id}/archive
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @param string $id Id of the Kanban
     */
    public function optionsArchive($id): void
    {
        Header::allowOptionsGetPatch();
    }

    /**
     * Get items
     *
     * Get the items of a given kanban in a given column
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * <p><b>query</b> is optional. When filled, it is a json object with:</p>
     * <p>an integer "tracker_report_id" to filter kanban items corresponding to the
     *      given Tracker report id. <br></p>
     *
     *      Example: <pre>{"tracker_report_id":41}</pre>
     *
     * <br>
     * <p>Reports using the field bound to the "Status" semantic may not filter items
     *      the way you expect them to. For example, using a Tracker report with a "Status"
     *      criteria with "Status" = "On going" and providing the column id for the "Review"
     *      column will return an empty column. Items cannot have two "Status" values at
     *      the same time, so this will result in an empty list.</p>
     *
     * @url GET {id}/items
     * @access hybrid
     *
     * @param int $id        Id of the kanban
     * @param int $column_id Id of the column the item belongs to
     * @param string $query  Search string in json format
     * @param int $limit     Number of elements displayed per page
     * @param int $offset    Position of the first element to display
     *
     * @return ItemCollectionRepresentation
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getItems($id, $column_id, $query = '', $limit = 10, $offset = 0)
    {
        $this->checkAccess();
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $this->getKanbanProject($kanban)
        );

        if (! $this->columnIsInTracker($kanban, $user, $column_id)) {
            throw new RestException(404);
        }

        $column_identifier = new ColumnIdentifier($column_id);

        if ($query !== '') {
            $tracker_report_id    = $this->getTrackerReportIdFromQuery($query);
            $report               = $this->getReport($user, $kanban, $tracker_report_id);
            $items_representation = $this->filtered_item_collection_builder->build(
                $column_identifier,
                $user,
                $report,
                $limit,
                $offset
            );
        } else {
            $items_representation = $this->item_collection_builder->build(
                $column_identifier,
                $user,
                $kanban,
                $limit,
                $offset
            );
        }

        Header::allowOptionsGet();
        Header::sendPaginationHeaders($limit, $offset, $items_representation->total_size, self::MAX_LIMIT);

        return $items_representation;
    }

    private function columnIsInTracker(Kanban $kanban, PFUser $user, int $column_id): bool
    {
        $status_field = $this->getStatusField($kanban, $user);

        return array_key_exists($column_id, $status_field->getAllValues());
    }

    /**
     * Partial re-order of Kanban items
     *
     * Partial re-order of Kanban items
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url PATCH {id}/items
     *
     * @param int                                                $id          Id of the Kanban
     * @param int                                                $column_id   Id of the column the item belongs to {@from query}
     * @param \Tuleap\Tracker\REST\Helpers\OrderRepresentation $order       Order of the items {@from body}
     * @param \Tuleap\Kanban\REST\v1\KanbanAddRepresentation     $add         Ids to add to the column {@from body}
     * @param string                                             $from_column Id of the column the item is coming from (when moving an item) {@from body}
     *
     * @throws RestException 400
     * @throws RestException 403
     */
    protected function patchItems(
        $id,
        $column_id,
        ?OrderRepresentation $order = null,
        ?KanbanAddRepresentation $add = null,
        $from_column = null,
    ): void {
        try {
            $current_user = UserManager::instance()->getCurrentUser();
            $kanban       = $this->kanban_factory->getKanban($current_user, $id);
        } catch (KanbanNotFoundException $exception) {
            throw new RestException(404);
        } catch (KanbanCannotAccessException $exception) {
            throw new RestException(403);
        }

        $project = $this->getKanbanProject($kanban);
        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        if (! $this->columnIsInTracker($kanban, $current_user, $column_id)) {
            throw new RestException(404);
        }

        if ($add) {
            $add->checkFormat();
            $this->validateIdsInAddAreInKanbanTracker($kanban, $add);

            try {
                $this->moveArtifactsInColumn($kanban, $current_user, $add, $column_id);
            } catch (Tracker_NoChangeException $exception) {
            } catch (Tracker_Workflow_GlobalRulesViolationException $exception) {
                throw new RestException(400, $exception->getMessage());
            } catch (FieldValidationException $exception) {
                throw new RestException(400, $exception->getMessage());
            } catch (Tracker_Workflow_Transition_InvalidConditionForTransitionException $exception) {
                throw new RestException(400, $exception->getMessage());
            } catch (Exception $exception) {
                throw new RestException(500, $exception->getMessage());
            }
        }

        if ($order) {
            $order->checkFormat();
            $kanban_column_items = $this->getItemsInColumn($kanban->getTrackerId(), $column_id);
            $order_validator     = new OrderValidator($kanban_column_items);

            try {
                $order_validator->validate($order);
            } catch (IdsFromBodyAreNotUniqueException $exception) {
                throw new RestException(409, $exception->getMessage());
            } catch (OrderIdOutOfBoundException $exception) {
                throw new RestException(409, $exception->getMessage());
            } catch (Exception $exception) {
                throw new RestException(500, $exception->getMessage());
            }

            $orderer = ArtifactsRankOrderer::build();
            $orderer->reorder($order, Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT, $project);
        }

        if ($add || $order) {
            $this->statistics_aggregator->addCardDragAndDropHit(
                $this->getProjectIdForKanban($kanban)
            );
        }
    }

    /**
     * @return array<int, bool>
     */
    private function getItemsInColumn(int $tracker_id, int $column_id): array
    {
        $column_item_ids = [];
        foreach ($this->kanban_item_dao->getItemsInColumn($tracker_id, $column_id) as $artifact) {
            $column_item_ids[$artifact['id']] = true;
        }

        return $column_item_ids;
    }

    /**
     * @url OPTIONS {id}/items
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @param string $id Id of the Kanban
     */
    public function optionsItems($id): void
    {
        Header::allowOptionsGetPatch();
    }

    /**
     * Delete Kanban
     *
     * Delete Kanban
     *
     * @url DELETE {id}
     * @access protected
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @param string $id Id of the kanban
     * @throws RestException 403
     */
    protected function delete($id): void
    {
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, (int) $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $this->getKanbanProject($kanban)
        );

        $this->checkUserCanUpdateKanban($user, $kanban);
        $this->kanban_dao->delete((int) $id);

        $visited_dao = new RecentlyVisitedKanbanDao();
        $visited_dao->deleteVisitByKanbanId((int) $id);

        Header::allowOptionsGetPatchDelete();

        $kanban_sender = new KanbanRealtimeStructureMessageSender(
            $this->tracker_factory,
            $this->structure_realtime_kanban,
            $this->node_js_client,
            $this->permissions_serializer
        );
        $kanban_sender->sendMessageStructure($kanban, 'kanban:delete', $user, \HTTPRequest::instance(), '');
    }

    /**
     * @url OPTIONS {id}/columns
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @param string $id Id of the Kanban
     */
    public function optionsColumns($id): void
    {
        Header::allowOptionsPostPut();
    }

    /**
     * Add a new column
     *
     * Create a new kanban column. Will add another open value to the field corresponding to
     * the 'Status' semantic. An error will be thrown if the semantic 'Status' is not bound to
     * static values.
     *
     * @url POST {id}/columns
     * @status 201
     *
     * @param string                         $id     Id of the Kanban
     * @param KanbanColumnPOSTRepresentation $column The created kanban column {@from body} {@type Tuleap\Kanban\REST\v1\KanbanColumnPOSTRepresentation}
     *
     * @return \Tuleap\Kanban\REST\v1\KanbanColumnRepresentation
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     *
     * @throws RestException 400
     */
    protected function postColumns($id, KanbanColumnPOSTRepresentation $column)
    {
        $current_user = $this->getCurrentUser();
        $kanban_id    = (int) $id;
        $kanban       = $this->getKanban($current_user, $kanban_id);
        $column_label = $column->label;

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $this->getKanbanProject($kanban)
        );

        try {
            $new_column_id = $this->kanban_column_manager->createColumn($current_user, $kanban, $column_label);
            if ($new_column_id === null) {
                throw new RestException(500, 'An error occurred while creating the column');
            }
        } catch (KanbanUserNotAdminException $exception) {
            throw new RestException(401, $exception->getMessage());
        } catch (KanbanSemanticStatusNotDefinedException | SemanticStatusNotDefinedException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (KanbanSemanticStatusNotBoundToStaticValuesException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (KanbanSemanticStatusBasedOnASharedFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $this->form_element_factory->clearCaches();

        $new_column = $this->kanban_column_factory->getColumnForAKanban($kanban, $new_column_id, $current_user);

        try {
            $this->kanban_actions_checker->checkUserCanAddInPlace($current_user, $kanban);
            $add_in_place = true;
        } catch (Exception $exception) {
            $add_in_place = false;
        }

        try {
            $this->kanban_actions_checker->checkUserCanDeleteColumn($current_user, $kanban, $new_column);
            $user_can_remove_column = true;
        } catch (Exception $exception) {
            $user_can_remove_column = false;
        }

        try {
            $this->kanban_actions_checker->checkUserCanEditColumnLabel($current_user, $kanban);
            $user_can_edit_label = true;
        } catch (Exception $exception) {
            $user_can_edit_label = false;
        }

        $column_representation = new KanbanColumnRepresentation($new_column, $add_in_place, $user_can_remove_column, $user_can_edit_label);

        $kanban_sender = new KanbanRealtimeStructureMessageSender(
            $this->tracker_factory,
            $this->structure_realtime_kanban,
            $this->node_js_client,
            $this->permissions_serializer
        );
        $kanban_sender->sendMessageStructure(
            $kanban,
            'kanban_column:create',
            $current_user,
            \HTTPRequest::instance(),
            $column_representation
        );

        return $column_representation;
    }

    /**
     * Reorder Kanban columns
     *
     * @url PUT {id}/columns
     *
     * @param string $id         Id of the Kanban
     * @param array  $column_ids The created kanban column {@from body} {@type int}
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function putColumns($id, array $column_ids): void
    {
        $user      = $this->getCurrentUser();
        $kanban_id = (int) $id;
        $kanban    = $this->getKanban($user, $kanban_id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $this->getKanbanProject($kanban)
        );

        $this->checkColumnIdsExist($user, $kanban, $column_ids);

        try {
            $this->kanban_column_manager->reorderColumns($user, $kanban, $column_ids);
        } catch (KanbanUserNotAdminException $exception) {
            throw new RestException(401, $exception->getMessage());
        } catch (KanbanSemanticStatusNotDefinedException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (KanbanSemanticStatusNotBoundToStaticValuesException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (KanbanSemanticStatusBasedOnASharedFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (KanbanSemanticStatusAllColumnIdsNotProvidedException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (KanbanSemanticStatusColumnIdsNotInOpenSemanticException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $kanban_sender = new KanbanRealtimeStructureMessageSender(
            $this->tracker_factory,
            $this->structure_realtime_kanban,
            $this->node_js_client,
            $this->permissions_serializer
        );
        $kanban_sender->sendMessageStructure(
            $kanban,
            'kanban_column:move',
            $user,
            \HTTPRequest::instance(),
            $column_ids
        );
    }

    /**
     * Get cumulative flow
     *
     * For each column, get the total number of kanban items that were in this column for the requested period.
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * <p><b>query</b> is optional. When filled, it is a json object with:</p>
     * <p>an integer "tracker_report_id" to filter kanban items corresponding to the
     *      given Tracker report id. <br></p>
     *
     *      Example: <pre>{"tracker_report_id":41}</pre>
     *
     * <br>
     * <p>Reports using the field bound to the "Status" semantic may not filter items
     *      the way you expect them to. For example, using a Tracker report with a "Status"
     *      criteria with "Status" = "On going" will return an empty column for other columns
     *      such as Archive or Backlog. Columns are based on "Status" and items can
     *      only have one "Status" value at a time.</p>
     *
     * @url GET {id}/cumulative_flow
     * @access hybrid
     *
     * @param int    $id                     Id of the kanban
     * @param string $start_date             Start date of the cumulative flow in ISO format (YYYY-MM-DD) {@from path}{@type date}
     * @param string $end_date               End date of the cumulative flow in ISO format (YYYY-MM-DD) {@from path}{@type date}
     * @param int    $interval_between_point Number of days between 2 points of the cumulative flow {@from path}{@type int}{@min 1}
     * @param string $query                  Search string in json format {@from path}{@type string}
     *
     * @return DiagramRepresentation
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getCumulativeFlow($id, $start_date, $end_date, $interval_between_point, $query = '')
    {
        $this->checkAccess();
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $this->getKanbanProject($kanban)
        );

        Header::allowOptionsGet();

        $datetime_start = new DateTime($start_date);
        $datetime_end   = new DateTime($end_date);
        if ($datetime_start > $datetime_end) {
            throw new RestException(400, '`start_date` must be older than `end_date`');
        }

        try {
            if ($query !== '') {
                $tracker_report_id = $this->getTrackerReportIdFromQuery($query);
                $report            = $this->getReport($user, $kanban, $tracker_report_id);

                $diagram_representation = $this->filtered_diagram_builder->build(
                    $kanban,
                    $user,
                    $datetime_start,
                    $datetime_end,
                    $interval_between_point,
                    $report
                );
            } else {
                $diagram_representation = $this->diagram_builder->build(
                    $kanban,
                    $user,
                    $datetime_start,
                    $datetime_end,
                    $interval_between_point
                );
            }
        } catch (TooManyPointsException $exception) {
            throw new RestException(
                400,
                'Number of points requested is too large, you can request for ' . OrderedColumnRepresentationsBuilder::MAX_POSSIBLE_POINTS . 'maximum'
            );
        }

        return $diagram_representation;
    }

    /**
     * @url OPTIONS {id}/cumulative_flow
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @param int $id Id of the Kanban
     */
    public function optionsCumulativeFlow($id): void
    {
        Header::allowOptionsGet();
    }

    private function checkColumnIdsExist(PFUser $user, Kanban $kanban, array $column_ids): void
    {
        foreach ($column_ids as $column_id) {
            if (! $this->columnIsInTracker($kanban, $user, $column_id)) {
                throw new RestException(404, "Column $column_id is not known");
            }
        }
    }

    private function getKanban(PFUser $user, int $id): Kanban
    {
        try {
            $kanban = $this->kanban_factory->getKanban($user, $id);
        } catch (KanbanNotFoundException $exception) {
            throw new RestException(404);
        } catch (KanbanCannotAccessException $exception) {
            throw new RestException(403);
        }

        return $kanban;
    }

    private function getCurrentUser(): PFUser
    {
        return UserManager::instance()->getCurrentUser();
    }

    private function getTrackerForKanban(Kanban $kanban): Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
        if (! $tracker) {
            throw new RestException(500, 'The tracker used by the kanban does not exist anymore');
        }

        return $tracker;
    }

    private function getProjectIdForKanban(Kanban $kanban): int
    {
        return (int) $this->getKanbanProject($kanban)->getGroupId();
    }

    private function getReport(PFUser $user, Kanban $kanban, int $tracker_report_id): Tracker_Report
    {
        $report = $this->report_factory->getReportById($tracker_report_id, $user->getId(), false);

        if ($report === null) {
            throw new RestException(404, 'The report was not found');
        }
        if ($report->getTracker()->getId() !== $kanban->getTrackerId()) {
            throw new RestException(400, 'The provided report does not belong to the kanban tracker');
        }
        if (! $report->isPublic()) {
            throw new RestException(400, 'Personnal tracker reports cannot be used');
        }

        return $report;
    }

    /**
     * @url OPTIONS {id}/tracker_reports
     */
    public function optionsTrackerReports(int $id): void
    {
        Header::allowOptionsPut();
    }

    /**
     * Add list of report available for filters
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * <p>The route adds the ability to select tracker reports that could be used to filter a Kanban.</p>
     * <br>
     *
     * <p>To add one or more reports to a Kanban, please use the following format:
     * <pre>{"tracker_report_ids": [1,2,3]}</pre>
     * </p>
     *
     * <p>To remove all the selected reports, please use an empty array:
     * <pre>{"tracker_report_ids": []}</pre>
     * </p>
     *
     * @url PUT {id}/tracker_reports
     *
     * @param int   $id                  Id of the kanban
     * @param array $tracker_report_ids  List of selected report ids {@from body} {@type int}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 500
     */
    protected function putTrackerReports($id, array $tracker_report_ids): void
    {
        $this->checkAccess();

        $this->optionsTrackerReports($id);

        $current_user = $this->getCurrentUser();
        $kanban_id    = $id;
        $kanban       = $this->getKanban($current_user, $kanban_id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $this->getKanbanProject($kanban)
        );

        foreach ($tracker_report_ids as $report_id) {
            $this->getReport($current_user, $kanban, $report_id);
        }

        try {
            $this->kanban_actions_checker->checkUserCanAdministrate($current_user, $kanban);
            $this->tracker_report_updater->save($kanban, $tracker_report_ids);
        } catch (KanbanUserNotAdminException $exception) {
            throw new RestException(403, "You can't administrate this Kanban");
        } catch (Exception $exception) {
            throw new RestException(500, 'An error occured while saving reports for Kanban');
        }
    }

    private function getKanbanProject(Kanban $kanban): \Project
    {
        $kanban_tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
        if ($kanban_tracker === null) {
            throw new \RuntimeException('Tracker does not exist');
        }

        return $kanban_tracker->getProject();
    }

    private function getStatusValueRetriever(): StatusValueRetriever
    {
        return new StatusValueRetriever(
            TrackerSemanticStatusFactory::instance(),
            new FirstPossibleValueInListRetriever(
                new FirstValidValueAccordingToDependenciesRetriever(
                    Tracker_FormElementFactory::instance()
                ),
                new ValidValuesAccordingToTransitionsRetriever(
                    Workflow_Transition_ConditionFactory::build()
                )
            )
        );
    }

    /**
     * @throws RestException
     * @throws \Tracker_Exception
     */
    private function moveArtifact(
        \Tuleap\Tracker\Artifact\Artifact $artifact,
        PFUser $user,
        ListField $status_field,
        int $closed_value,
    ): void {
        if (! $artifact->userCanView($user)) {
            throw new RestException(403, 'You cannot access this kanban item.');
        }

        $fields_data = [
            $status_field->getId() => $closed_value,
        ];

        try {
            $submission_timestamp = \Tuleap\Request\RequestTime::getTimestamp();

            $new_changeset = NewChangeset::fromFieldsDataArray(
                $artifact,
                $fields_data,
                '',
                CommentFormatIdentifier::COMMONMARK,
                [],
                $user,
                $submission_timestamp,
                new CreatedFileURLMapping(),
            );

            $this->getNewChangesetCreator()->create(
                $new_changeset,
                PostCreationContext::withNoConfig(true)
            );
        } catch (Tracker_NoChangeException $exception) {
        }
    }

    private function getNewChangesetCreator(): NewChangesetCreator
    {
        $tracker_artifact_factory = Tracker_ArtifactFactory::instance();
        $form_element_factory     = Tracker_FormElementFactory::instance();
        $event_dispatcher         = EventManager::instance();
        $fields_retriever         = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);
        $usage_dao                = new \Tuleap\Tracker\Admin\ArtifactLinksUsageDao();

        $user_manager = UserManager::instance();
        return new NewChangesetCreator(
            new \Tuleap\DB\DBTransactionExecutorWithConnection(\Tuleap\DB\DBFactory::getMainTuleapDBConnection()),
            ArtifactChangesetSaver::build(),
            new AfterNewChangesetHandler($tracker_artifact_factory, $fields_retriever),
            \WorkflowFactory::instance(),
            new CommentCreator(
                new Tracker_Artifact_Changeset_CommentDao(),
                ReferenceManager::instance(),
                new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao()),
                new TextValueValidator(),
            ),
            new NewChangesetFieldValueSaver(
                $fields_retriever,
                new ChangesetValueSaver(),
            ),
            new NewChangesetValidator(
                new Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                    $form_element_factory,
                    new \Tuleap\Tracker\FormElement\ArtifactLinkValidator(
                        $tracker_artifact_factory,
                        new \Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory(
                            new \Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao(),
                            $usage_dao
                        ),
                        $usage_dao,
                        $event_dispatcher,
                    ),
                    new WorkflowUpdateChecker(new FrozenFieldDetector(
                        new TransitionRetriever(
                            new StateFactory(
                                TransitionFactory::instance(),
                                new SimpleWorkflowDao()
                            ),
                            new TransitionExtractor()
                        ),
                        FrozenFieldsRetriever::instance(),
                    )),
                ),
                new Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
                new \Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction(
                    $tracker_artifact_factory
                ),
            ),
            new NewChangesetPostProcessor(
                $event_dispatcher,
                ActionsQueuer::build(\BackendLogger::getDefaultLogger()),
                new ChangesetCommentIndexer(
                    new ItemToIndexQueueEventBased($event_dispatcher),
                    $event_dispatcher,
                    new \Tracker_Artifact_Changeset_CommentDao(),
                ),
            ),
        );
    }
}
