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
use Http\Client\Common\Plugin\CookiePlugin;
use Http\Message\CookieJar;
use Jenkins_Client;
use Jenkins_ClientUnableToLaunchBuildException;
use Luracast\Restler\RestException;
use PFUser;
use ProjectManager;
use TemplateRenderer;
use Tracker_AfterSaveException;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_Artifact_PriorityManager;
use Tracker_ArtifactFactory;
use Tracker_ChangesetCommitException;
use Tracker_ChangesetNotCreatedException;
use Tracker_CommentNotStoredException;
use Tracker_Exception;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_FormElementFactory;
use Tracker_NoChangeException;
use Tracker_Permission_PermissionRetrieveAssignee;
use Tracker_Permission_PermissionsSerializer;
use Tracker_ReportFactory;
use Tracker_Semantic_StatusFactory;
use Tracker_URLVerification;
use TrackerFactory;
use TransitionFactory;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;
use Tuleap\Markdown\CodeBlockFeatures;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\EnhancedCodeBlockExtension;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\RealTimeMercure\ClientBuilder;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\ArtifactFactory;
use Tuleap\TestManagement\Campaign\ArtifactNotFoundException;
use Tuleap\TestManagement\Campaign\AutomatedTests\AutomatedTestsTriggerer;
use Tuleap\TestManagement\Campaign\AutomatedTests\NoJobConfiguredForCampaignException;
use Tuleap\TestManagement\Campaign\Campaign;
use Tuleap\TestManagement\Campaign\CampaignDao;
use Tuleap\TestManagement\Campaign\CampaignRetriever;
use Tuleap\TestManagement\Campaign\CampaignSaver;
use Tuleap\TestManagement\Campaign\Execution\DefinitionForExecutionRetriever;
use Tuleap\TestManagement\Campaign\Execution\DefinitionNotFoundException;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Campaign\JobConfiguration;
use Tuleap\TestManagement\Campaign\TestExecutionTestStatusDAO;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\ConfigConformanceValidator;
use Tuleap\TestManagement\Dao;
use Tuleap\TestManagement\LabelFieldNotFoundException;
use Tuleap\TestManagement\MilestoneItemsArtifactFactory;
use Tuleap\TestManagement\RealTime\RealTimeMessageSender;
use Tuleap\TestManagement\REST\ExecutionChangesExtractor;
use Tuleap\TestManagement\REST\FormattedChangesetValueForFileFieldRetriever;
use Tuleap\TestManagement\REST\FormattedChangesetValueForIntFieldRetriever;
use Tuleap\TestManagement\REST\FormattedChangesetValueForListFieldRetriever;
use Tuleap\TestManagement\REST\FormattedChangesetValueForTextFieldRetriever;
use Tuleap\TestManagement\REST\v1\DefinitionRepresentations\DefinitionRepresentationBuilder;
use Tuleap\TestManagement\REST\v1\Execution\ListOfDefinitionsForCampaignRetriever;
use Tuleap\TestManagement\REST\v1\Execution\StepsResultsFilter;
use Tuleap\TestManagement\REST\v1\Execution\StepsResultsRepresentationBuilder;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentContentNotValidException;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\CachingTrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsRunner;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactForwardLinksRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactLinksByChangesetCache;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksToNewChangesetsConverter;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdaterDataFormater;
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
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataFromValuesByFieldBuilder;
use Tuleap\Tracker\Rule\FirstValidValueAccordingToDependenciesRetriever;
use Tuleap\Tracker\Semantic\Status\SemanticStatusClosedValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\SemanticStatusNotDefinedException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\ValidValuesAccordingToTransitionsRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;
use UserManager;
use Workflow_Transition_ConditionFactory;

/**
 * @psalm-import-type StatusAcceptableValue from CampaignArtifactUpdateFieldValuesBuilder
 */
class CampaignsResource
{
    public const MAX_LIMIT = 50;
    /**
     * @var CampaignCreator
     */
    private $campaign_creator;

    /** @var Config */
    private $config;

    /** @var UserManager */
    private $user_manager;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var ExecutionCreator */
    private $execution_creator;

    /** @var ConfigConformanceValidator */
    private $conformance_validator;

    /** @var ExecutionRepresentationBuilder */
    private $execution_representation_builder;

    /** @var CampaignRepresentationBuilder */
    private $campaign_representation_builder;

    /** @var ProjectManager */
    private $project_manager;

    /** @var ArtifactLinkUpdater */
    private $artifactlink_updater;

    /** @var RealTimeMessageSender */
    private $realtime_message_sender;

    /** @var CampaignUpdater */
    private $campaign_updater;

    /** @var AutomatedTestsTriggerer */
    private $automated_triggerer;

    /** @var CampaignRetriever */
    private $campaign_retriever;

    /** @var ExecutionDao */
    private $execution_dao;
    private ArtifactUpdater $artifact_updater;
    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;
    /**
     * @var ArtifactFactory
     */
    private $testmanagement_artifact_factory;
    /**
     * @var ArtifactDao
     */
    private $artifact_dao;

    public function __construct()
    {
        $this->project_manager       = ProjectManager::instance();
        $this->user_manager          = UserManager::instance();
        $tracker_factory             = TrackerFactory::instance();
        $this->artifact_factory      = Tracker_ArtifactFactory::instance();
        $this->formelement_factory   = Tracker_FormElementFactory::instance();
        $this->config                = new Config(new Dao(), $tracker_factory);
        $this->conformance_validator = new ConfigConformanceValidator(
            $this->config
        );
        $this->artifact_dao          = new ArtifactDao();

        $this->testmanagement_artifact_factory = new ArtifactFactory(
            $this->config,
            $this->artifact_factory,
            $this->artifact_dao
        );

        $event_manager                    = EventManager::instance();
        $milestone_items_artifact_factory = new MilestoneItemsArtifactFactory(
            $this->config,
            $this->artifact_dao,
            $this->artifact_factory,
            $event_manager
        );

        $assigned_to_representation_builder = new AssignedToRepresentationBuilder(
            $this->formelement_factory,
            $this->user_manager
        );

        $requirement_retriever = new RequirementRetriever($this->artifact_factory, $this->artifact_dao, $this->config);
        $definition_retriever  = new DefinitionForExecutionRetriever($this->conformance_validator);

        $this->execution_dao                  = new ExecutionDao();
        $steps_results_representation_builder = new StepsResultsRepresentationBuilder(
            $this->formelement_factory,
            new StepsResultsFilter()
        );

        $purifier               = Codendi_HTMLPurifier::instance();
        $commonmark_interpreter = CommonMarkInterpreter::build($purifier, new EnhancedCodeBlockExtension(new CodeBlockFeatures()));

        $this->execution_representation_builder = new ExecutionRepresentationBuilder(
            $this->user_manager,
            $this->formelement_factory,
            $this->conformance_validator,
            $assigned_to_representation_builder,
            $this->artifact_dao,
            $this->artifact_factory,
            $definition_retriever,
            $this->execution_dao,
            $steps_results_representation_builder,
            new FileUploadDataProvider($this->getFrozenFieldDetector(), $this->formelement_factory),
            new DefinitionRepresentationBuilder(
                $this->formelement_factory,
                $this->conformance_validator,
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

        $campaign_dao = new CampaignDao();
        $key_factory  = new KeyFactory();

        $this->campaign_retriever = new CampaignRetriever($this->artifact_factory, $campaign_dao, $key_factory);

        $this->campaign_representation_builder = new CampaignRepresentationBuilder(
            $tracker_factory,
            $this->formelement_factory,
            $this->testmanagement_artifact_factory,
            $this->campaign_retriever,
            $this->config,
            new TestExecutionTestStatusDAO(),
            new StatusValueRetriever(
                new Tracker_Semantic_StatusFactory(),
                $this->getFirstPossibleValueInListRetriever()
            )
        );

        $artifact_link_initial_builder = new NewArtifactLinkInitialChangesetValueBuilder();
        $fields_data_builder           = new FieldsDataBuilder(
            $this->formelement_factory,
            new NewArtifactLinkChangesetValueBuilder(
                new ArtifactForwardLinksRetriever(
                    new ArtifactLinksByChangesetCache(),
                    new ChangesetValueArtifactLinkDao(),
                    $this->artifact_factory
                )
            ),
            $artifact_link_initial_builder
        );

        $usage_dao            = new ArtifactLinksUsageDao();
        $fields_retriever     = new FieldsToBeSavedInSpecificOrderRetriever($this->formelement_factory);
        $transaction_executor = new DBTransactionExecutorWithConnection(
            DBFactory::getMainTuleapDBConnection()
        );

        $changeset_creator = new NewChangesetCreator(
            new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                $this->formelement_factory,
                new ArtifactLinkValidator(
                    $this->artifact_factory,
                    new TypePresenterFactory(new TypeDao(), $usage_dao),
                    $usage_dao,
                    $event_manager,
                ),
                new WorkflowUpdateChecker($this->getFrozenFieldDetector())
            ),
            $fields_retriever,
            $event_manager,
            new \Tracker_Artifact_Changeset_ChangesetDataInitializator($this->formelement_factory),
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
                    new ItemToIndexQueueEventBased($event_manager),
                    $event_manager,
                    new \Tracker_Artifact_Changeset_CommentDao(),
                ),
                new TextValueValidator(),
            )
        );

        $artifact_creator = new ArtifactCreator(
            $fields_data_builder,
            $this->artifact_factory,
            $tracker_factory,
            new FieldsDataFromValuesByFieldBuilder($this->formelement_factory, $artifact_link_initial_builder),
            $this->formelement_factory,
            SubmissionPermissionVerifier::instance(),
            $transaction_executor,
            new ReverseLinksToNewChangesetsConverter($this->formelement_factory, $this->artifact_factory),
            $changeset_creator
        );

        $this->execution_creator = new ExecutionCreator(
            $this->formelement_factory,
            $this->config,
            $this->project_manager,
            $tracker_factory,
            $artifact_creator,
            $this->execution_dao
        );

        $definition_selector = new DefinitionSelector(
            $this->config,
            $this->testmanagement_artifact_factory,
            new ProjectAuthorization(),
            $this->artifact_factory,
            $milestone_items_artifact_factory,
            Tracker_ReportFactory::instance()
        );

        $this->campaign_creator = new CampaignCreator(
            $this->config,
            $this->project_manager,
            $this->formelement_factory,
            $tracker_factory,
            $definition_selector,
            $artifact_creator,
            $this->execution_creator
        );

        $this->artifact_updater = new ArtifactUpdater(
            $fields_data_builder,
            $changeset_creator,
            new ArtifactRestUpdateConditionsChecker(),
        );

        $this->campaign_updater = new CampaignUpdater(
            $this->artifact_updater,
            new CampaignSaver($campaign_dao, $key_factory),
            new CampaignArtifactUpdateFieldValuesBuilder(
                $this->formelement_factory,
                new StatusValueRetriever(
                    Tracker_Semantic_StatusFactory::instance(),
                    $this->getFirstPossibleValueInListRetriever()
                )
            )
        );

        $priority_manager = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            $this->user_manager,
            $this->artifact_factory
        );

        $this->artifactlink_updater = new ArtifactLinkUpdater($priority_manager, new ArtifactLinkUpdaterDataFormater());

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
        $this->realtime_message_sender   = new RealTimeMessageSender(
            $node_js_client,
            $permissions_serializer,
            $artifact_message_sender,
            $mercure_artifact_message_sender,
        );

        $http_client          = HttpClientFactory::createClient(new CookiePlugin(new CookieJar()));
        $http_request_factory = HTTPFactoryBuilder::requestFactory();

        $this->automated_triggerer = new AutomatedTestsTriggerer(
            new Jenkins_Client(
                $http_client,
                $http_request_factory,
                HTTPFactoryBuilder::streamFactory(),
                new JenkinsCSRFCrumbRetriever($http_client, $http_request_factory)
            )
        );
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
     * Get campaign
     *
     * Get testing campaign by its id
     *
     * @url GET {id}
     *
     * @param int $id Id of the campaign
     *
     * @return CampaignRepresentation
     *
     * @access hybrid
     *
     * @throws RestException 403
     */
    public function getId($id)
    {
        $this->optionsId($id);

        $user     = $this->getCurrentUser();
        $campaign = $this->getCampaignUserCanRead($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $campaign->getArtifact()->getTracker()->getProject()
        );

        return $this->campaign_representation_builder->getCampaignRepresentation($user, $campaign);
    }

    /**
     * @url OPTIONS {id}/testmanagement_executions
     *
     */
    public function optionsExecutions(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get executions
     *
     * Get executions of a given campaign
     * <br/>
     * <br/>
     * Test steps can be rendered as two formats: HTML or Text. Test steps rendered as HTML can have two source formats: HTML itself or CommonMark (Markdown).<br/>
     * Test steps that are already written in HTML have the following structure:
     * <pre><code>{<br/>
     *   &quot;description&quot;: &quot;&lt;p&gt;HTML description&lt;/p&gt;&quot;,<br/>
     *   &quot;description_format&quot;: &quot;html&quot;<br/>
     *   &quot;expected_results&quot;: &quot;HTML expected results&quot;,<br/>
     *   &quot;expected_results_format&quot;: &quot;html&quot;,<br/>
     *   "rank": 1<br/>
     * }</code></pre>
     * <p>Test steps that are written in CommonMark format (Markdown) have an additional "commonmark" property that contains the source.
     * Notice that they also have format "html":</p>
     * <pre><code>{<br/>
     *   &quot;description&quot;: &quot;&lt;p&gt;&lt;strong&gt;Markdown&lt;/strong&gt; description&lt;/p&gt;&quot;,<br/>
     *   &quot;description_format&quot;: &quot;html&quot;<br/>
     *   &quot;commonmark_description&quot;: &quot;\*\*Markdown\*\* description&quot;,<br/>
     *   &quot;expected_results&quot;: &quot;&lt;strong&gt;Markdown&lt;/strong&gt; expected results&quot;,<br/>
     *   &quot;expected_results_format&quot;: &quot;html&quot;,<br/>
     *   &quot;commonmark_expected_results&quot;: &quot;\*\*Markdown\*\* expected results&quot;,<br/>
     *   "rank": 1<br/>
     * }</code></pre>
     *
     * @url GET {id}/testmanagement_executions
     *
     * @param int $id     Id of the campaign
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type ExecutionRepresentation}
     *
     * @access hybrid
     *
     * @throws RestException 400
     * @throws RestException 403
     */
    public function getExecutions($id, $limit = 10, $offset = 0)
    {
        $this->optionsExecutions($id);

        $user     = $this->getCurrentUser();
        $campaign = $this->getCampaignUserCanRead($user, $id);
        $artifact = $campaign->getArtifact();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $artifact->getTracker()->getProject()
        );

        $execution_tracker = $this->config->getTestExecutionTracker($artifact->getTracker()->getProject());
        if (! $execution_tracker) {
            throw new RestException(400, "There isn't any execution tracker configured");
        }

        $execution_representations = $this->execution_representation_builder
            ->getPaginatedExecutionsRepresentationsForCampaign(
                $user,
                $artifact,
                $execution_tracker,
                $limit,
                $offset
            );

        $this->sendPaginationHeaders($limit, $offset, $execution_representations->getTotalSize());

        return $execution_representations->getRepresentations();
    }

    /**
     * PATCH test executions
     *
     * Create new test executions and unlink some test executions for a campaign
     *
     * @url PATCH {id}/testmanagement_executions
     *
     * @param int    $id                      Id of the campaign
     * @param array  $definition_ids_to_add   Test definition ids for which test executions should be created {@from body}
     * @param array  $execution_ids_to_remove Test execution ids which should be unlinked from the campaign {@from body}
     *
     * @return array {@type ExecutionRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 403
     */
    protected function patchExecutions($id, $definition_ids_to_add, $execution_ids_to_remove)
    {
        $user              = $this->getCurrentUser();
        $campaign          = $this->getCampaignUserCanRead($user, $id);
        $artifact          = $campaign->getArtifact();
        $project           = $artifact->getTracker()->getProject();
        $project_id        = $project->getID();
        $new_execution_ids = [];
        $executions_to_add = [];

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $project
        );

        if (! $artifact->isOpen()) {
            throw new I18NRestException(400, dgettext('plugin-testmanagement', 'The campaign is closed.'));
        }

        $execution_tracker = $this->config->getTestExecutionTracker($project);
        if (! $execution_tracker) {
            throw new RestException(400, "There isn't any execution tracker configured");
        }

        $execution_tracker_id = $this->config->getTestExecutionTrackerId($artifact->getTracker()->getProject());

        $linked_definitions = [];
        if ($execution_tracker_id) {
            $linked_definitions = $this->getListOfDefinitionsForCampaignRetriever()->getDefinitionListForCampaign(
                $user,
                $artifact,
                $execution_tracker_id
            );
        }

        foreach ($definition_ids_to_add as $definition_id) {
            if (isset($linked_definitions[$definition_id])) {
                continue;
            }

            $definition = $this->artifact_factory->getArtifactById($definition_id);
            if (! $definition) {
                throw new RestException(400, 'Invalid definition id ' . (int) $definition_id);
            }
            $new_execution_ref   = $this->execution_creator->createTestExecution(
                $project_id,
                $user,
                $definition
            );
            $new_execution_ids[] = $new_execution_ref->id;
            $executions_to_add[] = $new_execution_ref->getArtifact();
        }

        $executions_to_remove = $this->artifact_factory->getArtifactsByArtifactIdList($execution_ids_to_remove);

        $this->artifactlink_updater->updateArtifactLinks(
            $user,
            $artifact,
            $new_execution_ids,
            $execution_ids_to_remove,
            \Tracker_FormElement_Field_ArtifactLink::NO_TYPE
        );

        foreach ($executions_to_remove as $execution) {
            $this->execution_dao->removeExecution($execution->getId());
            $this->realtime_message_sender->sendExecutionDeleted($user, $artifact, $execution, $_SERVER[RealTimeMessageSender::HTTP_CLIENT_UUID]);
        }

        foreach ($executions_to_add as $execution) {
            $this->realtime_message_sender->sendExecutionCreated($user, $artifact, $execution, $_SERVER[RealTimeMessageSender::HTTP_CLIENT_UUID]);
        }

        $this->sendAllowHeadersForExecutionsList($artifact);

        $limit  = 10;
        $offset = 0;
        try {
            $execution_representations =
                $this->execution_representation_builder->getPaginatedExecutionsRepresentationsForCampaign(
                    $user,
                    $artifact,
                    $execution_tracker,
                    $limit,
                    $offset
                );
        } catch (DefinitionNotFoundException $e) {
            $execution_id = $e->getExecutionArtifact()->getId();
            throw new RestException(400, "The execution with id $execution_id is not linked to a definition");
        }

        $this->sendPaginationHeaders($limit, $offset, $execution_representations->getTotalSize());

        return $execution_representations->getRepresentations();
    }

    /**
     * @url OPTIONS
     *
     */
    public function options(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * POST campaign
     *
     * Create a new campaign
     *
     * @url POST
     *
     * @param int $project_id Id of the project the campaign will belong to
     * @param string $label The label of the new campaign
     * @param string $test_selector The method used to set initial test definitions for campaign {@from query} {@choice none,all,milestone,report}
     * @param int $milestone_id Id of the milestone with which the campaign will be linked {@from query}
     * @param int $report_id Id of the report to retrieve test definitions for campaign {@from query}
     *
     * @throws RestException 403
     *
     */
    protected function post($project_id, $label, $test_selector = 'all', $milestone_id = 0, $report_id = 0): \Tuleap\Tracker\REST\Artifact\ArtifactReference
    {
        $this->options();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $this->project_manager->getProject($project_id)
        );

        return $this->campaign_creator->createCampaign(
            UserManager::instance()->getCurrentUser(),
            $project_id,
            $label,
            $test_selector,
            $milestone_id,
            $report_id
        );
    }

    /**
     * PATCH campaign
     *
     * With this route it's possible to update label, job configuration and linked automated tests.
     * <br>
     * <br>
     * To update automated tests, update the campaign with automated tests result Junits and build url
     * this will update the corresponding tests
     * <br>
     * <br>
     * Exemple :
     *
     * If you have a test with 'automated tests' field file as 'automated' :
     * <br>
     * <pre>
     * {
     * &nbsp;"automated_tests_results" : {<br>
     * &nbsp;"build_url": "your/url",</br>
     * &nbsp;"junit_contents": [<br>
     * &nbsp;"&lt;?xml version=\\"1.0\\" encoding=\\"UTF-8\\"?&gt;
     * &nbsp;&lt;testsuites name=\\"fake test\\" time=\\"13\\" tests=\\"1\\" failures=\\"0\\"&gt;<br>
     * &nbsp;&lt;testsuite name=\\"fake test\\" timestamp=\\"2020-04-29T07:52:25\\" tests=\\"1\\" failures=\\"0\\" time=\\"6\\"&gt;<br>
     * &nbsp;&lt;testcase name=\\"automated\\" time=\\"6.105\\" classname=\\"Project administrator can start Kanban\\"&gt;&lt;/testcase><br>
     * &nbsp;&lt;/testsuite&gt;<br>
     * &nbsp;&lt;/testsuites&gt;"<br>
     * ]}}
     *</pre>
     *
     * @url    PATCH {id}
     *
     * @param int                                     $id Id of the campaign
     * @param string                                  $label New label of the campaign {@from body}
     * @param JobConfigurationRepresentation          $job_configuration {@from body}
     * @param AutomatedTestsResultPATCHRepresentation $automated_tests_results {@from body}
     * @param string | null                           $change_status {@from body} {@required false} {@choice closed,open}
     * @psalm-param StatusAcceptableValue             $change_status
     *
     * @return CampaignRepresentation
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 500
     */
    protected function patch(
        $id,
        $label = null,
        ?JobConfigurationRepresentation $job_configuration = null,
        ?AutomatedTestsResultPATCHRepresentation $automated_tests_results = null,
        ?string $change_status = null,
    ) {
        $user              = $this->getCurrentUser();
        $campaign          = $this->getUpdatedCampaign($user, $id, $label, $job_configuration);
        $campaign_artifact = $campaign->getArtifact();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $campaign_artifact->getTracker()->getProject()
        );

        if (! $campaign_artifact->userCanUpdate($user)) {
            throw new RestException(403, "You don't have the permission to update this campaign");
        }

        if ($automated_tests_results !== null && ! $campaign_artifact->isOpen()) {
            throw new I18NRestException(400, dgettext('plugin-testmanagement', 'The campaign is closed.'));
        }

        try {
            $this->campaign_updater->updateCampaign(
                $user,
                $campaign,
                $change_status
            );
        } catch (Tracker_ChangesetNotCreatedException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_CommentNotStoredException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (CommentContentNotValidException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_AfterSaveException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_ChangesetCommitException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (LabelFieldNotFoundException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Exception $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (
            SemanticStatusNotDefinedException |
            SemanticStatusClosedValueNotFoundException |
            NoPossibleValueException $exception
        ) {
            throw new RestException(400, $exception->getMessage());
        }

        if ($automated_tests_results !== null) {
            try {
                $this->getExecutionsFromAutomatedTestsUpdater()->updateExecutionFromAutomatedTests(
                    $automated_tests_results,
                    $campaign_artifact,
                    $user
                );
            } catch (AutomatedTestsNotXmlException $exception) {
                throw new RestException(400, $exception->getMessage());
            }
        }

        $campaign_representation = $this->campaign_representation_builder->getCampaignRepresentation($user, $campaign);

        $this->realtime_message_sender->sendCampaignUpdated($user, $campaign->getArtifact(), $_SERVER[RealTimeMessageSender::HTTP_CLIENT_UUID]);

        $this->sendAllowHeadersForCampaign($campaign);

        return $campaign_representation;
    }

    /**
     * POST automated tests
     *
     * <pre>/!\ Experimental. DO NOT USE</pre>
     *
     * @url POST {id}/automated_tests
     *
     * @param int $id Id of the campaign
     *
     * @throws RestException 403
     *
     */
    protected function postAutomatedTests($id): void
    {
        $this->options();

        $user              = $this->getCurrentUser();
        $campaign          = $this->getCampaignUserCanRead($user, $id);
        $campaign_artifact = $campaign->getArtifact();

        if (! $campaign_artifact->isOpen()) {
            throw new I18NRestException(400, dgettext('plugin-testmanagement', 'The campaign is closed.'));
        }

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $campaign->getArtifact()->getTracker()->getProject()
        );

        try {
            $this->automated_triggerer->triggerAutomatedTests($campaign);
        } catch (Jenkins_ClientUnableToLaunchBuildException $e) {
            throw new RestException(500, $e->getMessage());
        } catch (NoJobConfiguredForCampaignException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }

    private function sendPaginationHeaders(int $limit, int $offset, int $size): void
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeadersForExecutionsList(Artifact $artifact): void
    {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsPatch();
        Header::lastModified($date);
    }

    private function sendAllowHeadersForCampaign(Campaign $campaign): void
    {
        $date = $campaign->getArtifact()->getLastUpdateDate();
        Header::allowOptionsPatch();
        Header::lastModified($date);
    }

    /**
     * @param int                                 $id
     * @param string|null                         $label
     *
     * @return Campaign
     * @throws RestException
     */
    private function getUpdatedCampaign(
        PFUser $user,
        $id,
        $label = null,
        ?JobConfigurationRepresentation $job_representation = null,
    ) {
        $campaign = $this->getCampaignUserCanRead($user, $id);

        $this->overrideWithSubmittedData($campaign, $label, $job_representation);

        return $campaign;
    }

    /**
     * @param int    $id
     *
     * @return Campaign
     * @throws RestException
     */
    private function getCampaignUserCanRead(PFUser $user, $id)
    {
        try {
            $campaign = $this->campaign_retriever->getById($id);
            $this->checkUserCanReadCampaign($user, $campaign);
        } catch (ArtifactNotFoundException $e) {
            throw new RestException(404);
        }

        return $campaign;
    }

    /**
     * @throws RestException
     *
     */
    private function checkUserCanReadCampaign(PFUser $user, Campaign $campaign): void
    {
        $artifact = $campaign->getArtifact();

        if (! $this->conformance_validator->isArtifactACampaign($artifact)) {
            throw new RestException(404, 'The campaign does not exist');
        }

        if (! $artifact->userCanView($user)) {
            throw new RestException(403);
        }

        ProjectAuthorization::userCanAccessProject(
            $user,
            $artifact->getTracker()->getProject(),
            new Tracker_URLVerification()
        );
    }

    /**
     * @param string|null                         $label
     *
     */
    private function overrideWithSubmittedData(
        Campaign $campaign,
        $label = null,
        ?JobConfigurationRepresentation $job_representation = null,
    ): void {
        if ($label) {
            $campaign->setLabel($label);
        }

        if ($job_representation) {
            $uri_validator = new \Valid_HTTPURI();
            $uri_validator->disableFeedback();
            if (! $uri_validator->validate($job_representation->url)) {
                throw new RestException(
                    400,
                    dgettext('tuleap-testmanagement', 'Job URL is invalid')
                );
            }

            $job_configuration = new JobConfiguration(
                $job_representation->url ?? '',
                new ConcealedString($job_representation->token ?? '')
            );
            $campaign->setJobConfiguration($job_configuration);
        }
    }

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

    private function getListOfDefinitionsForCampaignRetriever(): ListOfDefinitionsForCampaignRetriever
    {
        return new ListOfDefinitionsForCampaignRetriever(
            $this->artifact_dao,
            $this->artifact_factory,
            $this->getDefinitionForExecutionRetriever()
        );
    }

    private function getExecutionsFromAutomatedTestsUpdater(): ExecutionFromAutomatedTestsUpdater
    {
        return new ExecutionFromAutomatedTestsUpdater(
            $this->getExecutionStatusUpdater(),
            $this->getExecutionChangesExtractor(),
            new TestsDataFromJunitExtractor($this->getRendererForJUnitExtraction()),
            $this->getExecutionsWithAutomatedTestDataRetriever(),
        );
    }

    private function getExecutionsWithAutomatedTestDataRetriever(): ListOfExecutionsWithAutomatedTestDataRetriever
    {
        return new ListOfExecutionsWithAutomatedTestDataRetriever(
            $this->config,
            $this->artifact_dao,
            $this->getDefinitionForExecutionRetriever(),
            new ExecutionWithAutomatedTestDataProvider(
                new ExecutionDao(),
                $this->formelement_factory
            ),
            $this->artifact_factory,
        );
    }

    private function getExecutionStatusUpdater(): ExecutionStatusUpdater
    {
        return new ExecutionStatusUpdater(
            $this->artifact_updater,
            $this->testmanagement_artifact_factory,
            $this->realtime_message_sender,
            $this->user_manager
        );
    }

    private function getExecutionChangesExtractor(): ExecutionChangesExtractor
    {
        return new ExecutionChangesExtractor(
            new FormattedChangesetValueForFileFieldRetriever(
                new FileUploadDataProvider(
                    $this->getFrozenFieldDetector(),
                    $this->formelement_factory
                )
            ),
            new FormattedChangesetValueForIntFieldRetriever($this->formelement_factory),
            new FormattedChangesetValueForTextFieldRetriever($this->formelement_factory),
            new FormattedChangesetValueForListFieldRetriever($this->formelement_factory)
        );
    }

    private function getRendererForJUnitExtraction(): TemplateRenderer
    {
        $templates_path = join(
            '/',
            [
                TESTMANAGEMENT_BASE_DIR,
                'templates',
                'TestsDataJUnitExtraction',
            ]
        );

        return \TemplateRendererFactory::build()->getRenderer($templates_path);
    }

    private function getDefinitionForExecutionRetriever(): DefinitionForExecutionRetriever
    {
        return new DefinitionForExecutionRetriever(
            new ConfigConformanceValidator($this->config)
        );
    }

    private function getFirstPossibleValueInListRetriever(): FirstPossibleValueInListRetriever
    {
        return new FirstPossibleValueInListRetriever(
            new FirstValidValueAccordingToDependenciesRetriever(
                $this->formelement_factory
            ),
            new ValidValuesAccordingToTransitionsRetriever(
                Workflow_Transition_ConditionFactory::build()
            )
        );
    }
}
