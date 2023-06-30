<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace Tuleap\Kanban\REST\v1;

use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanCannotAccessException;
use Tuleap\Kanban\KanbanDao;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Kanban\KanbanItemDao;
use Tuleap\Kanban\KanbanItemManager;
use Tuleap\Kanban\KanbanNotFoundException;
use AgileDashboardStatisticsAggregator;
use EventManager;
use Luracast\Restler\RestException;
use PFUser;
use Tracker;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_List;
use Tracker_FormElementFactory;
use Tracker_Semantic_Status;
use TrackerFactory;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsRunner;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactForwardLinksRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactLinksByChangesetCache;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\Link\ArtifactUpdateHandler;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\Permission\SubmissionPermissionVerifier;
use Tuleap\Tracker\REST\Artifact\ArtifactCreator;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataFromValuesByFieldBuilder;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;
use UserManager;

final class KanbanItemsResource extends AuthenticatedResource
{
    /** @var KanbanFactory */
    private $kanban_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var TimeInfoFactory */
    private $time_info_factory;

    /** @var AgileDashboardStatisticsAggregator */
    private $statistics_aggregator;

    /**
     * @var ItemRepresentationBuilder
     */
    private $item_representation_builder;

    public function __construct()
    {
        $this->tracker_factory      = TrackerFactory::instance();
        $this->artifact_factory     = Tracker_ArtifactFactory::instance();
        $this->form_element_factory = Tracker_FormElementFactory::instance();

        $this->kanban_factory = new KanbanFactory(
            $this->tracker_factory,
            new KanbanDao()
        );

        $kanban_item_dao                   = new KanbanItemDao();
        $this->time_info_factory           = new TimeInfoFactory($kanban_item_dao);
        $this->statistics_aggregator       = new AgileDashboardStatisticsAggregator();
        $color_builder                     = new BackgroundColorBuilder(new BindDecoratorRetriever());
        $this->item_representation_builder = new ItemRepresentationBuilder(
            new KanbanItemManager($kanban_item_dao),
            $this->time_info_factory,
            UserManager::instance(),
            \EventManager::instance(),
            $color_builder
        );
    }

    /**
     * @url OPTIONS
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     */
    public function options(): void
    {
        Header::allowOptionsGetPost();
    }

    /**
     * Add new Kanban Item
     *
     * Create a kanban item in the given column or backlog
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     * <pre>
     * /!\ Only works for trackers that allow artifact creation with only a "title" /!\
     * </pre>
     *
     * @access protected
     *
     * @url POST
     *
     * @param KanbanItemPOSTRepresentation $item The created kanban item {@from body} {@type Tuleap\Kanban\REST\v1\KanbanItemPOSTRepresentation}
     *
     * @status 201
     * @throws RestException 403
     */
    protected function post(KanbanItemPOSTRepresentation $item): KanbanItemRepresentation
    {
        $current_user = $this->getCurrentUser();
        $kanban       = $this->getKanban($current_user, $item->kanban_id);
        $tracker      = $this->tracker_factory->getTrackerById($kanban->getTrackerId());

        if ($tracker === null) {
            throw new \RuntimeException('Tracker does not exist');
        }

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $tracker->getProject()
        );

        $usage_dao            = new ArtifactLinksUsageDao();
        $fields_retriever     = new FieldsToBeSavedInSpecificOrderRetriever($this->form_element_factory);
        $event_dispatcher     = EventManager::instance();
        $transaction_executor = new DBTransactionExecutorWithConnection(
            DBFactory::getMainTuleapDBConnection()
        );

        $changeset_creator = new NewChangesetCreator(
            new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                $this->form_element_factory,
                new ArtifactLinkValidator(
                    $this->artifact_factory,
                    new TypePresenterFactory(new TypeDao(), $usage_dao),
                    $usage_dao,
                    $event_dispatcher,
                ),
                new WorkflowUpdateChecker(
                    new FrozenFieldDetector(
                        new TransitionRetriever(
                            new StateFactory(\TransitionFactory::instance(), new SimpleWorkflowDao()),
                            new TransitionExtractor()
                        ),
                        FrozenFieldsRetriever::instance(),
                    )
                )
            ),
            $fields_retriever,
            $event_dispatcher,
            new \Tracker_Artifact_Changeset_ChangesetDataInitializator($this->form_element_factory),
            $transaction_executor,
            ArtifactChangesetSaver::build(),
            new ParentLinkAction($this->artifact_factory),
            new AfterNewChangesetHandler($this->artifact_factory, $fields_retriever),
            ActionsRunner::build(\BackendLogger::getDefaultLogger()),
            new ChangesetValueSaver(),
            \WorkflowFactory::instance(),
            new CommentCreator(
                new \Tracker_Artifact_Changeset_CommentDao(),
                \ReferenceManager::instance(),
                new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao()),
                new ChangesetCommentIndexer(
                    new ItemToIndexQueueEventBased($event_dispatcher),
                    $event_dispatcher,
                    new \Tracker_Artifact_Changeset_CommentDao(),
                ),
                new TextValueValidator(),
            )
        );

        $artifact_link_initial_builder = new NewArtifactLinkInitialChangesetValueBuilder();
        $updater                       = new ArtifactCreator(
            new FieldsDataBuilder(
                $this->form_element_factory,
                new NewArtifactLinkChangesetValueBuilder(
                    new ArtifactForwardLinksRetriever(
                        new ArtifactLinksByChangesetCache(),
                        new ChangesetValueArtifactLinkDao(),
                        $this->artifact_factory
                    )
                ),
                $artifact_link_initial_builder
            ),
            $this->artifact_factory,
            $this->tracker_factory,
            new FieldsDataFromValuesByFieldBuilder($this->form_element_factory, $artifact_link_initial_builder),
            $this->form_element_factory,
            new ArtifactUpdateHandler($changeset_creator, $this->form_element_factory, $this->artifact_factory),
            SubmissionPermissionVerifier::instance()
        );

        $tracker_reference = TrackerReference::build($tracker);

        $artifact_fields = $this->buildFieldsData($tracker, $item);

        $art_ref = $updater->create($current_user, $tracker_reference, $artifact_fields, true);

        $artifact = $art_ref->getArtifact();
        if (! $artifact) {
            throw new RestException(500, implode('. ', $GLOBALS['Response']->getFeedbackErrors()));
        }

        $this->statistics_aggregator->addKanbanAddInPlaceHit(
            $tracker->getGroupId()
        );

        $item_representation = $this->item_representation_builder->buildItemRepresentation($artifact);

        return $item_representation;
    }

    /**
     * Get a Kanban item
     *
     *
     * Get the Kanban representation of the given artifact.
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     * <br/>
     * Example:
     * <pre><code>
     * {<br/>
     *     "id": 195,<br/>
     *     "item_name": "task",<br/>
     *     "label": "My Kanban task",<br/>
     *     "color": "inca_silver",<br/>
     *     "card_fields": [<br/>
     *         {<br/>
     *             "field_id": 7132,<br/>
     *             "type": "string",<br/>
     *             "label": "Title",<br/>
     *             "value": "My Kanban Task"<br/>
     *         }<br/>
     *     ],<br/>
     *     "in_column": "backlog"<br/>
     * }<br/>
     * </code></pre>
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param  int $id Id of the artifact
     *
     *
     * @throws RestException 403
     */
    protected function get($id): KanbanItemRepresentation
    {
        $this->checkAccess();

        $current_user = $this->getCurrentUser();
        $artifact     = $this->artifact_factory->getArtifactById($id);

        if (! $artifact) {
            throw new RestException(404, 'Kanban item not found.');
        }

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $current_user,
            $artifact->getTracker()->getProject()
        );

        if (! $artifact->userCanView($current_user)) {
            throw new RestException(403, 'You cannot access this kanban item.');
        }

        $item_representation = $this->item_representation_builder->buildItemRepresentation($artifact);

        return $item_representation;
    }

    /**
     * @return ArtifactValuesRepresentation[]
     */
    private function buildFieldsData(Tracker $tracker, KanbanItemPOSTRepresentation $item): array
    {
        $fields_data = [];

        $this->addSummaryToFieldsData($tracker, $item, $fields_data);
        $this->addStatusToFieldsData($tracker, $item, $fields_data);

        return $fields_data;
    }

    /**
     * @param ArtifactValuesRepresentation[] $fields_data
     */
    private function addSummaryToFieldsData(
        Tracker $tracker,
        KanbanItemPOSTRepresentation $item,
        array &$fields_data,
    ): void {
        $summary_field = $tracker->getTitleField();

        if (! $summary_field) {
            throw new RestException(403);
        }

        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = $summary_field->getId();
        $representation->value    = $item->label;

        $fields_data[] = $representation;
    }

    /**
     * @param ArtifactValuesRepresentation[] $fields_data
     */
    private function addStatusToFieldsData(
        Tracker $tracker,
        KanbanItemPOSTRepresentation $item,
        array &$fields_data,
    ): void {
        $status_field = $tracker->getStatusField();

        if (! $status_field) {
            throw new RestException(403);
        }

        $value = Tracker_FormElement_Field_List::NONE_VALUE;
        if (! empty($item->column_id)) {
            $semantic = Tracker_Semantic_Status::load($tracker);

            if (! $semantic->getFieldId()) {
                throw new RestException(403);
            }

            if (! in_array($item->column_id, $semantic->getOpenValues())) {
                throw new RestException(400, 'Unknown column');
            }

            $value = $item->column_id;
        }

        $representation                 = new ArtifactValuesRepresentation();
        $representation->field_id       = $status_field->getId();
        $representation->bind_value_ids = [$value];

        $fields_data[] = $representation;
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
}
