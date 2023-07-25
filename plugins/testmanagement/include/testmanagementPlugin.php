<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use FastRoute\RouteCollector;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Event\Events\ImportValidateChangesetExternalField;
use Tuleap\Event\Events\ImportValidateExternalFields;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\JWT\generators\MercureJWTGeneratorBuilder;
use Tuleap\Layout\HomePage\StatisticsCollectionCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Flags\ProjectFlagsDao;
use Tuleap\Project\HeartbeatsEntryCollection;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\Service\AddMissingService;
use Tuleap\Project\Service\PluginWithService;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Project\XML\Export\ArchiveInterface;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\QuickLink\SwitchToQuickLink;
use Tuleap\TestManagement\Administration\AdminTrackersRetriever;
use Tuleap\TestManagement\Administration\FieldUsageDetector;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\Campaign\CampaignDao;
use Tuleap\TestManagement\Campaign\CampaignRetriever;
use Tuleap\TestManagement\Campaign\CloseCampaignController;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Campaign\OpenCampaignController;
use Tuleap\TestManagement\Campaign\StatusUpdater;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\Dao;
use Tuleap\TestManagement\FirstConfigCreator;
use Tuleap\TestManagement\Heartbeat\HeartbeatArtifactTrackerExcluder;
use Tuleap\TestManagement\Heartbeat\LatestHeartbeatsCollector;
use Tuleap\TestManagement\LegacyRoutingController;
use Tuleap\TestManagement\Move\TTMMovableFieldsCollector;
use Tuleap\TestManagement\REST\ResourcesInjector;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinition;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinitionChangesetValue;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinitionSubmittedValuesTransformator;
use Tuleap\TestManagement\Step\Execution\Field\StepExecution;
use Tuleap\TestManagement\TestManagementPluginInfo;
use Tuleap\TestManagement\TestmanagementTrackersConfiguration;
use Tuleap\TestManagement\TestmanagementTrackersConfigurator;
use Tuleap\TestManagement\TestmanagementTrackersCreator;
use Tuleap\TestManagement\TrackerComesFromLegacyEngineException;
use Tuleap\TestManagement\TrackerNotCreatedException;
use Tuleap\TestManagement\Type\TypeCoveredByOverrider;
use Tuleap\TestManagement\Type\TypeCoveredByPresenter;
use Tuleap\TestManagement\Workflow\PostActionChecker;
use Tuleap\TestManagement\XML\Exporter;
use Tuleap\TestManagement\XML\ImportXMLFromTracker;
use Tuleap\TestManagement\XML\StepXMLExporter;
use Tuleap\TestManagement\XML\TrackerArtifactXMLImportXMLImportFieldStrategySteps;
use Tuleap\TestManagement\XML\TrackerXMLExporterChangesetValueStepDefinitionXMLExporter;
use Tuleap\TestManagement\XML\XMLImport;
use Tuleap\Tracker\Action\CollectMovableExternalFieldEvent;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Admin\DisplayingTrackerEvent;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalArtifactActionButtonsFetcher;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonLinkPresenter;
use Tuleap\Tracker\Artifact\Event\ExternalStrategiesGetter;
use Tuleap\Tracker\Artifact\Heartbeat\ExcludeTrackersFromArtifactHeartbeats;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\SwitchToLinksCollection;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Events\ArtifactLinkTypeCanBeUnused;
use Tuleap\Tracker\Events\GetEditableTypesInProject;
use Tuleap\Tracker\Events\XMLImportArtifactLinkTypeCanBeDisabled;
use Tuleap\Tracker\FormElement\Event\ImportExternalElement;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkValueSaver;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\File\FileURLSubstitutor;
use Tuleap\Tracker\FormElement\View\Admin\DisplayAdminFormElementsWarningsEvent;
use Tuleap\Tracker\FormElement\View\Admin\FilterFormElementsThatCanBeCreatedForTracker;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\CheckPostActionsForTracker;
use Tuleap\Tracker\Rule\FirstValidValueAccordingToDependenciesRetriever;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\TrackerEventTrackersDuplicated;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\ValidValuesAccordingToTransitionsRetriever;
use Tuleap\Tracker\XML\Exporter\ChangesetValue\GetExternalExporter;
use Tuleap\Tracker\XML\Exporter\TrackerEventExportFullXML;
use Tuleap\Tracker\XML\Importer\ImportXMLProjectTrackerDone;
use Tuleap\Tracker\XML\Updater\FieldChange\FieldChangeExternalFieldXMLUpdateEvent;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';

class testmanagementPlugin extends Plugin implements PluginWithService //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const NAME              = 'testmanagement';
    public const SERVICE_SHORTNAME = 'plugin_testmanagement';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->filesystem_path = TESTMANAGEMENT_BASE_DIR;
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-testmanagement', TESTMANAGEMENT_GETTEXT_DIR);
    }

    #[\Tuleap\Plugin\ListeningToEventName('javascript_file')]
    public function javascriptFile(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        if ($this->canIncludeStepDefinitionAssets()) {
            $layout = $params['layout'];
            assert($layout instanceof \Tuleap\Layout\BaseLayout);
            $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($this->getAssets(), 'step-definition-field.js'));
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('cssfile')]
    public function cssfile(): void
    {
        if ($this->isTrackerURL()) {
            $style_css_url = $this->getAssets()->getFileURL('flamingparrot.css');

            echo '<link rel="stylesheet" type="text/css" href="' . $style_css_url . '" />';
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/testmanagement'
        );
    }

    public function getDependencies()
    {
        return ['tracker'];
    }

    public function getServiceShortname()
    {
        return self::SERVICE_SHORTNAME;
    }

    #[\Tuleap\Plugin\ListeningToEventName(Tracker_FormElementFactory::GET_CLASSNAMES)]
    public function trackerFormelementGetClassnames(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['fields'][StepDefinition::TYPE] = StepDefinition::class;
        $params['fields'][StepExecution::TYPE]  = StepExecution::class;
    }

    public function isUsedByProject(Project $project): bool
    {
        return $project->usesService($this->getServiceShortname());
    }

    /**
     * @see Event::SERVICE_CLASSNAMES
     * @param array{classnames: array<string, class-string>, project: \Project} $params
     */
    public function serviceClassnames(array &$params): void
    {
        $params['classnames'][$this->getServiceShortname()] = \Tuleap\TestManagement\Service::class;
    }

    /**
     * @see Event::SERVICE_IS_USED
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     */
    public function serviceIsUsed(array $params): void
    {
        // nothing to do for TTM
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        $service_short_name = $this->getServiceShortname();

        if (! $event->isForService($this->getServiceShortname())) {
            return;
        }

        $project = $event->getProject();
        $event->pluginSetAValue();

        $dao                          = new ArtifactLinksUsageDao();
        $covered_by_type_is_activated = ! $dao->isTypeDisabledInProject(
            (int) $project->getID(),
            TypeCoveredByPresenter::TYPE_COVERED_BY
        );

        if ($project->usesService($service_short_name)) {
            // Service is being deactivated
            $event->serviceCanBeActivated();
            return;
        }

        if ($covered_by_type_is_activated) {
            $event->serviceCanBeActivated();
            return;
        }

        $message = sprintf(
            dgettext(
                'tuleap-testmanagement',
                'Service %s cannot be activated because the artifact link type "%s" is not activated'
            ),
            $service_short_name,
            TypeCoveredByPresenter::TYPE_COVERED_BY
        );

        $event->setWarningMessage($message);
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        // nothing to do for TTM
    }

    public function addMissingService(AddMissingService $event): void
    {
        // nothing to do for TTM
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        $template = $event->getTemplateProject();

        if ($event->shouldProjectInheritFromTemplate() && $this->isUsedByProject($template)) {
            $this->allowProjectToUseType($template, $event->getJustCreatedProject());
        }
    }

    private function allowProjectToUseType(Project $template, Project $project): void
    {
        if (! $this->getArtifactLinksUsageUpdater()->isProjectAllowedToUseArtifactLinkTypes($template)) {
            $this->getArtifactLinksUsageUpdater()->forceUsageOfArtifactLinkTypes($project);
        }
    }

    /**
     * @return ArtifactLinksUsageUpdater
     */
    private function getArtifactLinksUsageUpdater()
    {
        return new ArtifactLinksUsageUpdater(new ArtifactLinksUsageDao());
    }

    #[\Tuleap\Plugin\ListeningToEventName(TypePresenterFactory::EVENT_GET_ARTIFACTLINK_TYPES)]
    public function eventGetArtifactlinkTypes(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['types'][] = new TypeCoveredByPresenter();
    }

    #[\Tuleap\Plugin\ListeningToEventName(TypePresenterFactory::EVENT_GET_TYPE_PRESENTER)]
    public function eventGetTypePresenter(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        if ($params['shortname'] === TypeCoveredByPresenter::TYPE_COVERED_BY) {
            $params['presenter'] = new TypeCoveredByPresenter();
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(ArtifactLinkValueSaver::TRACKER_EVENT_ARTIFACT_LINK_TYPE_REQUESTED)]
    public function trackerEventArtifactLinkTypeRequested(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($params['project_id']);
        if ($this->isUsedByProject($project)) {
            $to_artifact             = $params['to_artifact'];
            $new_linked_artifact_ids = explode(',', $params['submitted_value']['new_values']);

            $overrider       = new TypeCoveredByOverrider($this->getConfig(), new ArtifactLinksUsageDao());
            $overriding_type = $overrider->getOverridingType($project, $to_artifact, $new_linked_artifact_ids);

            if (! empty($overriding_type)) {
                $params['type'] = $overriding_type;
            }
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function additionalArtifactActionButtonsFetcher(AdditionalArtifactActionButtonsFetcher $event): void
    {
        $tracker = $event->getArtifact()->getTracker();
        $project = $tracker->getProject();

        $plugin_testmanagement_is_used = $project->usesService($this->getServiceShortname());

        if (! $plugin_testmanagement_is_used) {
            return;
        }

        if ($event->getUser()->isAnonymous()) {
            return;
        }

        $link_label = dgettext('tuleap-testmanagement', 'See graph of dependencies');

        $url = $this->getPluginPath() . '/?'
            . http_build_query(['group_id' => $tracker->getGroupId()])
            . '#!/graph/'
            . urlencode((string) $event->getArtifact()->getId());

        $icon = 'fa-tlp-dependencies-graph';

        $link = new AdditionalButtonLinkPresenter(
            $link_label,
            $url,
            "",
            $icon
        );

        $event->addLinkPresenter($link);
    }

    /**
     * List TestManagement trackers to duplicate
     *
     * @param array $params The project duplication parameters (source project id, tracker ids list)
     *
     */
    #[\Tuleap\Plugin\ListeningToEventName(TrackerFactory::TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED)]
    public function trackerEventProjectCreationTrackersRequired(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $config  = $this->getConfig();
        $project = ProjectManager::instance()->getProject($params['project_id']);

        $plugin_testmanagement_is_used = $project->usesService($this->getServiceShortname());
        if (! $plugin_testmanagement_is_used) {
            return;
        }

        $params['tracker_ids_list'] = array_merge(
            $params['tracker_ids_list'],
            [
                $config->getCampaignTrackerId($project),
                $config->getTestDefinitionTrackerId($project),
                $config->getTestExecutionTrackerId($project),
            ]
        );
    }

    /**
     * Configure new project's TestManagement trackers
     */
    #[\Tuleap\Plugin\ListeningToEventClass]
    public function trackerEventTrackersDuplicated(TrackerEventTrackersDuplicated $event): void
    {
        $config       = $this->getConfig();
        $from_project = ProjectManager::instance()->getProject($event->source_project_id);
        $to_project   = ProjectManager::instance()->getProject($event->project_id);

        $plugin_testmanagement_is_used = $to_project->usesService($this->getServiceShortname());
        if (! $plugin_testmanagement_is_used) {
            return;
        }

        $logger               = BackendLogger::getDefaultLogger();
        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());


        $config_creator = new FirstConfigCreator(
            $config,
            TrackerFactory::instance(),
            $this->getTrackerChecker(),
            new TestmanagementTrackersConfigurator(new TestmanagementTrackersConfiguration()),
            new TestmanagementTrackersCreator(
                TrackerXmlImport::build(new XMLImportHelper(UserManager::instance())),
                $logger
            )
        );

        try {
            $tracker_mapping = $event->tracker_mapping;
            $transaction_executor->execute(
                function () use ($to_project, $from_project, $tracker_mapping, $config_creator): void {
                    $config_creator->createConfigForProjectFromTemplate($to_project, $from_project, $tracker_mapping);
                }
            );
        } catch (TrackerComesFromLegacyEngineException | TrackerNotCreatedException $exception) {
            $logger->error('TTM configuration for project #' . $to_project->getID() . ' not duplicated.');
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(Tracker::TRACKER_USAGE)]
    public function trackerUsage(array $params): void
    {
        $tracker = $params['tracker'];
        $project = $tracker->getProject();
        if (! $project->usesService($this->getServiceShortname())) {
            return;
        }

        $tracker_id = $tracker->getId();

        static $config = null;
        if ($config === null) {
            $config = $this->getConfig();
        }

        if (
            (int) $config->getCampaignTrackerId($project) === (int) $tracker_id ||
            (int) $config->getIssueTrackerId($project) === (int) $tracker_id ||
            (int) $config->getTestDefinitionTrackerId($project) === (int) $tracker_id ||
            (int) $config->getTestExecutionTrackerId($project) === (int) $tracker_id
        ) {
            $params['result']['message']        = $this->getPluginInfo()->getPluginDescriptor()->getFullName();
            $params['result']['can_be_deleted'] = false;
        }
    }

    /**
     * @return TestManagementPluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new TestManagementPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeViaLegacyRouter'));
            $r->addRoute(['POST'], '/campaign/{campaign_id:\d+}/open', $this->getRouteHandler('routeOpenCampaignController'));
            $r->addRoute(['POST'], '/campaign/{campaign_id:\d+}/close', $this->getRouteHandler('routeCloseCampaignController'));
            $r->post('/mercure_realtime_token/{campaign_id:\d+}', $this->getRouteHandler('routeJWTController'));
        });
    }

    public function routeJWTController(): \Tuleap\TestManagement\RealTime\MercureJWTController
    {
        $campaign_retriver = new CampaignRetriever(
            Tracker_ArtifactFactory::instance(),
            new CampaignDao(),
            new KeyFactory(),
        );
        return new \Tuleap\TestManagement\RealTime\MercureJWTController(
            $campaign_retriver,
            $this->getBackendLogger(),
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            MercureJWTGeneratorBuilder::build(MercureJWTGeneratorBuilder::DEFAULTPATH),
            UserManager::instance(),
            new \Tuleap\TestManagement\ConfigConformanceValidator(new Config(new Dao(), TrackerFactory::instance())),
            new SapiEmitter(),
        );
    }

    public function routeOpenCampaignController(): OpenCampaignController
    {
        return new OpenCampaignController(
            new CampaignRetriever(
                Tracker_ArtifactFactory::instance(),
                new CampaignDao(),
                new KeyFactory()
            ),
            new StatusUpdater(
                new StatusValueRetriever(
                    Tracker_Semantic_StatusFactory::instance(),
                    $this->getFirstPossibleValueInListRetriever()
                )
            )
        );
    }

    public function routeCloseCampaignController(): CloseCampaignController
    {
        return new CloseCampaignController(
            new CampaignRetriever(
                Tracker_ArtifactFactory::instance(),
                new CampaignDao(),
                new KeyFactory()
            ),
            new StatusUpdater(
                new StatusValueRetriever(
                    Tracker_Semantic_StatusFactory::instance(),
                    $this->getFirstPossibleValueInListRetriever()
                )
            )
        );
    }

    public function routeViaLegacyRouter(): LegacyRoutingController
    {
        $config          = $this->getConfig();
        $tracker_factory = TrackerFactory::instance();
        $user_manager    = UserManager::instance();
        $event_manager   = EventManager::instance();

        $router = new Tuleap\TestManagement\Router(
            $config,
            $tracker_factory,
            $user_manager,
            $event_manager,
            $this->getArtifactLinksUsageUpdater(),
            $this->getTestmanagementFieldUsageDetector(),
            $this->getTrackerChecker(),
            new VisitRecorder(new RecentlyVisitedDao()),
            new Valid_UInt(),
            new ProjectFlagsBuilder(new ProjectFlagsDao()),
            new AdminTrackersRetriever($tracker_factory, $this->getTrackerChecker(), $config)
        );

        return new LegacyRoutingController(
            $router,
            $this->getAssets(),
            new \Tuleap\Layout\IncludeCoreAssets()
        );
    }

    private function getTrackerChecker(): TrackerChecker
    {
        return new TrackerChecker(
            TrackerFactory::instance(),
            new FrozenFieldsDao(),
            new HiddenFieldsetsDao(),
            $this->getTestmanagementFieldUsageDetector()
        );
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_PROJECT_RESOURCES)]
    public function restProjectResources(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $injector = new ResourcesInjector();
        $injector->declareProjectResource($params['resources'], $params['project']);
    }

    #[\Tuleap\Plugin\ListeningToEventName(Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_TYPES)]
    public function trackerAddSystemTypes(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['types'][] = TypeCoveredByPresenter::TYPE_COVERED_BY;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function importXMLProjectTrackerDone(ImportXMLProjectTrackerDone $event): void
    {
        $importer = new XMLImport($this->getConfig(), $this->getTrackerChecker(), new ExecutionDao());
        $importer->import(
            $event->getProject(),
            $event->getExtractionPath(),
            $event->getCreatedTrackersMapping(),
            $event->getArtifactsIdMapping(),
            $event->getChangesetIdMapping()
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function trackerGetEditableTypeInProject(GetEditableTypesInProject $event): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $project = $event->getProject();

        if ($this->isAllowed($project->getId())) {
            $event->addType(new TypeCoveredByPresenter());
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function trackerArtifactLinkCanBeUnused(ArtifactLinkTypeCanBeUnused $event): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $type    = $event->getType();
        $project = $event->getProject();

        if ($type->shortname === TypeCoveredByPresenter::TYPE_COVERED_BY) {
            $event->setTypeIsCheckedByPlugin();

            if (! $project->usesService($this->getServiceShortname())) {
                $event->setTypeIsUnusable();
            }
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function importValidateExternalFields(ImportValidateExternalFields $validate_external_fields): void
    {
        $xml        = $validate_external_fields->getXml();
        $attributes = $xml->attributes();
        if ($this->isStepField($attributes)) {
            $validator = $this->getImportXmlFromTracker();
            $validator->validateXMLImport($xml);
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function importExternalElement(ImportExternalElement $event): void
    {
        $xml        = $event->getXml();
        $attributes = $xml->attributes();
        if ($this->isStepField($attributes)) {
            $validator = $this->getImportXmlFromTracker();
            $field     = $validator->getInstanceFromXML($xml);
            if ($field !== null) {
                $event->setFormElement($field);
            }
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function importValidateChangesetExternalField(ImportValidateChangesetExternalField $validate_external_fields): void
    {
        $xml        = $validate_external_fields->getXml();
        $attributes = $xml->attributes();
        if ($attributes && isset($attributes['type']) && (string) $attributes['type'] === StepDefinition::TYPE) {
            $validator = $this->getImportXmlFromTracker();
            $validator->validateChangesetXMLImport($xml);
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getExternalStrategies(ExternalStrategiesGetter $event): void
    {
        $event->addStrategies(StepDefinition::TYPE, new TrackerArtifactXMLImportXMLImportFieldStrategySteps());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getExternalExporter(GetExternalExporter $get_external_exporter): void
    {
        $changeset_value = $get_external_exporter->getChangesetValue();
        if ($changeset_value instanceof StepDefinitionChangesetValue) {
            $get_external_exporter->addExporter(
                $this->getTrackerXMLExporterChangesetValueStepDefinitionXMLExporter()
            );
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function trackerXmlImportArtifactLinkCanBeDisabled(XMLImportArtifactLinkTypeCanBeDisabled $event): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        if ($event->getTypeName() !== TypeCoveredByPresenter::TYPE_COVERED_BY) {
            return;
        }

        $event->setTypeIsCheckedByPlugin();
        $project = $event->getProject();

        if (! $project->usesService($this->getServiceShortname())) {
            $event->setTypeIsUnusable();
        } else {
            $event->setMessage(TypeCoveredByPresenter::TYPE_COVERED_BY . " type is forced because the service testmanagement is used.");
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function filterFormElementsThatCanBeCreatedForTracker(FilterFormElementsThatCanBeCreatedForTracker $event): void
    {
        $project = $event->getTracker()->getProject();
        if (! $project->usesService($this->getServiceShortname())) {
            $event->removeByType(StepDefinition::TYPE);
            $event->removeByType(StepExecution::TYPE);
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function displayAdminFormElementsWarningsEvent(DisplayAdminFormElementsWarningsEvent $event): void
    {
        $field_usage = $this->getTestmanagementFieldUsageDetector();

        $tracker  = $event->getTracker();
        $response = $event->getResponse();
        $this->displayStepDefinitionBadUsageWarnings($field_usage, $tracker, $response);
        $this->displayStepExecutionBadUsageWarnings($field_usage, $tracker, $response);
    }

    private function getConfig(): Config
    {
        return new Config(new Dao(), TrackerFactory::instance());
    }

    private function displayStepDefinitionBadUsageWarnings(
        FieldUsageDetector $field_usage,
        Tracker $tracker,
        Response $response,
    ): void {
        if (! $field_usage->isStepDefinitionFieldUsed($tracker->getId())) {
            return;
        }

        $project = $tracker->getProject();
        if (! $project->usesService($this->getServiceShortname())) {
            $response->addFeedback(
                Feedback::WARN,
                dgettext(
                    'tuleap-testmanagement',
                    'The tracker is using a field "Step definition" that is only available in the context of Test Management. However this service is not enabled in the project: you may remove the field from the tracker.'
                )
            );

            return;
        }

        if ((int) $this->getConfig()->getTestDefinitionTrackerId($project) !== (int) $tracker->getId()) {
            $response->addFeedback(
                Feedback::WARN,
                dgettext(
                    'tuleap-testmanagement',
                    'Current tracker is not configured to be a test definition tracker in TestManagement, but is using a "Step definition" field: you may remove the field from the tracker.'
                )
            );
        }
    }

    private function displayStepExecutionBadUsageWarnings(
        FieldUsageDetector $field_usage,
        Tracker $tracker,
        Response $response,
    ): void {
        if (! $field_usage->isStepExecutionFieldUsed($tracker->getId())) {
            return;
        }

        $project = $tracker->getProject();
        if (! $project->usesService($this->getServiceShortname())) {
            $response->addFeedback(
                Feedback::WARN,
                dgettext(
                    'tuleap-testmanagement',
                    'The tracker is using a field "Step execution" that is only available in the context of Test Management. However this service is not enabled in the project: you may remove the field from the tracker.'
                )
            );

            return;
        }

        if ((int) $this->getConfig()->getTestExecutionTrackerId($project) !== (int) $tracker->getId()) {
            $response->addFeedback(
                Feedback::WARN,
                dgettext(
                    'tuleap-testmanagement',
                    'Current tracker is not configured to be a test execution tracker in TestManagement, but is using a "Step execution" field: you may remove the field from the tracker.'
                )
            );
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function trackerEventExportFullXML(TrackerEventExportFullXML $event): void
    {
        $project = $event->getProject();

        if (! $project->usesService($this->getServiceShortname())) {
            return;
        }

        $exporter    = new Exporter($this->getConfig(), new XML_RNGValidator(), new ExecutionDao());
        $xml_content = $exporter->exportToXML($project);

        if ($xml_content) {
            $this->addXMLFileIntoArchive($xml_content, $project, $event->getArchive());
        }
    }

    private function getTmpDir(): string
    {
        return rtrim(ForgeConfig::get('codendi_cache_dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * @param $params
     * @param $xml_content
     *
     */
    private function addXMLFileIntoArchive(SimpleXMLElement $xml_content, Project $project, ArchiveInterface $archive): void
    {
        $temporary_file = self::getTemporaryFileNameForProjectExport($project);
        $temporary_path = $this->getTmpDir() . "/$temporary_file";

        $dom = dom_import_simplexml($xml_content);
        if (! $dom) {
            return;
        }

        $dom_document = $dom->ownerDocument;
        if (! $dom_document) {
            return;
        }

        $dom_document->formatOutput = true;

        file_put_contents($temporary_path, $dom_document->saveXML());
        $archive->addFile('testmanagement.xml', $temporary_path);
    }

    private static function getTemporaryFileNameForProjectExport(Project $project): string
    {
        return 'export_ttm_' . (int) $project->getID() . time() . '.xml';
    }

    private function isTrackerURL(): bool
    {
        return strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL) === 0;
    }

    private function canIncludeStepDefinitionAssets(): bool
    {
        if (! $this->isTrackerURL()) {
            return false;
        }

        $request = HTTPRequest::instance();

        $artifact_id = $request->get('aid');
        if ($artifact_id) {
            $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);

            return $artifact && $this->isTrackerUsingStepDefinitionField($artifact->getTracker());
        }

        $is_submit_artifact_url = in_array($request->get('func'), ['new-artifact', 'submit-artifact']);
        if ($is_submit_artifact_url) {
            $tracker_id = $request->get('tracker');
            $tracker    = TrackerFactory::instance()->getTrackerById($tracker_id);
            if ($tracker === null) {
                throw new RuntimeException('Tracker does not exist');
            }

            return $this->isTrackerUsingStepDefinitionField($tracker);
        }

        return false;
    }

    private function isTrackerUsingStepDefinitionField(Tracker $tracker): bool
    {
        $used_step_definition_fields = Tracker_FormElementFactory::instance()->getUsedFormElementsByType(
            $tracker,
            StepDefinition::TYPE
        );

        return ! empty($used_step_definition_fields);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function statisticsCollectionCollector(StatisticsCollectionCollector $collector): void
    {
        $dao = new Dao();
        $collector->addStatistics(
            dgettext('tuleap-testmanagement', 'Tests executions'),
            $dao->countTestsExecutionsArtifacts(),
            $dao->countTestExecutionsArtifactsRegisteredBefore($collector->getTimestamp())
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function checkPostActionsForTracker(CheckPostActionsForTracker $event): void
    {
        $checker = new PostActionChecker($this->getConfig(), Tracker_FormElementFactory::instance());
        $checker->checkPostActions($event);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function serviceEnableForXmlImportRetriever(ServiceEnableForXmlImportRetriever $event): void
    {
        $event->addServiceIfPluginIsNotRestricted($this, $this->getServiceShortname());
    }

    private function getImportXmlFromTracker(): ImportXMLFromTracker
    {
        return new ImportXMLFromTracker(new XML_RNGValidator());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getSwitchToQuickLinkCollection(SwitchToLinksCollection $collection): void
    {
        $config  = $this->getConfig();
        $tracker = $collection->getArtifact()->getTracker();

        $project = $tracker->getProject();
        if (! $this->isUsedByProject($project)) {
            return;
        }

        $campaign_tracker_id = $config->getCampaignTrackerId($project);
        if ($campaign_tracker_id !== $tracker->getId()) {
            return;
        }
        $url = '/plugins/testmanagement/?' . http_build_query(
            [
                'group_id' => $project->getID(),
            ]
        ) . '#!/campaigns/' . urlencode((string) $collection->getArtifact()->getId());

        $collection->addQuickLink(
            new SwitchToQuickLink(
                dgettext('tuleap-testmanagement', 'Tests campaign artifact'),
                $collection->getArtifactUri(),
                $collection->getArtifactIconName()
            )
        );
        $collection->setIconName('fa-check-double');
        $collection->setMainUri($url);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectExcludedTrackerFromArtifactHeartbeats(ExcludeTrackersFromArtifactHeartbeats $event): void
    {
        $tracker_excluder = new HeartbeatArtifactTrackerExcluder();
        $tracker_excluder->excludeTrackers($this->getConfig(), $event);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectHeartbeatsEntries(HeartbeatsEntryCollection $collection): void
    {
        $collector = LatestHeartbeatsCollector::build();
        $collector->collect($collection);
    }

    private function getTestmanagementFieldUsageDetector(): FieldUsageDetector
    {
        return new FieldUsageDetector(
            TrackerFactory::instance(),
            Tracker_FormElementFactory::instance()
        );
    }

    private function isStepField(?SimpleXMLElement $attributes): bool
    {
        return $attributes && isset($attributes['type']) && ((string) $attributes['type'] === StepDefinition::TYPE || (string) $attributes['type'] === StepExecution::TYPE);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function displayingTrackerEvent(DisplayingTrackerEvent $event): void
    {
        $tracker = $event->getTracker();
        $project = $tracker->getProject();
        if (! $this->isUsedByProject($project)) {
            return;
        }

        $config = $this->getConfig();

        $test_exec_tracker_id = $config->getTestExecutionTrackerId($project);
        if ($test_exec_tracker_id !== $tracker->getId()) {
            return;
        }

        $GLOBALS['HTML']->addFeedback(
            Feedback::WARN,
            dgettext('tuleap-testmanagement', 'This tracker is a technical base for Test Management. Changing configuration (fields, workflow, ...) should be avoided because it may leads to inconsistencies.'),
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function fieldChangeExternalFieldXMLUpdateEvent(FieldChangeExternalFieldXMLUpdateEvent $event): void
    {
        $xml_element = $event->getFieldChangeXML();
        if ((string) $xml_element['type'] !== StepDefinition::TYPE) {
            return;
        }

        $steps = (new StepDefinitionSubmittedValuesTransformator(new FileURLSubstitutor()))
            ->transformSubmittedValuesIntoArrayOfStructuredSteps($event->getSubmittedValue(), new CreatedFileURLMapping());

        //We will rebuild all the <step> children, so we have to remove the existing ones.
        unset($xml_element->step);
        foreach ($steps as $step) {
            $this->getStepXMLExporter()->exportStepInFieldChange($step, $xml_element);
        }
    }

    private function getTrackerXMLExporterChangesetValueStepDefinitionXMLExporter(): TrackerXMLExporterChangesetValueStepDefinitionXMLExporter
    {
        return new TrackerXMLExporterChangesetValueStepDefinitionXMLExporter(
            $this->getStepXMLExporter()
        );
    }

    private function getStepXMLExporter(): StepXMLExporter
    {
        return new StepXMLExporter(
            new XML_SimpleXMLCDATAFactory()
        );
    }

    private function getFirstPossibleValueInListRetriever(): FirstPossibleValueInListRetriever
    {
        return new FirstPossibleValueInListRetriever(
            new FirstValidValueAccordingToDependenciesRetriever(
                Tracker_FormElementFactory::instance()
            ),
            new ValidValuesAccordingToTransitionsRetriever(
                Workflow_Transition_ConditionFactory::build()
            )
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectMovableExternalFieldEvent(CollectMovableExternalFieldEvent $event): void
    {
        TTMMovableFieldsCollector::collectMovableFields($event);
    }
}
