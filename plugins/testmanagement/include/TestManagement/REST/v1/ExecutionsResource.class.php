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

namespace Tuleap\TestManagement\REST\v1;

use BackendLogger;
use Codendi_HTMLPurifier;
use EventManager;
use Luracast\Restler\RestException;
use PFUser;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_ArtifactFactory;
use Tracker_Exception;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_FormElementFactory;
use Tracker_NoChangeException;
use Tracker_Permission_PermissionRetrieveAssignee;
use Tracker_Permission_PermissionsSerializer;
use Tracker_URLVerification;
use TrackerFactory;
use TransitionFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\RealTimeMercure\ClientBuilder;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\ArtifactFactory;
use Tuleap\TestManagement\Campaign\Execution\DefinitionForExecutionRetriever;
use Tuleap\TestManagement\Campaign\Execution\DefinitionNotFoundException;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\ConfigConformanceValidator;
use Tuleap\TestManagement\Dao;
use Tuleap\TestManagement\RealTime\RealTimeMessageSender;
use Tuleap\TestManagement\REST\ExecutionChangesExtractor;
use Tuleap\TestManagement\REST\FormattedChangesetValueForFileFieldRetriever;
use Tuleap\TestManagement\REST\FormattedChangesetValueForIntFieldRetriever;
use Tuleap\TestManagement\REST\FormattedChangesetValueForListFieldRetriever;
use Tuleap\TestManagement\REST\FormattedChangesetValueForTextFieldRetriever;
use Tuleap\TestManagement\REST\v1\DefinitionRepresentations\DefinitionRepresentationBuilder;
use Tuleap\TestManagement\REST\v1\Execution\StepsResultsFilter;
use Tuleap\TestManagement\REST\v1\Execution\StepsResultsRepresentationBuilder;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\CachingTrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\InitialChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsQueuer;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactForwardLinksRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactLinksByChangesetCache;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksToNewChangesetsConverter;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValueSaver;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\Permission\SubmissionPermissionVerifier;
use Tuleap\Tracker\RealTime\RealTimeArtifactMessageSender;
use Tuleap\Tracker\RealtimeMercure\RealTimeMercureArtifactMessageSender;
use Tuleap\Tracker\REST\Artifact\ArtifactCreator;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\ArtifactRestUpdateConditionsChecker;
use Tuleap\Tracker\REST\Artifact\ArtifactUpdater;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataFromValuesByFieldBuilder;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;
use UserManager;
use WrapperLogger;

class ExecutionsResource
{
    /** @var Config */
    private $config;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var ArtifactFactory */
    private $testmanagement_artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var ExecutionRepresentationBuilder */
    private $execution_representation_builder;

    /** @var RealTimeMessageSender */
    private $realtime_message_sender;

    /** @var ExecutionDao */
    private $execution_dao;

    /** @var DefinitionForExecutionRetriever */
    private $definition_retriever;

    private ArtifactUpdater $artifact_updater;

    /** @var UserManager */
    private $user_manager;

    /** @var ExecutionStatusUpdater */
    private $execution_status_updater;

    /** @var StepsResultsChangesBuilder */
    private $steps_results_changes_builder;

    public function __construct()
    {
        $this->config          = new Config(new Dao(), TrackerFactory::instance());
        $conformance_validator = new ConfigConformanceValidator($this->config);

        $this->user_manager        = UserManager::instance();
        $this->tracker_factory     = TrackerFactory::instance();
        $this->formelement_factory = Tracker_FormElementFactory::instance();
        $this->artifact_factory    = Tracker_ArtifactFactory::instance();
        $artifact_dao              = new ArtifactDao();

        $this->testmanagement_artifact_factory = new ArtifactFactory(
            $this->config,
            $this->artifact_factory,
            $artifact_dao
        );

        $assigned_to_representation_builder = new AssignedToRepresentationBuilder(
            $this->formelement_factory,
            $this->user_manager
        );

        $requirement_retriever = new RequirementRetriever($this->artifact_factory, $artifact_dao, $this->config);

        $this->definition_retriever             = new DefinitionForExecutionRetriever($conformance_validator);
        $this->execution_dao                    = new ExecutionDao();
        $steps_results_representation_builder   = new StepsResultsRepresentationBuilder(
            $this->formelement_factory,
            new StepsResultsFilter()
        );
        $purifier                               = Codendi_HTMLPurifier::instance();
        $commonmark_interpreter                 = CommonMarkInterpreter::build($purifier);
        $this->execution_representation_builder = new ExecutionRepresentationBuilder(
            $this->user_manager,
            $this->formelement_factory,
            $conformance_validator,
            $assigned_to_representation_builder,
            new ArtifactDao(),
            $this->artifact_factory,
            $this->definition_retriever,
            $this->execution_dao,
            $steps_results_representation_builder,
            $this->getFileUploadDataProvider(),
            new DefinitionRepresentationBuilder(
                $this->formelement_factory,
                $conformance_validator,
                $requirement_retriever,
                $purifier,
                $commonmark_interpreter,
                new ArtifactRepresentationBuilder(
                    $this->formelement_factory,
                    Tracker_ArtifactFactory::instance(),
                    new TypeDao(),
                    new ChangesetRepresentationBuilder(
                        UserManager::instance(),
                        $this->formelement_factory,
                        new CommentRepresentationBuilder(
                            CommonMarkInterpreter::build(\Codendi_HTMLPurifier::instance())
                        ),
                        new PermissionChecker(new CachingTrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentUGroupEnabledDao())))
                    )
                ),
                \Tracker_Artifact_PriorityManager::build(),
            ),
        );

        $node_js_client                  = new NodeJSClient(
            HttpClientFactory::createClientForInternalTuleapUse(),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            BackendLogger::getDefaultLogger(),
        );
        $permissions_serializer          = new Tracker_Permission_PermissionsSerializer(
            new Tracker_Permission_PermissionRetrieveAssignee(UserManager::instance())
        );
        $artifact_message_sender         = new RealTimeArtifactMessageSender(
            $node_js_client,
            $permissions_serializer
        );
        $mercure_client                  = ClientBuilder::build(ClientBuilder::DEFAULTPATH);
        $mercure_artifact_message_sender = new RealTimeMercureArtifactMessageSender(
            $mercure_client
        );
        $mercure_client                  = ClientBuilder::build(ClientBuilder::DEFAULTPATH);
        $this->realtime_message_sender   = new RealTimeMessageSender(
            $node_js_client,
            $permissions_serializer,
            $artifact_message_sender,
            $mercure_artifact_message_sender
        );

        $usage_dao        = new ArtifactLinksUsageDao();
        $fields_retriever = new FieldsToBeSavedInSpecificOrderRetriever($this->formelement_factory);
        $event_dispatcher = \EventManager::instance();

        $changeset_creator = new NewChangesetCreator(
            new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                $this->formelement_factory,
                new ArtifactLinkValidator(
                    $this->artifact_factory,
                    new TypePresenterFactory(new TypeDao(), $usage_dao),
                    $usage_dao,
                    $event_dispatcher,
                ),
                new WorkflowUpdateChecker($this->getFrozenFieldDetector())
            ),
            $fields_retriever,
            \EventManager::instance(),
            new \Tracker_Artifact_Changeset_ChangesetDataInitializator($this->formelement_factory),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            ArtifactChangesetSaver::build(),
            new ParentLinkAction($this->artifact_factory),
            new AfterNewChangesetHandler($this->artifact_factory, $fields_retriever),
            ActionsQueuer::build(\BackendLogger::getDefaultLogger()),
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

        $this->artifact_updater = new ArtifactUpdater(
            new FieldsDataBuilder(
                $this->formelement_factory,
                new NewArtifactLinkChangesetValueBuilder(
                    new ArtifactForwardLinksRetriever(
                        new ArtifactLinksByChangesetCache(),
                        new ChangesetValueArtifactLinkDao(),
                        $this->artifact_factory
                    )
                ),
                new NewArtifactLinkInitialChangesetValueBuilder()
            ),
            $changeset_creator,
            new ArtifactRestUpdateConditionsChecker(),
        );

        $this->steps_results_changes_builder = new StepsResultsChangesBuilder(
            $this->formelement_factory,
            $this->execution_dao,
            new TestStatusAccordingToStepsStatusChangesBuilder()
        );

        $this->execution_status_updater = new ExecutionStatusUpdater(
            $this->artifact_updater,
            $this->testmanagement_artifact_factory,
            $this->realtime_message_sender,
            $this->user_manager
        );
    }

    /**
     * @url OPTIONS
     *
     */
    public function options(): void
    {
        Header::allowOptions();
    }

    /**
     * @url OPTIONS {id}/presences
     *
     */
    public function optionsPresences(string $id): void
    {
        Header::allowOptionsPatch();
    }

    /**
     * @url OPTIONS {id}/issues
     *
     */
    public function optionsIssues(string $id): void
    {
        Header::allowOptionsPatch();
    }

    /**
     * @url OPTIONS {id}
     *
     */
    public function optionsId(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get execution
     *
     * Get testing execution by its id
     *
     * @url GET {id}
     *
     * @param int $id Id of the execution
     * @return ExecutionRepresentation
     * @throws RestException 400
     * @throws RestException 404
     */
    protected function getId(int $id)
    {
        $this->optionsId($id);

        $user     = $this->getCurrentUser();
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $id);
        if (! $artifact) {
            throw new RestException(404);
        }

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $artifact->getTracker()->getProject()
        );

        return $this->getExecutionRepresentation($user, $artifact);
    }

    /**
     * Create a test execution
     *
     * @url POST
     *
     * @param TrackerReference $tracker_reference Execution tracker of the execution {@from body}
     * @param int $definition_id Definition of the execution {@from body}
     * @param string $status Status of the execution {@from body} {@choice notrun,passed,failed,blocked}
     * @param string $results Result of the execution {@from body}
     * @return ExecutionRepresentation
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 500
     */
    protected function post(
        TrackerReference $tracker_reference,
        $definition_id,
        $status,
        int $time = 0,
        $results = '',
    ) {
        $tracker = $this->tracker_factory->getTrackerById($tracker_reference->id);
        if ($tracker === null) {
            throw new RestException(404);
        }
        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $tracker->getProject()
        );

        $user                 = $this->getCurrentUser();
        $usage_dao            = new ArtifactLinksUsageDao();
        $fields_retriever     = new FieldsToBeSavedInSpecificOrderRetriever($this->formelement_factory);
        $event_dispatcher     = EventManager::instance();
        $transaction_executor = new DBTransactionExecutorWithConnection(
            DBFactory::getMainTuleapDBConnection()
        );

        $artifact_link_initial_builder = new NewArtifactLinkInitialChangesetValueBuilder();
        $changeset_creator             = new NewChangesetCreator(
            new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                $this->formelement_factory,
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
            new \Tracker_Artifact_Changeset_ChangesetDataInitializator($this->formelement_factory),
            $transaction_executor,
            ArtifactChangesetSaver::build(),
            new ParentLinkAction($this->artifact_factory),
            new AfterNewChangesetHandler($this->artifact_factory, $fields_retriever),
            ActionsQueuer::build(\BackendLogger::getDefaultLogger()),
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

        $creator = new ArtifactCreator(
            new FieldsDataBuilder(
                $this->formelement_factory,
                new NewArtifactLinkChangesetValueBuilder(
                    new ArtifactForwardLinksRetriever(
                        new ArtifactLinksByChangesetCache(),
                        new ChangesetValueArtifactLinkDao(),
                        $this->artifact_factory
                    )
                ),
                new NewArtifactLinkInitialChangesetValueBuilder()
            ),
            TrackerArtifactCreator::build(
                new InitialChangesetCreator(
                    Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
                    $fields_retriever,
                    new \Tracker_Artifact_Changeset_ChangesetDataInitializator($this->formelement_factory),
                    new WrapperLogger(BackendLogger::getDefaultLogger(), self::class),
                    ArtifactChangesetSaver::build(),
                    new AfterNewChangesetHandler($this->artifact_factory, $fields_retriever),
                    \WorkflowFactory::instance(),
                    new InitialChangesetValueSaver(),
                ),
                Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
                new WrapperLogger(BackendLogger::getDefaultLogger(), self::class),
            ),
            $this->tracker_factory,
            new FieldsDataFromValuesByFieldBuilder($this->formelement_factory, $artifact_link_initial_builder),
            $this->formelement_factory,
            SubmissionPermissionVerifier::instance(),
            $transaction_executor,
            new ReverseLinksToNewChangesetsConverter($this->formelement_factory, $this->artifact_factory),
            $changeset_creator
        );

        try {
            $values = $this->getValuesByFieldsName(
                $user,
                $tracker_reference->id,
                $definition_id,
                $status,
                $time,
                $results
            );

            if (! empty($values)) {
                $artifact_reference = $creator->create($user, $tracker_reference, $values, true);
            } else {
                throw new RestException(400, "No valid data are provided");
            }
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_Exception $exception) {
            throw new RestException(500, $exception->getMessage());
        }

        $this->sendAllowHeadersForExecutionPost($artifact_reference->getArtifact());

        return $this->getExecutionRepresentation($user, $artifact_reference->getArtifact());
    }

    /**
     * Update part of a test execution
     *
     * @url PATCH {id}
     *
     * @param string $id Id of the execution artifact
     * @param PATCHExecutionRepresentation $body Actions to performs on the execution {@from body}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 500
     *
     */
    protected function patchId($id, PATCHExecutionRepresentation $body): void
    {
        $user               = $this->getCurrentUser();
        $execution_artifact = $this->getArtifactById($user, (int) $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $execution_artifact->getTracker()->getProject()
        );

        if (! $execution_artifact->userCanUpdate($user)) {
            throw new RestException(403);
        }

        $campaign = $this->testmanagement_artifact_factory->getCampaignForExecution($execution_artifact);
        if ($campaign && ! $campaign->isOpen()) {
            throw new I18NRestException(400, dgettext('plugin-testmanagement', 'The campaign is closed.'));
        }

        $definition_artifact = $this->getDefinitionOfExecution($user, $execution_artifact);

        $last_changeset = $definition_artifact->getLastChangeset();
        if ($body->force_use_latest_definition_version && $last_changeset) {
            $this->execution_dao->updateExecutionToUseLatestVersionOfDefinition(
                $id,
                (int) $last_changeset->getId()
            );
        }

        if ($body->steps_results) {
            $this->execution_status_updater->update(
                $execution_artifact,
                $this->steps_results_changes_builder->getStepsChanges(
                    $body->steps_results,
                    $execution_artifact,
                    $definition_artifact,
                    $user
                ),
                $user
            );
        }
    }

    /**
     * Update a test execution
     *
     * @url PUT {id}
     *
     * @param string $id Id of the artifact
     * @param string $status Status of the execution {@from body} {@choice notrun,passed,failed,blocked}
     * @param int[] $uploaded_file_ids files_ids to add during the execution {@from body}
     * @param int[] $deleted_file_ids files_ids to delete during the execution {@from body}
     * @param int $time Time to pass the execution {@from body}
     * @param string $results Result of the execution {@from body}
     * @return ExecutionRepresentation
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 500
     */
    protected function putId($id, $status, array $uploaded_file_ids = [], array $deleted_file_ids = [], $time = 0, $results = '')
    {
        $user     = $this->getCurrentUser();
        $artifact = $this->getArtifactById($user, (int) $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $artifact->getTracker()->getProject()
        );

        $this->execution_status_updater->update(
            $artifact,
            $this->getExecutionChangesExtractor()->getChanges($status, $uploaded_file_ids, $deleted_file_ids, $time, $results, $artifact, $user),
            $user
        );

        $this->sendAllowHeadersForExecutionPut($artifact);

        return $this->getExecutionRepresentation($user, $artifact);
    }

    /**
     * User views a test execution
     *
     * @url PATCH {id}/presences
     *
     * @param string $id Id of the artifact
     * @param string $uuid Uuid of current user {@from body}
     * @param string $remove_from Id of the old artifact {@from body}
     *
     * @throws RestException 404
     *
     */
    protected function presences($id, $uuid, $remove_from = ''): void
    {
        $user     = $this->getCurrentUser();
        $artifact = $this->getArtifactById($user, (int) $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $artifact->getTracker()->getProject()
        );

        $campaign = $this->testmanagement_artifact_factory->getCampaignForExecution($artifact);
        if ($campaign) {
            $this->realtime_message_sender->sendPresences($campaign, $artifact, $user, $uuid, $remove_from, $_SERVER[RealTimeMessageSender::HTTP_CLIENT_UUID]);
        }

        $this->optionsPresences($id);
    }

    /**
     * Create an artifact link between an issue and a test execution
     *
     * @url PATCH {id}/issues
     *
     * @param string $id Id of the test execution artifact
     * @param string $issue_id Id of the issue artifact {@from body}
     * @param NewChangesetCommentRepresentation $comment Comment describing the test execution {body, format} {@from body}
     *
     * @throws RestException 400
     * @throws RestException 404
     * @throws RestException 500
     *
     */
    protected function patchIssueLink($id, $issue_id, ?NewChangesetCommentRepresentation $comment = null): void
    {
        $user               = $this->getCurrentUser();
        $execution_artifact = $this->getArtifactById($user, (int) $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $execution_artifact->getTracker()->getProject()
        );

        $issue_artifact = $this->getArtifactById($user, (int) $issue_id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $issue_artifact->getTracker()->getProject()
        );

        if (! $execution_artifact || ! $issue_artifact) {
            throw new RestException(404);
        }

        $tracker = $issue_artifact->getTracker();
        if ($tracker->getId() !== $this->config->getIssueTrackerId($tracker->getProject())) {
            throw new RestException(400, 'The given artifact does not belong to issue tracker');
        }

        $is_linked = $execution_artifact->linkArtifact($issue_artifact->getId(), $user);
        if (! $is_linked) {
            throw new RestException(400, 'Could not link the issue artifact to the test execution');
        }

        $campaign = $this->testmanagement_artifact_factory->getCampaignForExecution($execution_artifact);
        if ($campaign) {
            $this->realtime_message_sender->sendArtifactLinkAdded(
                $user,
                $campaign,
                $execution_artifact,
                $issue_artifact,
                $_SERVER[RealTimeMessageSender::HTTP_CLIENT_UUID] ?? null,
                MinimalTrackerRepresentation::build($issue_artifact->getTracker())
            );
        }

        try {
            $this->artifact_updater->update($user, $issue_artifact, [], $comment);
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        }

        $this->optionsIssues($id);
    }

    private function getFieldByName(string $field_name, int $tracker_id, PFUser $user): ?\Tracker_FormElement_Field
    {
        return $this->formelement_factory->getUsedFieldByNameForUser(
            $tracker_id,
            $field_name,
            $user
        );
    }

    private function getArtifactById(PFUser $user, int $id): Artifact
    {
        $artifact = $this->testmanagement_artifact_factory->getArtifactByIdUserCanView($user, $id);
        if ($artifact) {
            ProjectAuthorization::userCanAccessProject(
                $user,
                $artifact->getTracker()->getProject(),
                new Tracker_URLVerification()
            );
            return $artifact;
        }
        throw new RestException(404);
    }

    /**
     * @return array
     */
    private function getValuesByFieldsName(
        PFUser $user,
        int $tracker_id,
        int $definition_id,
        string $status,
        int $time,
        string $results,
    ) {
        $status_field         = $this->getFieldByName(ExecutionRepresentation::FIELD_STATUS, $tracker_id, $user);
        $time_field           = $this->getFieldByName(ExecutionRepresentation::FIELD_TIME, $tracker_id, $user);
        $results_field        = $this->getFieldByName(ExecutionRepresentation::FIELD_RESULTS, $tracker_id, $user);
        $artifact_links_field = $this->getFieldByName(
            ExecutionRepresentation::FIELD_ARTIFACT_LINKS,
            $tracker_id,
            $user
        );

        $values = [];

        if ($status_field) {
            $status_field_binds = [];
            assert($status_field instanceof \Tracker_FormElement_Field_List);
            $bind = $status_field->getBind();
            if ($bind) {
                assert($bind instanceof Tracker_FormElement_Field_List_Bind);
                $status_field_binds = $bind->getValuesByKeyword($status);
            }
            $status_field_bind = array_pop($status_field_binds);

            if ($status_field_bind !== null) {
                $values[] = $this->createArtifactValuesRepresentation(
                    intval($status_field->getId()),
                    [
                        (int) $status_field_bind->getId(),
                    ],
                    'bind_value_ids'
                );
            }
        }

        if ($time_field) {
            $values[] = $this->createArtifactValuesRepresentation(
                intval($time_field->getId()),
                $time,
                'value'
            );
        }

        if ($results_field) {
            $values[] = $this->createArtifactValuesRepresentation(
                intval($results_field->getId()),
                $results,
                'value'
            );
        }

        if ($artifact_links_field) {
            $values[] = $this->createArtifactValuesRepresentation(
                intval($artifact_links_field->getId()),
                [
                    ['id' => $definition_id],
                ],
                'links'
            );
        }

        return $values;
    }

    /**
     * @param mixed $value
     */
    private function createArtifactValuesRepresentation(
        int $field_id,
        $value,
        string $key,
    ): ArtifactValuesRepresentation {
        $artifact_values_representation           = new ArtifactValuesRepresentation();
        $artifact_values_representation->field_id = $field_id;
        if ($key === 'value') {
            $artifact_values_representation->value = $value;
        } elseif ($key === 'bind_value_ids') {
            $artifact_values_representation->bind_value_ids = $value;
        } elseif ($key === 'links') {
            $artifact_values_representation->links = $value;
        }

        return $artifact_values_representation;
    }

    private function sendAllowHeadersForExecutionPut(Artifact $artifact): void
    {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsPut();
        Header::lastModified($date);
    }

    private function sendAllowHeadersForExecutionPost(Artifact $artifact): void
    {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsPost();
        Header::lastModified($date);
    }

    /**
     *
     *
     * @return Artifact
     * @throws RestException
     */
    private function getDefinitionOfExecution(PFUser $user, Artifact $execution_artifact)
    {
        try {
            return $this->definition_retriever->getDefinitionRepresentationForExecution(
                $user,
                $execution_artifact
            );
        } catch (DefinitionNotFoundException $e) {
            throw new RestException(400, 'The execution is not linked to a definition');
        }
    }

    /**
     * @param PFUser   $user
     * @param Artifact $artifact
     *
     * @return ExecutionRepresentation
     * @throws RestException
     */
    private function getExecutionRepresentation($user, $artifact)
    {
        try {
            return $this->execution_representation_builder->getExecutionRepresentation($user, $artifact);
        } catch (DefinitionNotFoundException $e) {
            throw new RestException(400, 'The execution is not linked to a definition');
        }
    }

    /**
     * @throws RestException
     */
    private function getCurrentUser(): PFUser
    {
        return $this->user_manager->getCurrentUser();
    }

    private function getFrozenFieldDetector(): FrozenFieldDetector
    {
        return new FrozenFieldDetector(
            new TransitionRetriever(
                new StateFactory(
                    TransitionFactory::instance(),
                    new SimpleWorkflowDao()
                ),
                new TransitionExtractor()
            ),
            new FrozenFieldsRetriever(
                new FrozenFieldsDao(),
                Tracker_FormElementFactory::instance()
            )
        );
    }

    private function getFileUploadDataProvider(): FileUploadDataProvider
    {
        return new FileUploadDataProvider($this->getFrozenFieldDetector(), $this->formelement_factory);
    }

    private function getExecutionChangesExtractor(): ExecutionChangesExtractor
    {
        return new ExecutionChangesExtractor(
            new FormattedChangesetValueForFileFieldRetriever($this->getFileUploadDataProvider()),
            new FormattedChangesetValueForIntFieldRetriever($this->formelement_factory),
            new FormattedChangesetValueForTextFieldRetriever($this->formelement_factory),
            new FormattedChangesetValueForListFieldRetriever($this->formelement_factory)
        );
    }
}
