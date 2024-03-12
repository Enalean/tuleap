<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Log\LoggerInterface;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Mapper\ValinorMapperBuilderFactory;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Plugin\ListeningToEventName;
use Tuleap\Plugin\MandatoryAsyncWorkerSetupPluginInstallRequirement;
use Tuleap\Project\Admin\History\GetHistoryKeyLabel;
use Tuleap\Queue\WorkerAvailability;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
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
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsQueuer;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\MailSender;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationTaskCollectorEvent;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactForwardLinksRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactLinksByChangesetCache;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksDao;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksToNewChangesetsConverter;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\Link\ArtifactReverseLinksUpdater;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\PermissionsFunctionsWrapper;
use Tuleap\Tracker\REST\Artifact\ArtifactRestUpdateConditionsChecker;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\Artifact\PUTHandler;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsBuilder;
use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\PermissionsExporter;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentationBuilder;
use Tuleap\Tracker\REST\WorkflowRestBuilder;
use Tuleap\Tracker\Webhook\ArtifactPayloadBuilder;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowMenuItem;
use Tuleap\Tracker\Workflow\WorkflowMenuItemCollection;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;
use Tuleap\TrackerFunctions\Administration\ActivateFunctionController;
use Tuleap\TrackerFunctions\Administration\ActiveTrackerRetrieverMiddleware;
use Tuleap\TrackerFunctions\Administration\AdministrationController;
use Tuleap\TrackerFunctions\Administration\AdministrationCSRFTokenProvider;
use Tuleap\TrackerFunctions\Administration\CheckTrackerCSRFMiddleware;
use Tuleap\TrackerFunctions\Administration\CustomCodeExecutionHistorySaver;
use Tuleap\TrackerFunctions\Administration\MaxSize10Mb;
use Tuleap\TrackerFunctions\Administration\FunctionDao;
use Tuleap\TrackerFunctions\Administration\RejectNonTrackerAdministratorMiddleware;
use Tuleap\TrackerFunctions\Administration\RemoveFunctionController;
use Tuleap\TrackerFunctions\Administration\UpdateFunctionController;
use Tuleap\TrackerFunctions\CustomCodeExecutionTask;
use Tuleap\TrackerFunctions\Logs\LogLinePresenterBuilder;
use Tuleap\TrackerFunctions\Logs\FunctionLogDao;
use Tuleap\TrackerFunctions\Notification\BuildMessagesForAdmins;
use Tuleap\TrackerFunctions\Notification\RetrieveTrackerAdminRecipients;
use Tuleap\TrackerFunctions\Notification\SendMessagesForAdmins;
use Tuleap\TrackerFunctions\Notification\SendNotificationToTrackerAdministrator;
use Tuleap\TrackerFunctions\WASM\CallWASMFunction;
use Tuleap\TrackerFunctions\WASM\ExecuteWASMResponse;
use Tuleap\TrackerFunctions\WASM\FindWASMFunctionPath;
use Tuleap\TrackerFunctions\WASM\ProcessWASMResponse;
use Tuleap\WebAssembly\FFIWASMCaller;
use Tuleap\WebAssembly\WasmtimeCacheConfigurationBuilder;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class tracker_functionsPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-tracker_functions', __DIR__ . '/../site-content');
    }

    public function getInstallRequirements(): array
    {
        return [new MandatoryAsyncWorkerSetupPluginInstallRequirement(new WorkerAvailability())];
    }

    public function getPluginInfo(): Tuleap\TrackerFunctions\Plugin\PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\TrackerFunctions\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['tracker'];
    }

    #[ListeningToEventClass]
    public function collectPostCreationTask(PostCreationTaskCollectorEvent $event): void
    {
        $mapper                        = ValinorMapperBuilderFactory::mapperBuilder()->allowPermissiveTypes()->mapper();
        $logger                        = $event->getLogger();
        $user_manager                  = UserManager::instance();
        $form_element_factory          = Tracker_FormElementFactory::instance();
        $transition_retriever          = new TransitionRetriever(
            new StateFactory(
                TransitionFactory::instance(),
                new SimpleWorkflowDao()
            ),
            new TransitionExtractor()
        );
        $frozen_fields_detector        = new FrozenFieldDetector(
            $transition_retriever,
            new FrozenFieldsRetriever(
                new FrozenFieldsDao(),
                Tracker_FormElementFactory::instance()
            )
        );
        $ugroup_manager                = new UGroupManager();
        $permissions_functions_wrapper = new PermissionsFunctionsWrapper();

        $event->addAsyncTask(new CustomCodeExecutionTask(
            $logger,
            new ArtifactPayloadBuilder(
                new ChangesetRepresentationBuilder(
                    $user_manager,
                    $form_element_factory,
                    new CommentRepresentationBuilder(
                        CommonMarkInterpreter::build(Codendi_HTMLPurifier::instance())
                    ),
                    new PermissionChecker(new CachingTrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentUGroupEnabledDao()))),
                ),
                new Tracker_REST_TrackerRestBuilder(
                    $form_element_factory,
                    new FormElementRepresentationsBuilder(
                        $form_element_factory,
                        new PermissionsExporter($frozen_fields_detector),
                        new HiddenFieldsetChecker(
                            new HiddenFieldsetsDetector(
                                $transition_retriever,
                                new HiddenFieldsetsRetriever(
                                    new HiddenFieldsetsDao(),
                                    $form_element_factory,
                                ),
                                $form_element_factory,
                            ),
                            new FieldsExtractor(),
                        ),
                        new PermissionsForGroupsBuilder(
                            $ugroup_manager,
                            $frozen_fields_detector,
                            $permissions_functions_wrapper,
                        ),
                        new TypePresenterFactory(
                            new TypeDao(),
                            new ArtifactLinksUsageDao(),
                        ),
                    ),
                    new PermissionsRepresentationBuilder(
                        $ugroup_manager,
                        $permissions_functions_wrapper,
                    ),
                    new WorkflowRestBuilder(),
                ),
            ),
            new FindWASMFunctionPath(),
            new CallWASMFunction(
                new FFIWASMCaller(new WasmtimeCacheConfigurationBuilder(), $mapper, Prometheus::instance(), 'tracker_functions_plugin'),
                new ProcessWASMResponse($logger, $mapper),
                $logger,
            ),
            new ExecuteWASMResponse($logger, $this->getPutHandler($logger)),
            new FunctionLogDao(Tracker_ArtifactFactory::instance()),
            new FunctionDao(),
            new SendNotificationToTrackerAdministrator(
                new RetrieveTrackerAdminRecipients($ugroup_manager, $ugroup_manager),
                new BuildMessagesForAdmins(TemplateRendererFactory::build()),
                new SendMessagesForAdmins(new MailSender()),
                $logger,
            ),
            $this,
        ));
    }

    #[ListeningToEventClass]
    public function workflowMenuItemCollection(WorkflowMenuItemCollection $collection): void
    {
        if (! $this->isAllowed((int) $collection->tracker->getProject()->getID())) {
            return;
        }

        $collection->addItem(
            new WorkflowMenuItem(
                '/tracker_functions/' . urlencode((string) $collection->tracker->getId()) . '/admin',
                dgettext('tuleap-tracker_functions', 'Tuleap Functions'),
                'tracker-functions',
            ),
        );
    }

    #[ListeningToEventClass]
    public function getHistoryKeyLabel(GetHistoryKeyLabel $event): void
    {
        $label = CustomCodeExecutionHistorySaver::getLabelFromKey($event->getKey());
        if ($label) {
            $event->setLabel($label);
        }
    }

    #[ListeningToEventName('fill_project_history_sub_events')]
    public function fillProjectHistorySubEvents(array $params): void
    {
        CustomCodeExecutionHistorySaver::fillProjectHistorySubEvents($params);
    }

    #[ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->get('/tracker_functions/{id:\d+}/admin', $this->getRouteHandler('routeTrackerAdministration'));
        $event->getRouteCollector()->post('/tracker_functions/{id:\d+}/admin', $this->getRouteHandler('routePostTrackerAdministration'));
        $event->getRouteCollector()->post('/tracker_functions/{id:\d+}/admin/remove', $this->getRouteHandler('routeRemoveTrackerAdministration'));
        $event->getRouteCollector()->post('/tracker_functions/{id:\d+}/admin/activate', $this->getRouteHandler('routeActivateTrackerAdministration'));
    }

    public function routeActivateTrackerAdministration(): DispatchableWithRequest
    {
        $history_saver = new CustomCodeExecutionHistorySaver(new ProjectHistoryDao());

        return new ActivateFunctionController(
            new RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new FeedbackSerializer(new FeedbackDao()),
            ),
            new FunctionDao(),
            $history_saver,
            $history_saver,
            new SapiEmitter(),
            new ActiveTrackerRetrieverMiddleware(TrackerFactory::instance()),
            new RejectNonTrackerAdministratorMiddleware(UserManager::instance()),
            new CheckTrackerCSRFMiddleware(new AdministrationCSRFTokenProvider()),
        );
    }

    public function routeTrackerAdministration(): DispatchableWithRequest
    {
        return new AdministrationController(
            TrackerFactory::instance(),
            new TrackerManager(),
            TemplateRendererFactory::build(),
            new AdministrationCSRFTokenProvider(),
            new FindWASMFunctionPath(),
            new FunctionDao(),
            new FunctionLogDao(Tracker_ArtifactFactory::instance()),
            new LogLinePresenterBuilder(new TlpRelativeDatePresenterBuilder()),
            EventManager::instance(),
        );
    }

    public function routePostTrackerAdministration(): DispatchableWithRequest
    {
        return new UpdateFunctionController(
            new RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new FeedbackSerializer(new FeedbackDao()),
            ),
            BackendLogger::getDefaultLogger(),
            new FindWASMFunctionPath(),
            new CustomCodeExecutionHistorySaver(new ProjectHistoryDao()),
            new FunctionDao(),
            new MaxSize10Mb(),
            new SapiEmitter(),
            new ActiveTrackerRetrieverMiddleware(TrackerFactory::instance()),
            new RejectNonTrackerAdministratorMiddleware(UserManager::instance()),
            new CheckTrackerCSRFMiddleware(new AdministrationCSRFTokenProvider()),
        );
    }

    public function routeRemoveTrackerAdministration(): DispatchableWithRequest
    {
        return new RemoveFunctionController(
            new RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new FeedbackSerializer(new FeedbackDao()),
            ),
            new CustomCodeExecutionHistorySaver(new ProjectHistoryDao()),
            new FindWASMFunctionPath(),
            new FunctionDao(),
            new FunctionLogDao(Tracker_ArtifactFactory::instance()),
            new SapiEmitter(),
            new ActiveTrackerRetrieverMiddleware(TrackerFactory::instance()),
            new RejectNonTrackerAdministratorMiddleware(UserManager::instance()),
            new CheckTrackerCSRFMiddleware(new AdministrationCSRFTokenProvider()),
        );
    }

    private function getPutHandler(LoggerInterface $logger): PUTHandler
    {
        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

        $usage_dao           = new ArtifactLinksUsageDao();
        $formelement_factory = Tracker_FormElementFactory::instance();
        $fields_retriever    = new FieldsToBeSavedInSpecificOrderRetriever($formelement_factory);
        $event_manager       = EventManager::instance();

        $artifact_factory  = Tracker_ArtifactFactory::instance();
        $changeset_creator = new NewChangesetCreator(
            new Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                $formelement_factory,
                new ArtifactLinkValidator(
                    $artifact_factory,
                    new TypePresenterFactory(new TypeDao(), $usage_dao),
                    $usage_dao,
                    $event_manager,
                ),
                new WorkflowUpdateChecker(
                    new FrozenFieldDetector(
                        new TransitionRetriever(
                            new StateFactory(TransitionFactory::instance(), new SimpleWorkflowDao()),
                            new TransitionExtractor()
                        ),
                        FrozenFieldsRetriever::instance(),
                    )
                )
            ),
            $fields_retriever,
            $event_manager,
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($formelement_factory),
            $transaction_executor,
            ArtifactChangesetSaver::build(),
            new ParentLinkAction($artifact_factory),
            new AfterNewChangesetHandler($artifact_factory, $fields_retriever),
            ActionsQueuer::build($logger),
            new ChangesetValueSaver(),
            WorkflowFactory::instance(),
            new CommentCreator(
                new Tracker_Artifact_Changeset_CommentDao(),
                ReferenceManager::instance(),
                new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao()),
                new TextValueValidator(),
            ),
            new ChangesetCommentIndexer(
                new ItemToIndexQueueEventBased($event_manager),
                $event_manager,
                new Tracker_Artifact_Changeset_CommentDao(),
            ),
        );

        $fields_data_builder       = new FieldsDataBuilder(
            $formelement_factory,
            new NewArtifactLinkChangesetValueBuilder(
                new ArtifactForwardLinksRetriever(
                    new ArtifactLinksByChangesetCache(),
                    new ChangesetValueArtifactLinkDao(),
                    $artifact_factory
                ),
            ),
            new NewArtifactLinkInitialChangesetValueBuilder()
        );
        $update_conditions_checker = new ArtifactRestUpdateConditionsChecker();
        $artifact_factory          = Tracker_ArtifactFactory::instance();

        $reverse_link_retriever = new ReverseLinksRetriever(
            new ReverseLinksDao(),
            $artifact_factory
        );

        return new PUTHandler(
            $fields_data_builder,
            new ArtifactReverseLinksUpdater(
                $reverse_link_retriever,
                new ReverseLinksToNewChangesetsConverter(
                    $formelement_factory,
                    $artifact_factory
                ),
                $changeset_creator
            ),
            $update_conditions_checker,
        );
    }
}
