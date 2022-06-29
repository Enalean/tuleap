<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Git\Events\GetExternalUsedServiceEvent;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\GitlabHTTPClientFactory;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\API\Tag\GitlabTagRetriever;
use Tuleap\Gitlab\Artifact\Action\CreateBranchButtonFetcher;
use Tuleap\Gitlab\Artifact\Action\CreateBranchPrefixDao;
use Tuleap\Gitlab\Artifact\ArtifactRetriever;
use Tuleap\Gitlab\EventsHandlers\ReferenceAdministrationWarningsCollectorEventHandler;
use Tuleap\Gitlab\Plugin\GitlabIntegrationAvailabilityChecker;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchCrossReferenceEnhancer;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchFactory;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchReference;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitCrossReferenceEnhancer;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitFactory;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitReference;
use Tuleap\Gitlab\Reference\GitlabCrossReferenceOrganizer;
use Tuleap\Gitlab\Reference\GitlabReferenceBuilder;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReference;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReferenceRetriever;
use Tuleap\Gitlab\Reference\Tag\GitlabTagFactory;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\GitlabRepositoryWebhookController;
use Tuleap\Gitlab\Repository\IntegrationWebhookController;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectDao;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenDao;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenRetriever;
use Tuleap\Gitlab\Repository\Webhook\Bot\BotCommentReferencePresenterBuilder;
use Tuleap\Gitlab\Repository\Webhook\Bot\CommentSender;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\Bot\InvalidCredentialsNotifier;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\CrossReferenceFromMergeRequestCreator;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\MergeRequestTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestBotCommenter;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookActionProcessor;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookAuthorDataRetriever;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookDataBuilder;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PreviouslySavedReferencesRetriever;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\TuleapReferencesFromMergeRequestDataExtractor;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\BranchNameTuleapReferenceParser;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\BranchInfoDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\PostPushWebhookActionBranchHandler;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushCommitArtifactUpdater;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushCommitBotCommenter;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushCommitWebhookDataExtractor;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookActionProcessor;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookCloseArtifactHandler;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookDataBuilder;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PrefixedLogger;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretChecker;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretRetriever;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagInfoDao;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagPushWebhookActionProcessor;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagPushWebhookCreateAction;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagPushWebhookDataBuilder;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagPushWebhookDeleteAction;
use Tuleap\Gitlab\Repository\Webhook\WebhookActions;
use Tuleap\Gitlab\Repository\Webhook\WebhookDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookDataExtractor;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;
use Tuleap\Gitlab\REST\ResourcesInjector;
use Tuleap\Gitlab\REST\v1\GitlabRepositoryRepresentationFactory;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\Admin\Reference\Browse\ExternalSystemReferencePresenter;
use Tuleap\Project\Admin\Reference\Browse\ExternalSystemReferencePresentersCollector;
use Tuleap\Project\Admin\Reference\ReferenceAdministrationWarningsCollectorEvent;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\GetReferenceEvent;
use Tuleap\Reference\Nature;
use Tuleap\Reference\NatureCollection;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalArtifactActionButtonsFetcher;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\CommentOnlyChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsRunner;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Rule\FirstValidValueAccordingToDependenciesRetriever;
use Tuleap\Tracker\Semantic\Status\Done\DoneValueRetriever;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDao;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneUsedExternalService;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneUsedExternalServiceEvent;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueChecker;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\ValidValuesAccordingToTransitionsRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../git/include/gitPlugin.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class gitlabPlugin extends Plugin
{
    public const SERVICE_NAME          = "gitlab";
    public const LOG_IDENTIFIER        = "gitlab_syslog";
    private const POST_PUSH_LOG_PREFIX = '|  |  |_ ';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-gitlab', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\Gitlab\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(GetExternalUsedServiceEvent::NAME);

        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_PROJECT_RESOURCES);

        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(GetReferenceEvent::NAME);

        $this->addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES);
        $this->addHook(Event::GET_REFERENCE_ADMIN_CAPABILITIES);
        $this->addHook(NatureCollection::NAME);
        $this->addHook(ReferenceAdministrationWarningsCollectorEvent::NAME);
        $this->addHook(CrossReferenceByNatureOrganizer::NAME);

        $this->addHook(ExternalSystemReferencePresentersCollector::NAME);
        $this->addHook(SemanticDoneUsedExternalServiceEvent::NAME);
        $this->addHook(AdditionalArtifactActionButtonsFetcher::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getDependencies(): array
    {
        return ['git', 'tracker'];
    }

    public function getExternalUsedServiceEvent(GetExternalUsedServiceEvent $event): void
    {
        $project        = $event->getProject();
        $is_gitlab_used = $this->isAllowed((int) $project->getGroupId());

        if (! $is_gitlab_used) {
            return;
        }

        $event->addUsedServiceName(self::SERVICE_NAME);
    }

    /**
     * @see Event::REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new ResourcesInjector();
        $injector->declareProjectGitlabResource($params['resources'], $params['project']);
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources(array $params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup('/plugins/gitlab', function (FastRoute\RouteCollector $r) {
            $r->post('/repository/webhook', $this->getRouteHandler('routePostGitlabRepositoryWebhook'));
            $r->post('/integration/{integration_id:\d+}/webhook', $this->getRouteHandler('routePostIntegrationWebhook'));
        });
    }

    public function routePostGitlabRepositoryWebhook(): GitlabRepositoryWebhookController
    {
        $logger            = BackendLogger::getDefaultLogger(self::LOG_IDENTIFIER);
        $reference_manager = ReferenceManager::instance();

        $request_factory       = HTTPFactoryBuilder::requestFactory();
        $stream_factory        = HTTPFactoryBuilder::streamFactory();
        $gitlab_client_factory = new GitlabHTTPClientFactory(HttpClientFactory::createClient());
        $gitlab_api_client     = new ClientWrapper($request_factory, $stream_factory, $gitlab_client_factory);

        $event_manager              = EventManager::instance();
        $user_manager               = UserManager::instance();
        $tuleap_reference_retriever = new TuleapReferenceRetriever($event_manager, $reference_manager);

        $merge_request_reference_dao = new MergeRequestTuleapReferenceDao();

        $references_from_merge_request_data_extractor = new TuleapReferencesFromMergeRequestDataExtractor(
            new WebhookTuleapReferencesParser(),
            new BranchNameTuleapReferenceParser(),
        );

        $comment_sender = new CommentSender(
            $gitlab_api_client,
            new InvalidCredentialsNotifier(
                new MailBuilder(
                    TemplateRendererFactory::build(),
                    new MailFilter(
                        $user_manager,
                        new ProjectAccessChecker(
                            new RestrictedUserCanAccessProjectVerifier(),
                            $event_manager
                        ),
                        new MailLogger()
                    ),
                ),
                new IntegrationApiTokenDao(),
                $logger,
            ),
        );

        $credentials_retriever          = new CredentialsRetriever(
            new IntegrationApiTokenRetriever(new IntegrationApiTokenDao(), new KeyFactory())
        );
        $commenter                      = new PostPushCommitBotCommenter(
            $comment_sender,
            $credentials_retriever,
            $logger,
            new BotCommentReferencePresenterBuilder(),
            TemplateRendererFactory::build(),
        );
        $form_element_factory           = \Tracker_FormElementFactory::instance();
        $first_possible_value_retriever = new FirstPossibleValueInListRetriever(
            new FirstValidValueAccordingToDependenciesRetriever($form_element_factory),
            new ValidValuesAccordingToTransitionsRetriever(
                Workflow_Transition_ConditionFactory::build()
            )
        );
        $artifact_links_usage_dao       = new ArtifactLinksUsageDao();
        $db_transaction_executor        = new DBTransactionExecutorWithConnection(
            DBFactory::getMainTuleapDBConnection()
        );
        $artifact_factory               = \Tracker_ArtifactFactory::instance();
        $fields_retriever               = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);
        $semantic_status_factory        = Tracker_Semantic_StatusFactory::instance();
        $tag_info_dao                   = new TagInfoDao();
        $cross_reference_manager        = new CrossReferenceManager();

        $comment_creator = new CommentOnlyChangesetCreator(
            new NewChangesetCreator(
                new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                    $form_element_factory,
                    new ArtifactLinkValidator(
                        $artifact_factory,
                        new TypePresenterFactory(new TypeDao(), $artifact_links_usage_dao),
                        $artifact_links_usage_dao
                    ),
                    new WorkflowUpdateChecker(
                        new FrozenFieldDetector(
                            new TransitionRetriever(
                                new StateFactory(
                                    TransitionFactory::instance(),
                                    new SimpleWorkflowDao()
                                ),
                                new TransitionExtractor()
                            ),
                            FrozenFieldsRetriever::instance()
                        )
                    )
                ),
                $fields_retriever,
                $event_manager,
                new \Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
                $db_transaction_executor,
                ArtifactChangesetSaver::build(),
                new ParentLinkAction($artifact_factory),
                new AfterNewChangesetHandler($artifact_factory, $fields_retriever),
                ActionsRunner::build(\BackendLogger::getDefaultLogger()),
                new ChangesetValueSaver(),
                \WorkflowFactory::instance(),
                new CommentCreator(
                    new \Tracker_Artifact_Changeset_CommentDao(),
                    $reference_manager,
                    new TrackerPrivateCommentUGroupPermissionInserter(
                        new TrackerPrivateCommentUGroupPermissionDao()
                    )
                )
            )
        );

        return new GitlabRepositoryWebhookController(
            new WebhookDataExtractor(
                new PostPushWebhookDataBuilder(
                    new PostPushCommitWebhookDataExtractor(
                        $logger
                    )
                ),
                new PostMergeRequestWebhookDataBuilder($logger),
                new TagPushWebhookDataBuilder(),
                $logger
            ),
            $this->getGitlabRepositoryIntegrationFactory(),
            new SecretChecker(
                new SecretRetriever(
                    new WebhookDao(),
                    new KeyFactory()
                )
            ),
            new WebhookActions(
                new GitlabRepositoryIntegrationDao(),
                new PostPushWebhookActionProcessor(
                    new WebhookTuleapReferencesParser(),
                    new CommitTuleapReferenceDao(),
                    $reference_manager,
                    $tuleap_reference_retriever,
                    $logger,
                    $commenter,
                    new PostPushWebhookCloseArtifactHandler(
                        new PostPushCommitArtifactUpdater(
                            new StatusValueRetriever($semantic_status_factory, $first_possible_value_retriever),
                            new DoneValueRetriever(
                                new SemanticDoneFactory(
                                    new SemanticDoneDao(),
                                    new SemanticDoneValueChecker(),
                                ),
                                $first_possible_value_retriever
                            ),
                            $user_manager,
                            $logger,
                            $comment_creator
                        ),
                        new ArtifactRetriever($artifact_factory),
                        $user_manager,
                        $semantic_status_factory,
                        new GitlabRepositoryProjectDao(),
                        $credentials_retriever,
                        new GitlabProjectBuilder($gitlab_api_client),
                        $logger
                    ),
                    new PostPushWebhookActionBranchHandler(
                        new BranchNameTuleapReferenceParser(),
                        $reference_manager,
                        $tuleap_reference_retriever,
                        new BranchInfoDao(),
                        new CrossReferenceDao(),
                        $cross_reference_manager,
                        $logger
                    )
                ),
                new PostMergeRequestWebhookActionProcessor(
                    $merge_request_reference_dao,
                    $logger,
                    new PostMergeRequestBotCommenter(
                        $comment_sender,
                        $credentials_retriever,
                        $logger,
                        new BotCommentReferencePresenterBuilder(),
                        TemplateRendererFactory::build()
                    ),
                    new PreviouslySavedReferencesRetriever(
                        $references_from_merge_request_data_extractor,
                        $tuleap_reference_retriever,
                        $merge_request_reference_dao,
                    ),
                    new CrossReferenceFromMergeRequestCreator(
                        $references_from_merge_request_data_extractor,
                        $tuleap_reference_retriever,
                        $reference_manager,
                        $logger,
                    ),
                    new PostMergeRequestWebhookAuthorDataRetriever(
                        $gitlab_api_client,
                        $credentials_retriever
                    ),
                    new GitlabMergeRequestReferenceRetriever(new MergeRequestTuleapReferenceDao())
                ),
                new TagPushWebhookActionProcessor(
                    new TagPushWebhookCreateAction(
                        $credentials_retriever,
                        new GitlabTagRetriever(
                            $gitlab_api_client
                        ),
                        new WebhookTuleapReferencesParser(),
                        $tuleap_reference_retriever,
                        $reference_manager,
                        $tag_info_dao,
                        $logger
                    ),
                    new TagPushWebhookDeleteAction(
                        $tag_info_dao,
                        $cross_reference_manager,
                        $logger
                    ),
                    $db_transaction_executor
                ),
                $logger,
            ),
            $logger,
            HTTPFactoryBuilder::responseFactory(),
            new SapiEmitter(),
            new \Tuleap\Http\Server\ServiceInstrumentationMiddleware(self::SERVICE_NAME)
        );
    }

    public function routePostIntegrationWebhook(): IntegrationWebhookController
    {
        $logger            = BackendLogger::getDefaultLogger(self::LOG_IDENTIFIER);
        $prefixed_logger   = new PrefixedLogger($logger, self::POST_PUSH_LOG_PREFIX);
        $reference_manager = ReferenceManager::instance();

        $request_factory       = HTTPFactoryBuilder::requestFactory();
        $stream_factory        = HTTPFactoryBuilder::streamFactory();
        $gitlab_client_factory = new GitlabHTTPClientFactory(HttpClientFactory::createClient());
        $gitlab_api_client     = new ClientWrapper($request_factory, $stream_factory, $gitlab_client_factory);
        $event_manager         = EventManager::instance();
        $user_manager          = UserManager::instance();

        $tuleap_reference_retriever  = new TuleapReferenceRetriever($event_manager, $reference_manager);
        $merge_request_reference_dao = new MergeRequestTuleapReferenceDao();

        $references_from_merge_request_data_extractor = new TuleapReferencesFromMergeRequestDataExtractor(
            new WebhookTuleapReferencesParser(),
            new BranchNameTuleapReferenceParser(),
        );

        $comment_sender = new CommentSender(
            $gitlab_api_client,
            new InvalidCredentialsNotifier(
                new MailBuilder(
                    TemplateRendererFactory::build(),
                    new MailFilter(
                        $user_manager,
                        new ProjectAccessChecker(
                            new RestrictedUserCanAccessProjectVerifier(),
                            $event_manager
                        ),
                        new MailLogger()
                    ),
                ),
                new IntegrationApiTokenDao(),
                $logger,
            ),
        );

        $credentials_retriever = new CredentialsRetriever(
            new IntegrationApiTokenRetriever(new IntegrationApiTokenDao(), new KeyFactory())
        );
        $commenter             = new PostPushCommitBotCommenter(
            $comment_sender,
            $credentials_retriever,
            $logger,
            new BotCommentReferencePresenterBuilder(),
            TemplateRendererFactory::build(),
        );

        $form_element_factory           = Tracker_FormElementFactory::instance();
        $first_possible_value_retriever = new FirstPossibleValueInListRetriever(
            new FirstValidValueAccordingToDependenciesRetriever($form_element_factory),
            new ValidValuesAccordingToTransitionsRetriever(
                \Workflow_Transition_ConditionFactory::build()
            )
        );
        $semantic_status_factory        = \Tracker_Semantic_StatusFactory::instance();
        $artifact_factory               = Tracker_ArtifactFactory::instance();
        $tag_info_dao                   = new TagInfoDao();
        $cross_reference_manager        = new CrossReferenceManager();
        $db_transaction_executor        = new DBTransactionExecutorWithConnection(
            DBFactory::getMainTuleapDBConnection()
        );
        $fields_retriever               = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);
        $artifact_links_usage_dao       = new ArtifactLinksUsageDao();

        $comment_creator = new CommentOnlyChangesetCreator(
            new NewChangesetCreator(
                new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                    $form_element_factory,
                    new ArtifactLinkValidator(
                        $artifact_factory,
                        new TypePresenterFactory(new TypeDao(), $artifact_links_usage_dao),
                        $artifact_links_usage_dao
                    ),
                    new WorkflowUpdateChecker(
                        new FrozenFieldDetector(
                            new TransitionRetriever(
                                new StateFactory(
                                    TransitionFactory::instance(),
                                    new SimpleWorkflowDao()
                                ),
                                new TransitionExtractor()
                            ),
                            FrozenFieldsRetriever::instance()
                        )
                    )
                ),
                $fields_retriever,
                $event_manager,
                new \Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
                $db_transaction_executor,
                ArtifactChangesetSaver::build(),
                new ParentLinkAction($artifact_factory),
                new AfterNewChangesetHandler($artifact_factory, $fields_retriever),
                ActionsRunner::build(\BackendLogger::getDefaultLogger()),
                new ChangesetValueSaver(),
                \WorkflowFactory::instance(),
                new CommentCreator(
                    new \Tracker_Artifact_Changeset_CommentDao(),
                    $reference_manager,
                    new TrackerPrivateCommentUGroupPermissionInserter(
                        new TrackerPrivateCommentUGroupPermissionDao()
                    )
                )
            )
        );

        return new IntegrationWebhookController(
            new WebhookDataExtractor(
                new PostPushWebhookDataBuilder(
                    new PostPushCommitWebhookDataExtractor(
                        $logger
                    )
                ),
                new PostMergeRequestWebhookDataBuilder($logger),
                new TagPushWebhookDataBuilder(),
                $logger
            ),
            $this->getGitlabRepositoryIntegrationFactory(),
            new SecretChecker(
                new SecretRetriever(
                    new WebhookDao(),
                    new KeyFactory()
                )
            ),
            new WebhookActions(
                new GitlabRepositoryIntegrationDao(),
                new PostPushWebhookActionProcessor(
                    new WebhookTuleapReferencesParser(),
                    new CommitTuleapReferenceDao(),
                    $reference_manager,
                    $tuleap_reference_retriever,
                    $logger,
                    $commenter,
                    new PostPushWebhookCloseArtifactHandler(
                        new PostPushCommitArtifactUpdater(
                            new StatusValueRetriever($semantic_status_factory, $first_possible_value_retriever),
                            new DoneValueRetriever(
                                new SemanticDoneFactory(
                                    new SemanticDoneDao(),
                                    new SemanticDoneValueChecker()
                                ),
                                $first_possible_value_retriever
                            ),
                            $user_manager,
                            $prefixed_logger,
                            $comment_creator
                        ),
                        new ArtifactRetriever($artifact_factory),
                        $user_manager,
                        $semantic_status_factory,
                        new GitlabRepositoryProjectDao(),
                        $credentials_retriever,
                        new GitlabProjectBuilder($gitlab_api_client),
                        $prefixed_logger
                    ),
                    new PostPushWebhookActionBranchHandler(
                        new BranchNameTuleapReferenceParser(),
                        $reference_manager,
                        $tuleap_reference_retriever,
                        new BranchInfoDao(),
                        new CrossReferenceDao(),
                        $cross_reference_manager,
                        $logger
                    )
                ),
                new PostMergeRequestWebhookActionProcessor(
                    $merge_request_reference_dao,
                    $logger,
                    new PostMergeRequestBotCommenter(
                        $comment_sender,
                        $credentials_retriever,
                        $logger,
                        new BotCommentReferencePresenterBuilder(),
                        TemplateRendererFactory::build()
                    ),
                    new PreviouslySavedReferencesRetriever(
                        $references_from_merge_request_data_extractor,
                        $tuleap_reference_retriever,
                        $merge_request_reference_dao,
                    ),
                    new CrossReferenceFromMergeRequestCreator(
                        $references_from_merge_request_data_extractor,
                        $tuleap_reference_retriever,
                        $reference_manager,
                        $logger,
                    ),
                    new PostMergeRequestWebhookAuthorDataRetriever(
                        $gitlab_api_client,
                        $credentials_retriever
                    ),
                    new GitlabMergeRequestReferenceRetriever(new MergeRequestTuleapReferenceDao())
                ),
                new TagPushWebhookActionProcessor(
                    new TagPushWebhookCreateAction(
                        $credentials_retriever,
                        new GitlabTagRetriever($gitlab_api_client),
                        new WebhookTuleapReferencesParser(),
                        $tuleap_reference_retriever,
                        $reference_manager,
                        $tag_info_dao,
                        $logger
                    ),
                    new TagPushWebhookDeleteAction(
                        $tag_info_dao,
                        $cross_reference_manager,
                        $logger
                    ),
                    $db_transaction_executor
                ),
                $logger,
            ),
            $logger,
            HTTPFactoryBuilder::responseFactory(),
            new SapiEmitter(),
            new \Tuleap\Http\Server\ServiceInstrumentationMiddleware(self::SERVICE_NAME)
        );
    }

    public function getReference(GetReferenceEvent $event): void
    {
        if (
            $event->getKeyword() === GitlabCommitReference::REFERENCE_NAME ||
            $event->getKeyword() === GitlabMergeRequestReference::REFERENCE_NAME ||
            $event->getKeyword() === GitlabTagReference::REFERENCE_NAME ||
            $event->getKeyword() === GitlabBranchReference::REFERENCE_NAME
        ) {
            $builder = new GitlabReferenceBuilder(
                new \Tuleap\Gitlab\Reference\ReferenceDao(),
                $this->getGitlabRepositoryIntegrationFactory()
            );

            $reference = $builder->buildGitlabReference(
                $event->getProject(),
                $event->getKeyword(),
                $event->getValue()
            );

            if ($reference !== null) {
                $event->setReference($reference);
            }
        }
    }

    public function get_plugins_available_keywords_references(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['keywords'][] = GitlabCommitReference::REFERENCE_NAME;
        $params['keywords'][] = GitlabMergeRequestReference::REFERENCE_NAME;
        $params['keywords'][] = GitlabTagReference::REFERENCE_NAME;
        $params['keywords'][] = GitlabBranchReference::REFERENCE_NAME;
    }

    /** @see \Event::GET_REFERENCE_ADMIN_CAPABILITIES */
    public function get_reference_admin_capabilities(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $reference = $params['reference'];
        \assert($reference instanceof Reference);

        if (
            $reference->getNature() === GitlabCommitReference::NATURE_NAME ||
            $reference->getNature() === GitlabMergeRequestReference::NATURE_NAME ||
            $reference->getNature() === GitlabTagReference::NATURE_NAME ||
            $reference->getNature() === GitlabBranchReference::NATURE_NAME
        ) {
            $params['can_be_deleted'] = false;
            $params['can_be_edited']  = false;
        }
    }

    private function getGitlabRepositoryIntegrationFactory(): GitlabRepositoryIntegrationFactory
    {
        return new GitlabRepositoryIntegrationFactory(
            new GitlabRepositoryIntegrationDao(),
            ProjectManager::instance()
        );
    }

    public function getAvailableReferenceNatures(NatureCollection $natures): void
    {
        $natures->addNature(
            GitlabCommitReference::NATURE_NAME,
            new Nature(
                GitlabCommitReference::REFERENCE_NAME,
                'fab fa-gitlab',
                dgettext('tuleap-gitlab', 'GitLab commit'),
                false
            )
        );

        $natures->addNature(
            GitlabMergeRequestReference::NATURE_NAME,
            new Nature(
                GitlabMergeRequestReference::REFERENCE_NAME,
                'fab fa-gitlab',
                dgettext('tuleap-gitlab', 'GitLab merge request'),
                false
            )
        );

        $natures->addNature(
            GitlabTagReference::NATURE_NAME,
            new Nature(
                GitlabTagReference::REFERENCE_NAME,
                'fab fa-gitlab',
                dgettext('tuleap-gitlab', 'GitLab Tag'),
                false
            )
        );

        $natures->addNature(
            GitlabBranchReference::NATURE_NAME,
            new Nature(
                GitlabBranchReference::REFERENCE_NAME,
                'fab fa-gitlab',
                dgettext('tuleap-gitlab', 'GitLab Branch'),
                false
            )
        );
    }

    public function referenceAdministrationWarningsCollectorEvent(ReferenceAdministrationWarningsCollectorEvent $event): void
    {
        (new ReferenceAdministrationWarningsCollectorEventHandler())->handle($event);
    }

    public function crossReferenceByNatureOrganizer(CrossReferenceByNatureOrganizer $organizer): void
    {
        $repository_integration_dao = new GitlabRepositoryIntegrationDao();
        $gitlab_organizer           = new GitlabCrossReferenceOrganizer(
            new GitlabRepositoryIntegrationFactory($repository_integration_dao, ProjectManager::instance()),
            new GitlabCommitFactory(new CommitTuleapReferenceDao()),
            new GitlabCommitCrossReferenceEnhancer(
                \UserManager::instance(),
                \UserHelper::instance(),
                new TlpRelativeDatePresenterBuilder()
            ),
            new GitlabMergeRequestReferenceRetriever(new MergeRequestTuleapReferenceDao()),
            new GitlabTagFactory(
                new TagInfoDao()
            ),
            new GitlabBranchFactory(
                new BranchInfoDao()
            ),
            new GitlabBranchCrossReferenceEnhancer(
                new TlpRelativeDatePresenterBuilder()
            ),
            ProjectManager::instance(),
            new TlpRelativeDatePresenterBuilder(),
            UserManager::instance(),
            UserHelper::instance()
        );
        $gitlab_organizer->organizeGitLabReferences($organizer);
    }

    public function externalSystemReferencePresentersCollector(ExternalSystemReferencePresentersCollector $collector): void
    {
        $collector->add(
            new ExternalSystemReferencePresenter(
                GitlabCommitReference::REFERENCE_NAME,
                dgettext('tuleap-gitlab', 'Reference to a GitLab commit'),
                dgettext('tuleap-gitlab', 'GitLab commit'),
            )
        );
        $collector->add(
            new ExternalSystemReferencePresenter(
                GitlabMergeRequestReference::REFERENCE_NAME,
                dgettext('tuleap-gitlab', 'Reference to a GitLab merge request'),
                dgettext('tuleap-gitlab', 'GitLab merge request'),
            )
        );
        $collector->add(
            new ExternalSystemReferencePresenter(
                GitlabTagReference::REFERENCE_NAME,
                dgettext('tuleap-gitlab', 'Reference to a GitLab tag'),
                dgettext('tuleap-gitlab', 'GitLab tag'),
            )
        );
        $collector->add(
            new ExternalSystemReferencePresenter(
                GitlabBranchReference::REFERENCE_NAME,
                dgettext('tuleap-gitlab', 'Reference to a GitLab branch'),
                dgettext('tuleap-gitlab', 'GitLab branch'),
            )
        );
    }

    public function semanticDoneUsedExternalServiceEvent(SemanticDoneUsedExternalServiceEvent $event): void
    {
        $project = $event->getTracker()->getProject();

        if (! $this->getGitlabIntegrationAvailabilityChecker()->isGitlabIntegrationAvailableForProject($project)) {
            return;
        }

        $event->setExternalServicesDescriptions(
            new SemanticDoneUsedExternalService(
                dgettext('tuleap-gitlab', 'GitLab integration'),
                dgettext('tuleap-gitlab', 'close artifacts'),
            )
        );
    }

    private function getGitlabIntegrationAvailabilityChecker(): GitlabIntegrationAvailabilityChecker
    {
        return new GitlabIntegrationAvailabilityChecker(
            $this->_getPluginManager(),
            $this
        );
    }

    public function additionalArtifactActionButtonsFetcher(AdditionalArtifactActionButtonsFetcher $event): void
    {
        $button_fecther = new CreateBranchButtonFetcher(
            $this->getGitlabIntegrationAvailabilityChecker(),
            new GitlabRepositoryRepresentationFactory(
                $this->getGitlabRepositoryIntegrationFactory(),
                new WebhookDao()
            ),
            new \Tuleap\Gitlab\Artifact\BranchNameCreatorFromArtifact(
                new \Cocur\Slugify\Slugify(),
                new CreateBranchPrefixDao()
            ),
            new JavascriptAsset(
                $this->getAssets(),
                "artifact-create-branch.js"
            )
        );

        $button_action = $button_fecther->getActionButton(
            $event->getArtifact(),
            $event->getUser()
        );

        if ($button_action === null) {
            return;
        }

        $event->addAction($button_action);
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/gitlab'
        );
    }
}
