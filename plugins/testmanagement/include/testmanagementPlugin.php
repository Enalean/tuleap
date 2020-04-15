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
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfoCollector;
use Tuleap\AgileDashboard\REST\v1\AdditionalPanesForMilestoneEvent;
use Tuleap\AgileDashboard\REST\v1\PaneInfoRepresentation;
use Tuleap\Event\Events\ImportValidateChangesetExternalField;
use Tuleap\Event\Events\ImportValidateExternalFields;
use Tuleap\layout\HomePage\StatisticsCollectionCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\XML\Export\ArchiveInterface;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\TestManagement\Administration\StepFieldUsageDetector;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\AgileDashboardPaneInfo;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\Dao;
use Tuleap\TestManagement\FirstConfigCreator;
use Tuleap\TestManagement\LegacyRoutingController;
use Tuleap\TestManagement\Nature\NatureCoveredByOverrider;
use Tuleap\TestManagement\Nature\NatureCoveredByPresenter;
use Tuleap\TestManagement\REST\ResourcesInjector;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinition;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinitionChangesetValue;
use Tuleap\TestManagement\Step\Execution\Field\StepExecution;
use Tuleap\TestManagement\TestManagementPluginInfo;
use Tuleap\TestManagement\TrackerComesFromLegacyEngineException;
use Tuleap\TestManagement\TrackerNotCreatedException;
use Tuleap\TestManagement\XML\Exporter;
use Tuleap\TestManagement\XML\ImportXMLFromTracker;
use Tuleap\TestManagement\XML\TrackerArtifactXMLImportXMLImportFieldStrategySteps;
use Tuleap\TestManagement\XML\TrackerXMLExporterChangesetValueStepDefinitionXMLExporter;
use Tuleap\TestManagement\XML\XMLImport;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalArtifactActionButtonsFetcher;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonLinkPresenter;
use Tuleap\Tracker\Artifact\Event\ExternalStrategiesGetter;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Events\ArtifactLinkTypeCanBeUnused;
use Tuleap\Tracker\Events\GetEditableTypesInProject;
use Tuleap\Tracker\Events\XMLImportArtifactLinkTypeCanBeDisabled;
use Tuleap\Tracker\FormElement\Event\ImportExternalElement;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\FormElement\View\Admin\DisplayAdminFormElementsWarningsEvent;
use Tuleap\Tracker\FormElement\View\Admin\FilterFormElementsThatCanBeCreatedForTracker;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\CheckPostActionsForTracker;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\XML\Exporter\ChangesetValue\GetExternalExporter;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';

class testmanagementPlugin extends Plugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->filesystem_path = TESTMANAGEMENT_BASE_DIR;
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-testmanagement', TESTMANAGEMENT_GETTEXT_DIR);
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(Event::REST_PROJECT_RESOURCES);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(Event::REGISTER_PROJECT_CREATION);
        $this->addHook(NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES);
        $this->addHook(NaturePresenterFactory::EVENT_GET_NATURE_PRESENTER);
        $this->addHook(ProjectServiceBeforeActivation::NAME);
        $this->addHook(ImportValidateExternalFields::NAME);
        $this->addHook(ServiceEnableForXmlImportRetriever::NAME);
        $this->addHook(ImportExternalElement::NAME);
        $this->addHook(ImportValidateChangesetExternalField::NAME);
        $this->addHook(ExternalStrategiesGetter::NAME);
        $this->addHook(GetExternalExporter::NAME);

        $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);

        if (defined('AGILEDASHBOARD_BASE_URL')) {
            $this->addHook(PaneInfoCollector::NAME);
            $this->addHook(AdditionalPanesForMilestoneEvent::NAME);
        }

        if (defined('TRACKER_BASE_URL')) {
            $this->addHook('javascript_file');
            $this->addHook('cssfile');
            $this->addHook(AdditionalArtifactActionButtonsFetcher::NAME);
            $this->addHook(TRACKER_EVENT_ARTIFACT_LINK_NATURE_REQUESTED);
            $this->addHook(TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED);
            $this->addHook(TRACKER_EVENT_TRACKERS_DUPLICATED);
            $this->addHook(Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES);

            $this->addHook(Event::IMPORT_XML_PROJECT_TRACKER_DONE);
            $this->addHook(GetEditableTypesInProject::NAME);
            $this->addHook(ArtifactLinkTypeCanBeUnused::NAME);
            $this->addHook(XMLImportArtifactLinkTypeCanBeDisabled::NAME);
            $this->addHook(Tracker_FormElementFactory::GET_CLASSNAMES);
            $this->addHook(FilterFormElementsThatCanBeCreatedForTracker::NAME);
            $this->addHook(DisplayAdminFormElementsWarningsEvent::NAME);
            $this->addHook(TRACKER_EVENT_EXPORT_FULL_XML);
            $this->addHook(TRACKER_USAGE);
            $this->addHook(StatisticsCollectionCollector::NAME);
            $this->addHook(CheckPostActionsForTracker::NAME);
        }

        return parent::getHooksAndCallbacks();
    }

    public function javascript_file(): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        if ($this->canIncludeStepDefinitionAssets()) {
            echo $this->getAssets()->getHTMLSnippet('step-definition-field.js');
        }
    }

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
            __DIR__ . '/../../../src/www/assets/testmanagement/',
            '/assets/testmanagement'
        );
    }

    public function getDependencies()
    {
        return ['tracker'];
    }

    public function getServiceShortname()
    {
        return 'plugin_testmanagement';
    }

    /**
     * @see Tracker_FormElementFactory::GET_CLASSNAMES
     *
     */
    public function tracker_formelement_get_classnames(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['fields'][StepDefinition::TYPE] = StepDefinition::class;
        $params['fields'][StepExecution::TYPE]  = StepExecution::class;
    }

    public function isUsedByProject(Project $project): bool
    {
        return $project->usesService($this->getServiceShortname());
    }

    public function service_classnames(array &$params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['classnames'][$this->getServiceShortname()] = \Tuleap\TestManagement\Service::class;
    }

    public function register_project_creation(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $project_manager = ProjectManager::instance();
        $template        = $project_manager->getProject($params['template_id']);
        $project         = $project_manager->getProject($params['group_id']);

        if ($params['project_creation_data']->projectShouldInheritFromTemplate() && $this->isUsedByProject($template)) {
            $this->allowProjectToUseNature($template, $project);
        }
    }

    private function allowProjectToUseNature(Project $template, Project $project): void
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

    public function event_get_artifactlink_natures(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['natures'][] = new NatureCoveredByPresenter();
    }

    public function event_get_nature_presenter(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        if ($params['shortname'] === NatureCoveredByPresenter::NATURE_COVERED_BY) {
            $params['presenter'] = new NatureCoveredByPresenter();
        }
    }

    public function tracker_event_artifact_link_nature_requested(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($params['project_id']);
        if ($this->isUsedByProject($project)) {
            $to_artifact             = $params['to_artifact'];
            $new_linked_artifact_ids = explode(',', $params['submitted_value']['new_values']);

            $overrider        = new NatureCoveredByOverrider($this->getConfig(), new ArtifactLinksUsageDao());
            $overridingNature = $overrider->getOverridingNature($project, $to_artifact, $new_linked_artifact_ids);

            if (! empty($overridingNature)) {
                $params['nature'] = $overridingNature;
            }
        }
    }

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
    public function tracker_event_project_creation_trackers_required(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $config  = $this->getConfig();
        $project = ProjectManager::instance()->getProject($params['project_id']);

        $plugin_testmanagement_is_used = $project->usesService($this->getServiceShortname());
        if (! $plugin_testmanagement_is_used) {
            return;
        }

        $params['tracker_ids_list'] = array_merge(
            $params['tracker_ids_list'],
            array(
                $config->getCampaignTrackerId($project),
                $config->getTestDefinitionTrackerId($project),
                $config->getTestExecutionTrackerId($project)
            )
        );
    }

    /**
     * Configure new project's TestManagement trackers
     *
     * @param mixed array $params The duplication params (tracker_mapping array, field_mapping array)
     *
     */
    public function tracker_event_trackers_duplicated(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $config       = $this->getConfig();
        $from_project = ProjectManager::instance()->getProject($params['source_project_id']);
        $to_project   = ProjectManager::instance()->getProject($params['group_id']);

        $plugin_testmanagement_is_used = $to_project->usesService($this->getServiceShortname());
        if (! $plugin_testmanagement_is_used) {
            return;
        }

        $logger = new BackendLogger();

        $config_creator = new FirstConfigCreator(
            $config,
            TrackerFactory::instance(),
            TrackerXmlImport::build(new XMLImportHelper(UserManager::instance())),
            $this->getTrackerChecker(),
            $logger
        );

        try {
            $config_creator->createConfigForProjectFromTemplate($to_project, $from_project, $params['tracker_mapping']);
        } catch (TrackerComesFromLegacyEngineException | TrackerNotCreatedException $exception) {
            $logger->error('TTM configuration for project #' . $to_project->getID() . ' not duplicated.');
        }
    }

    /**
     * @see TRACKER_USAGE
     *
     */
    public function trackerUsage(array $params): void
    {
        $tracker    = $params['tracker'];
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
     * Add tab in Agile Dashboard Planning view to redirect to TestManagement
     *
     */
    public function agiledashboardEventAdditionalPanesOnMilestone(PaneInfoCollector $collector): void
    {
        $milestone = $collector->getMilestone();
        $project   = $milestone->getProject();
        if ($project->usesService($this->getServiceShortname())) {
            $collector->addPane(new Tuleap\TestManagement\AgileDashboardPaneInfo($milestone));
        }
    }

    public function additionalPanesForMilestoneEvent(AdditionalPanesForMilestoneEvent $event): void
    {
        $milestone = $event->getMilestone();
        $project   = $milestone->getProject();
        if ($project->usesService($this->getServiceShortname())) {
            $pane = new AgileDashboardPaneInfo($milestone);

            $representation = new PaneInfoRepresentation();
            $representation->build($pane);

            $event->add($representation);
        }
    }

    /**
     * @return TestManagementPluginInfo
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new TestManagementPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeViaLegacyRouter'));
        });
    }

    public function routeViaLegacyRouter(): LegacyRoutingController
    {
        $config               = $this->getConfig();
        $tracker_factory      = TrackerFactory::instance();
        $project_manager      = ProjectManager::instance();
        $user_manager         = UserManager::instance();
        $event_manager        = EventManager::instance();
        $form_element_factory = Tracker_FormElementFactory::instance();

        $step_field_usage_detector = new StepFieldUsageDetector(
            $tracker_factory,
            $form_element_factory
        );

        $router = new Tuleap\TestManagement\Router(
            $config,
            $tracker_factory,
            $user_manager,
            $event_manager,
            $this->getArtifactLinksUsageUpdater(),
            $step_field_usage_detector,
            $this->getTrackerChecker(),
            new VisitRecorder(new RecentlyVisitedDao()),
            new Valid_UInt()
        );

        return new LegacyRoutingController(
            $router,
            $this->getAssets(),
            new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/core',
                '/assets/core'
            )
        );
    }

    private function getTrackerChecker(): TrackerChecker
    {
        return new TrackerChecker(
            TrackerFactory::instance(),
            new FrozenFieldsDao(),
            new HiddenFieldsetsDao()
        );
    }

    /**
     * @see REST_RESOURCES
     *
     */
    public function rest_resources(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see REST_PROJECT_RESOURCES
     *
     */
    public function rest_project_resources(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $injector = new ResourcesInjector();
        $injector->declareProjectResource($params['resources'], $params['project']);
    }

    public function tracker_add_system_natures(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['natures'][] = NatureCoveredByPresenter::NATURE_COVERED_BY;
    }

    public function import_xml_project_tracker_done(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $importer = new XMLImport($this->getConfig(), $this->getTrackerChecker(), new ExecutionDao());
        $importer->import(
            $params['project'],
            $params['extraction_path'],
            $params['mapping'],
            $params['artifact_id_mapping'],
            $params['changeset_id_mapping']
        );
    }

    public function tracker_get_editable_type_in_project(GetEditableTypesInProject $event): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $project = $event->getProject();

        if ($this->isAllowed($project->getId())) {
            $event->addType(new NatureCoveredByPresenter());
        }
    }

    public function tracker_artifact_link_can_be_unused(ArtifactLinkTypeCanBeUnused $event): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $type    = $event->getType();
        $project = $event->getProject();

        if ($type->shortname === NatureCoveredByPresenter::NATURE_COVERED_BY) {
            $event->setTypeIsCheckedByPlugin();

            if (! $project->usesService($this->getServiceShortname())) {
                $event->setTypeIsUnusable();
            }
        }
    }

    public function importValidateExternalFields(ImportValidateExternalFields $validate_external_fields): void
    {
        $xml = $validate_external_fields->getXml();
        $attributes = $xml->attributes();
        if ($attributes && isset($attributes['type']) && (string) $attributes['type'] === 'ttmstepdef') {
            $validator = $this->getImportXmlFromTracker();
            $validator->validateXMLImport($xml);
        }
    }

    public function importExternalElement(ImportExternalElement $event): void
    {
        $xml = $event->getXml();
        $attributes = $xml->attributes();
        if ($attributes && isset($attributes['type']) && (string) $attributes['type'] === 'ttmstepdef') {
            $validator = $this->getImportXmlFromTracker();
            $event->setFormElement(
                $validator->getInstanceFromXML($xml, $event->getProject(), $event->getFeedbackCollector())
            );
        }
    }
    public function importValidateChangesetExternalField(ImportValidateChangesetExternalField $validate_external_fields): void
    {
        $xml = $validate_external_fields->getXml();
        $attributes = $xml->attributes();
        if ($attributes && isset($attributes['type']) && (string) $attributes['type'] === StepDefinition::TYPE) {
            $validator = $this->getImportXmlFromTracker();
            $validator->validateChangesetXMLImport($xml);
        }
    }

    public function getExternalStrategies(ExternalStrategiesGetter $event): void
    {
        $event->addStrategies(StepDefinition::TYPE, new TrackerArtifactXMLImportXMLImportFieldStrategySteps());
    }

    public function getExternalExporter(GetExternalExporter $get_external_exporter): void
    {
        $changeset_value = $get_external_exporter->getChangesetValue();
        if ($changeset_value instanceof StepDefinitionChangesetValue) {
            $get_external_exporter->addExporter(new TrackerXMLExporterChangesetValueStepDefinitionXMLExporter(new XML_SimpleXMLCDATAFactory()));
        }
    }

    public function project_service_before_activation(ProjectServiceBeforeActivation $event): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $service_short_name = $event->getServiceShortname();
        $project            = $event->getProject();

        if ($service_short_name !== $this->getServiceShortname()) {
            return;
        }

        $event->pluginSetAValue();

        $dao                          = new ArtifactLinksUsageDao();
        $covered_by_type_is_activated = ! $dao->isTypeDisabledInProject(
            $project->getID(),
            NatureCoveredByPresenter::NATURE_COVERED_BY
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
            NatureCoveredByPresenter::NATURE_COVERED_BY
        );

        $event->setWarningMessage($message);
    }

    public function tracker_xml_import_artifact_link_can_be_disabled(XMLImportArtifactLinkTypeCanBeDisabled $event): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        if ($event->getTypeName() !== NatureCoveredByPresenter::NATURE_COVERED_BY) {
            return;
        }

        $event->setTypeIsCheckedByPlugin();
        $project = $event->getProject();

        if (! $project->usesService($this->getServiceShortname())) {
            $event->setTypeIsUnusable();
        } else {
            $event->setMessage(NatureCoveredByPresenter::NATURE_COVERED_BY . " type is forced because the service testmanagement is used.");
        }
    }

    public function filterFormElementsThatCanBeCreatedForTracker(FilterFormElementsThatCanBeCreatedForTracker $event): void
    {
        $project = $event->getTracker()->getProject();
        if (! $project->usesService($this->getServiceShortname())) {
            $event->removeByType(StepDefinition::TYPE);
            $event->removeByType(StepExecution::TYPE);
        }
    }

    public function displayAdminFormElementsWarningsEvent(DisplayAdminFormElementsWarningsEvent $event): void
    {
        $step_field_usage = new StepFieldUsageDetector(
            TrackerFactory::instance(),
            Tracker_FormElementFactory::instance()
        );

        $tracker  = $event->getTracker();
        $response = $event->getResponse();
        $this->displayStepDefinitionBadUsageWarnings($step_field_usage, $tracker, $response);
        $this->displayStepExecutionBadUsageWarnings($step_field_usage, $tracker, $response);
    }

    private function getConfig(): Config
    {
        return new Config(new Dao(), TrackerFactory::instance());
    }

    private function displayStepDefinitionBadUsageWarnings(
        StepFieldUsageDetector $step_field_usage,
        Tracker $tracker,
        Response $response
    ): void {
        if (! $step_field_usage->isStepDefinitionFieldUsed($tracker->getId())) {
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
        StepFieldUsageDetector $step_field_usage,
        Tracker $tracker,
        Response $response
    ): void {
        if (! $step_field_usage->isStepExecutionFieldUsed($tracker->getId())) {
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

    public function tracker_event_export_full_xml(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $project_id = $params['group_id'];
        $project    = ProjectManager::instance()->getProject($project_id);

        if (! $project || ! $project->usesService($this->getServiceShortname())) {
            return;
        }

        $exporter    = new Exporter($this->getConfig(), new XML_RNGValidator(), new ExecutionDao());
        $xml_content = $exporter->exportToXML($project);

        if ($xml_content) {
            $this->addXMLFileIntoArchive($xml_content, $project, $params['archive']);
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
        $temporaray_file = 'export_ttm_' . $project->getID() . time() . '.xml';
        $temporary_path  = $this->getTmpDir() . "/$temporaray_file";

        $dom = dom_import_simplexml($xml_content)->ownerDocument;
        $dom->formatOutput = true;

        file_put_contents($temporary_path, $dom->saveXML());
        $archive->addFile('testmanagement.xml', $temporary_path);
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

    public function statisticsCollectionCollector(StatisticsCollectionCollector $collector): void
    {
        $dao = new Dao();
        $collector->addStatistics(
            dgettext('tuleap-testmanagement', 'Tests executions'),
            $dao->countTestsExecutionsArtifacts(),
            $dao->countTestExecutionsArtifactsRegisteredBefore($collector->getTimestamp())
        );
    }

    public function checkPostActionsForTracker(CheckPostActionsForTracker $event): void
    {
        $frozen_fields_post_actions    = $event->getPostActions()->getFrozenFieldsPostActions();
        $hidden_fieldsets_post_actions = $event->getPostActions()->getHiddenFieldsetsPostActions();

        if (count($frozen_fields_post_actions) > 0 || count($hidden_fieldsets_post_actions) > 0) {
            $tracker = $event->getTracker();
            $config  = $this->getConfig();

            if (
                $tracker->getId() == $config->getTestExecutionTrackerId($tracker->getProject()) ||
                $tracker->getId() == $config->getTestDefinitionTrackerId($tracker->getProject())
            ) {
                $message = dgettext(
                    'tuleap-testmanagement',
                    'The post actions cannot be saved because this tracker is used in TestManagement and "frozen fields" or "hidden fieldsets" actions are defined.'
                );
                $event->setPostActionsNonEligible();
                $event->setErrorMessage($message);
            }
        }
    }

    public function serviceEnableForXmlImportRetriever(ServiceEnableForXmlImportRetriever $event): void
    {
        $event->addServiceIfPluginIsNotRestricted($this, $this->getServiceShortname());
    }

    private function getImportXmlFromTracker(): ImportXMLFromTracker
    {
        return new ImportXMLFromTracker(new XML_RNGValidator());
    }
}
