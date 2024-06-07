<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\AgileDashboard\AgileDashboard\Milestone\Backlog\RecentlyVisitedTopBacklogDao;
use Tuleap\AgileDashboard\AgileDashboard\Milestone\Backlog\VisitRetriever;
use Tuleap\AgileDashboard\AgileDashboardLegacyController;
use Tuleap\AgileDashboard\Artifact\AdditionalArtifactActionBuilder;
use Tuleap\AgileDashboard\Artifact\EventRedirectAfterArtifactCreationOrUpdateHandler;
use Tuleap\AgileDashboard\Artifact\HomeServiceRedirectionExtractor;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\Artifact\RedirectParameterInjector;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\MilestoneCrumbBuilder;
use Tuleap\AgileDashboard\CreateBacklogController;
use Tuleap\AgileDashboard\CSRFSynchronizerTokenProvider;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ConfigurationUpdater;
use Tuleap\AgileDashboard\ExplicitBacklog\CopiedArtifact\AddCopiedArtifactsToTopBacklog;
use Tuleap\AgileDashboard\ExplicitBacklog\CreateTrackerFromXMLChecker;
use Tuleap\AgileDashboard\ExplicitBacklog\DirectArtifactLinkCleaner;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ProjectNotUsingExplicitBacklogException;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedCriterionOptionsProvider;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedReportCriterionChecker;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedReportCriterionMatchingIdsRetriever;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCalculator;
use Tuleap\AgileDashboard\FormElement\Burnup\ProjectsCountModeDao;
use Tuleap\AgileDashboard\FormElement\BurnupCacheDao;
use Tuleap\AgileDashboard\FormElement\BurnupCacheDateRetriever;
use Tuleap\AgileDashboard\FormElement\BurnupCalculator;
use Tuleap\AgileDashboard\FormElement\BurnupDataDAO;
use Tuleap\AgileDashboard\FormElement\BurnupFieldRetriever;
use Tuleap\AgileDashboard\FormElement\MessageFetcher;
use Tuleap\AgileDashboard\FormElement\SystemEvent\SystemEvent_BURNUP_DAILY;
use Tuleap\AgileDashboard\FormElement\SystemEvent\SystemEvent_BURNUP_GENERATE;
use Tuleap\AgileDashboard\Milestone\Sidebar\MilestonesInSidebarDao;
use Tuleap\AgileDashboard\Move\AgileDashboardMovableFieldsCollector;
use Tuleap\AgileDashboard\SplitModalPresenter;
use Tuleap\Cardwall\Cardwall\CardwallUseStandardJavascriptEvent;
use Tuleap\AgileDashboard\Masschange\AdditionalMasschangeActionProcessor;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\AgileDashboard\Planning\PlanningTrackerBacklogChecker;
use Tuleap\AgileDashboard\Planning\XML\ProvideCurrentUserForXMLImport;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\AgileDashboard\Semantic\XML\SemanticsExporter;
use Tuleap\AgileDashboard\Tracker\TrackerHierarchyUpdateChecker;
use Tuleap\AgileDashboard\Tracker\TrackersCannotBeLinkedWithHierarchyException;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklog;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionFactory;
use Tuleap\AgileDashboard\Workflow\PostAction\Update\AddToTopBacklogValue;
use Tuleap\AgileDashboard\Workflow\PostAction\Update\Internal\AddToTopBacklogValueRepository;
use Tuleap\AgileDashboard\Workflow\PostAction\Update\Internal\AddToTopBacklogValueUpdater;
use Tuleap\AgileDashboard\Workflow\REST\v1\AddToTopBacklogJsonParser;
use Tuleap\AgileDashboard\Workflow\REST\v1\AddToTopBacklogRepresentation;
use Tuleap\Cardwall\Agiledashboard\CardwallPaneInfo;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\Kanban\Service\KanbanService;
use Tuleap\Layout\AfterStartProjectContainer;
use Tuleap\Layout\BeforeStartProjectHeader;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Layout\HomePage\StatisticsCollectionCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Plugin\ListeningToEventName;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupDisplayEvent;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware;
use Tuleap\Project\CachedProjectAccessChecker;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Event\ProjectXMLImportFromArchiveTemplatePreChecksEvent;
use Tuleap\Project\Event\ProjectXMLImportPreChecks;
use Tuleap\Project\Event\ProjectXMLImportPreChecksEvent;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\Registration\Template\Upload\ArchiveWithoutDataCheckerErrorCollection;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\Routing\CheckProjectCSRFMiddleware;
use Tuleap\Project\Routing\ProjectByNameRetrieverMiddleware;
use Tuleap\Project\Service\AddMissingService;
use Tuleap\Project\Service\PluginWithService;
use Tuleap\Project\Service\ServiceClassnamesCollector;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Project\XML\Import\ImportNotValidException;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\QuickLink\SwitchToQuickLink;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Statistics\CSV\StatisticsServiceUsage;
use Tuleap\Tracker\Action\AfterArtifactCopiedEvent;
use Tuleap\Tracker\Action\CollectMovableExternalFieldEvent;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalArtifactActionButtonsFetcher;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Artifact\Event\ArtifactDeleted;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\SwitchToLinksCollection;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Artifact\RedirectAfterArtifactCreationOrUpdateEvent;
use Tuleap\Tracker\Artifact\Renderer\BuildArtifactFormActionEvent;
use Tuleap\Tracker\Config\GeneralSettingsEvent;
use Tuleap\Tracker\CreateTrackerFromXMLEvent;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraImporterExternalPluginsEvent;
use Tuleap\Tracker\Events\CollectTrackerDependantServices;
use Tuleap\Tracker\FormElement\Event\MessageFetcherAdditionalWarnings;
use Tuleap\Tracker\Hierarchy\TrackerHierarchyUpdateEvent;
use Tuleap\Tracker\Masschange\TrackerMasschangeGetExternalActionsEvent;
use Tuleap\Tracker\Masschange\TrackerMasschangeProcessExternalActionsEvent;
use Tuleap\Tracker\Report\Event\TrackerReportProcessAdditionalQuery;
use Tuleap\Tracker\REST\v1\Event\GetExternalPostActionJsonParserEvent;
use Tuleap\Tracker\REST\v1\Event\PostActionVisitExternalActionsEvent;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\CheckPostActionsForTracker;
use Tuleap\Tracker\Semantic\Progress\Events\GetSemanticProgressUsageEvent;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDao;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneUsedExternalService;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneUsedExternalServiceEvent;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueChecker;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\TrackerEventTrackersDuplicated;
use Tuleap\Tracker\Workflow\Event\GetWorkflowExternalPostActionsValuesForUpdate;
use Tuleap\Tracker\Workflow\Event\GetWorkflowExternalPostActionsValueUpdater;
use Tuleap\Tracker\Workflow\Event\TransitionDeletionEvent;
use Tuleap\Tracker\Workflow\Event\WorkflowDeletionEvent;
use Tuleap\Tracker\Workflow\PostAction\ExternalPostActionSaveObjectEvent;
use Tuleap\Tracker\Workflow\PostAction\GetExternalPostActionPluginsEvent;
use Tuleap\Tracker\Workflow\PostAction\GetExternalSubFactoriesEvent;
use Tuleap\Tracker\Workflow\PostAction\GetExternalSubFactoryByNameEvent;
use Tuleap\Tracker\Workflow\PostAction\GetPostActionShortNameFromXmlTagNameEvent;
use Tuleap\Tracker\XML\Exporter\TrackerEventExportFullXML;
use Tuleap\Tracker\XML\Exporter\TrackerEventExportStructureXML;
use Tuleap\User\History\HistoryEntryCollection;
use Tuleap\User\History\HistoryRetriever;
use Tuleap\User\ProvideCurrentUser;

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../../cardwall/include/cardwallPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../kanban/vendor/autoload.php';
require_once 'constants.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class AgileDashboardPlugin extends Plugin implements PluginWithConfigKeys, PluginWithService
{
    public const PLUGIN_NAME      = 'agiledashboard';
    public const PLUGIN_SHORTNAME = 'plugin_agiledashboard';

    public const AGILEDASHBOARD_EVENT_REST_RESOURCES = 'agiledashboard_event_rest_resources';

    public const USER_PREF_DISPLAY_SPLIT_MODAL = 'should_display_agiledashboard_split_modal';

    /** @var AgileDashboard_SequenceIdManager */
    private $sequence_id_manager;

    /**
     * @var AddToTopBacklogPostActionFactory
     */
    private $add_to_top_backlog_post_action_factory;

    /**
     * Plugin constructor
     */
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindTextDomain('tuleap-agiledashboard', AGILEDASHBOARD_BASE_DIR . '/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        // Do not load the plugin if tracker is not installed & active
        if (defined('TRACKER_BASE_URL')) {
            $this->addHook('cssfile', 'cssfile');
            $this->addHook(trackerPlugin::TRACKER_EVENT_INCLUDE_CSS_FILE);
            $this->addHook(BuildArtifactFormActionEvent::NAME);
            $this->addHook(RedirectAfterArtifactCreationOrUpdateEvent::NAME);
            $this->addHook(Tracker_SemanticManager::TRACKER_EVENT_MANAGE_SEMANTICS, 'tracker_event_manage_semantics');
            $this->addHook(Tracker_SemanticFactory::TRACKER_EVENT_SEMANTIC_FROM_XML, 'tracker_event_semantic_from_xml');
            $this->addHook(Tracker_SemanticManager::TRACKER_EVENT_GET_SEMANTICS_NAMES, 'tracker_event_get_semantics_names');
            $this->addHook(Tracker_SemanticFactory::TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS);
            $this->addHook(Tracker_Report::TRACKER_EVENT_REPORT_DISPLAY_ADDITIONAL_CRITERIA);
            $this->addHook(Tracker_Report::TRACKER_EVENT_REPORT_SAVE_ADDITIONAL_CRITERIA);
            $this->addHook(Tracker_Report::TRACKER_EVENT_REPORT_LOAD_ADDITIONAL_CRITERIA);
            $this->addHook(Tracker_FormElement_Field_Priority::TRACKER_EVENT_FIELD_AUGMENT_DATA_FOR_REPORT);
            $this->addHook(Tracker::TRACKER_USAGE);
            $this->addHook(RegisterProjectCreationEvent::NAME);
            $this->addHook(TrackerFactory::TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED);
            $this->addHook(Event::IMPORT_XML_PROJECT_CARDWALL_DONE);
            $this->addHook(Event::REST_RESOURCES);
            $this->addHook(Event::REST_PROJECT_ADDITIONAL_INFORMATIONS);
            $this->addHook(Event::REST_PROJECT_RESOURCES);
            $this->addHook(Event::GET_PROJECTID_FROM_URL);
            $this->addHook(Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION);
            $this->addHook(PermissionPerGroupDisplayEvent::NAME);
            $this->addHook(ArtifactCreated::NAME);
            $this->addHook(ArtifactUpdated::NAME);
            $this->addHook(Tracker_FormElementFactory::GET_CLASSNAMES);
            $this->addHook(Event::GET_SYSTEM_EVENT_CLASS);
            $this->addHook('codendi_daily_start');
            $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
            $this->addHook(MessageFetcherAdditionalWarnings::NAME);
            $this->addHook(PermissionPerGroupPaneCollector::NAME);
            $this->addHook(ArtifactDeleted::NAME);
            $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);
            $this->addHook(SwitchToLinksCollection::NAME);
            $this->addHook(StatisticsCollectionCollector::NAME);
            $this->addHook(ProjectStatusUpdate::NAME);
            $this->addHook(AdditionalArtifactActionButtonsFetcher::NAME);
            $this->addHook(TrackerMasschangeGetExternalActionsEvent::NAME);
            $this->addHook(TrackerMasschangeProcessExternalActionsEvent::NAME);
            $this->addHook(TrackerReportProcessAdditionalQuery::NAME);
            $this->addHook(GetExternalSubFactoriesEvent::NAME);
            $this->addHook(WorkflowDeletionEvent::NAME);
            $this->addHook(TransitionDeletionEvent::NAME);
            $this->addHook(PostActionVisitExternalActionsEvent::NAME);
            $this->addHook(GetExternalPostActionJsonParserEvent::NAME);
            $this->addHook(GetWorkflowExternalPostActionsValueUpdater::NAME);
            $this->addHook(GetExternalSubFactoryByNameEvent::NAME);
            $this->addHook(ExternalPostActionSaveObjectEvent::NAME);
            $this->addHook(GetPostActionShortNameFromXmlTagNameEvent::NAME);
            $this->addHook(CreateTrackerFromXMLEvent::NAME);
            $this->addHook(GetExternalPostActionPluginsEvent::NAME);
            $this->addHook(CheckPostActionsForTracker::NAME);
            $this->addHook(GetWorkflowExternalPostActionsValuesForUpdate::NAME);
            $this->addHook(JiraImporterExternalPluginsEvent::NAME);
            $this->addHook(GetSemanticProgressUsageEvent::NAME);
            $this->addHook(SemanticDoneUsedExternalServiceEvent::NAME);
            $this->addHook(TrackerHierarchyUpdateEvent::NAME);
            $this->addHook(AfterArtifactCopiedEvent::NAME);
        }

        return parent::getHooksAndCallbacks();
    }

    public function tracker_formelement_get_classnames($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['dynamic']['burnup'] = \Tuleap\AgileDashboard\FormElement\Burnup::class;
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return ['kanban', 'tracker', 'cardwall'];
    }

    public function getServiceShortname(): string
    {
        return self::PLUGIN_SHORTNAME;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function serviceClassnamesCollector(ServiceClassnamesCollector $event): void
    {
        $event->addService($this->getServiceShortname(), \Tuleap\AgileDashboard\AgileDashboardService::class);
    }

    /**
     * @see Event::SERVICE_IS_USED
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     */
    public function serviceIsUsed(array $params): void
    {
        if (! isset($params['shortname']) || ! isset($params['is_used'])) {
            return;
        }

        $service_short_name = $params['shortname'];
        $service_is_used    = $params['is_used'];

        if ($service_short_name !== $this->getServiceShortname() || ! $service_is_used) {
            return;
        }

        $explicit_backlog_configuration_updater = $this->getExplicitBacklogConfigurationUpdater();

        $project = ProjectManager::instance()->getProject((int) $params['group_id']);
        $user    = UserManager::instance()->getCurrentUser();

        $explicit_backlog_configuration_updater->activateExplicitBacklogManagement(
            $project,
            $user
        );
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        // nothing to do for Agile Dashboard
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        // nothing to do for Agile Dashboard
    }

    public function addMissingService(AddMissingService $event): void
    {
        // nothing to do for Agile Dashboard
    }

    #[ListeningToEventClass]
    public function trackerEventExportStructureXML(TrackerEventExportStructureXML $event): void
    {
        $project_id  = (int) $event->getProject()->getID();
        $xml_content = $event->getXmlElement();

        $plannings = PlanningFactory::build()->getOrderedPlanningsWithBacklogTracker(
            $event->getUser(),
            $project_id
        );

        AgileDashboard_XMLExporter::build()->export(
            $event->getProject(),
            $xml_content,
            $plannings
        );
    }

    #[ListeningToEventClass]
    public function trackerEventExportFullXML(TrackerEventExportFullXML $event): void
    {
        $project_id  = (int) $event->getProject()->getID();
        $xml_content = $event->getXmlElement();

        $plannings = PlanningFactory::build()->getOrderedPlanningsWithBacklogTracker(
            $event->getUser(),
            $project_id
        );

        AgileDashboard_XMLExporter::build()->exportFull(
            $event->getProject(),
            $xml_content,
            $plannings
        );
    }

    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        if ($event->shouldProjectInheritFromTemplate()) {
            $this->getConfigurationManager()->duplicate(
                (int) $event->getJustCreatedProject()->getID(),
                (int) $event->getTemplateProject()->getID(),
            );

            $explicit_backlog_configuration_updater = $this->getExplicitBacklogConfigurationUpdater();
            $explicit_backlog_configuration_updater->activateExplicitBacklogManagement(
                $event->getJustCreatedProject(),
                UserManager::instance()->getCurrentUser()
            );

            (new ProjectsCountModeDao())->inheritBurnupCountMode(
                (int) $event->getTemplateProject()->getID(),
                (int) $event->getJustCreatedProject()->getID(),
            );
        }
    }

    /**
     * @return AgileDashboard_ConfigurationManager
     */
    private function getConfigurationManager()
    {
        return new AgileDashboard_ConfigurationManager(
            new AgileDashboard_ConfigurationDao(),
            EventManager::instance(),
            new MilestonesInSidebarDao(),
            new MilestonesInSidebarDao(),
        );
    }

    #[ListeningToEventClass]
    public function historyEntryCollection(HistoryEntryCollection $collection): void
    {
        $visit_retriever = new VisitRetriever(
            new RecentlyVisitedTopBacklogDao(),
            \ProjectManager::instance(),
            new CachedProjectAccessChecker(
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    EventManager::instance(),
                ),
            ),
        );
        $visit_retriever->getVisitHistory($collection, HistoryRetriever::MAX_LENGTH_HISTORY);
    }

    #[ListeningToEventName(Event::USER_HISTORY_CLEAR)]
    public function userHistoryClear(array $params): void
    {
        $user = $params['user'];
        assert($user instanceof PFUser);

        $visit_cleaner = new RecentlyVisitedTopBacklogDao();
        $visit_cleaner->deleteVisitByUserId((int) $user->getId());
    }

    public function cardwall_event_get_swimline_tracker($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $planning_factory = $this->getPlanningFactory();
        if ($planning = $planning_factory->getPlanningByPlanningTracker($params['tracker'])) {
            $params['backlog_trackers'] = $planning->getBacklogTrackers();
        }
    }

    /**
     * @see Tracker_Report::TRACKER_EVENT_REPORT_DISPLAY_ADDITIONAL_CRITERIA
     */
    public function tracker_event_report_display_additional_criteria($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $backlog_tracker = $params['tracker'];
        if (! $backlog_tracker) {
            return;
        }

        $planning_factory     = $this->getPlanningFactory();
        $user                 = $this->getCurrentUser();
        $provider             = new AgileDashboard_Milestone_MilestoneReportCriterionProvider(
            new AgileDashboard_Milestone_SelectedMilestoneProvider(
                $params['additional_criteria'],
                $this->getMilestoneFactory(),
                $user,
                $backlog_tracker->getProject()
            ),
            new AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider(
                new AgileDashboard_Planning_NearestPlanningTrackerProvider($planning_factory),
                $this->getMilestoneDao(),
                Tracker_HierarchyFactory::instance(),
                $planning_factory,
                TrackerFactory::instance(),
                Tracker_ArtifactFactory::instance()
            ),
            new UnplannedCriterionOptionsProvider(new ExplicitBacklogDao()),
            new UnplannedReportCriterionChecker($params['additional_criteria'])
        );
        $additional_criterion = $provider->getCriterion($backlog_tracker, $user);

        if (! $additional_criterion) {
            return;
        }

        $params['array_of_html_criteria'][] = $additional_criterion;
    }

    public function trackerReportProcessAdditionalQuery(TrackerReportProcessAdditionalQuery $event)
    {
        $backlog_tracker = $event->getTracker();

        $nearest_planning_tracker = (new AgileDashboard_Planning_NearestPlanningTrackerProvider($this->getPlanningFactory()))->getNearestPlanningTracker(
            $backlog_tracker,
            Tracker_HierarchyFactory::instance(),
        );
        if ($nearest_planning_tracker === null) {
            //Old criterion is set but tracker is no more used in AD configuration
            return;
        }

        $user    = $event->getUser();
        $project = $backlog_tracker->getProject();

        $unplanned_report_criterion_checker = new UnplannedReportCriterionChecker($event->getAdditionalCriteria());
        if ($unplanned_report_criterion_checker->isUnplannedValueSelected()) {
            $retriever = new UnplannedReportCriterionMatchingIdsRetriever(
                new ExplicitBacklogDao(),
                new ArtifactsInExplicitBacklogDao(),
                new PlannedArtifactDao(),
                $this->getArtifactFactory()
            );

            try {
                $event->addResult($retriever->getMatchingIds($backlog_tracker, $user));
                $event->setSearchIsPerformed();
            } catch (ProjectNotUsingExplicitBacklogException $exception) {
                //Do nothing
            } finally {
                return;
            }
        }

        $milestone_provider = new AgileDashboard_Milestone_SelectedMilestoneProvider($event->getAdditionalCriteria(), $this->getMilestoneFactory(), $user, $project);
        $milestone          = $milestone_provider->getMilestone();

        if ($milestone) {
            $provider = new AgileDashboard_BacklogItem_SubBacklogItemProvider(
                new Tracker_ArtifactDao(),
                $this->getBacklogFactory(),
                $this->getBacklogItemCollectionFactory(
                    $this->getMilestoneFactory(),
                    new AgileDashboard_Milestone_Backlog_BacklogItemBuilder()
                ),
                $this->getPlanningFactory(),
                new ExplicitBacklogDao(),
                new ArtifactsInExplicitBacklogDao()
            );

            $event->addResult($provider->getMatchingIds($milestone, $backlog_tracker, $user));
            $event->setSearchIsPerformed();
        }
    }

    /**
     * @see Tracker_Report::TRACKER_EVENT_REPORT_SAVE_ADDITIONAL_CRITERIA
     */
    public function tracker_event_report_save_additional_criteria($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $dao     = new MilestoneReportCriterionDao();
        $project = $params['report']->getTracker()->getProject();
        $user    = $this->getCurrentUser();

        $unplanned_report_criterion_checker = new UnplannedReportCriterionChecker($params['additional_criteria']);
        if ($unplanned_report_criterion_checker->isUnplannedValueSelected()) {
            $dao->save($params['report']->getId(), AgileDashboard_Milestone_MilestoneReportCriterionProvider::UNPLANNED);
            return;
        }

        $milestone_provider = new AgileDashboard_Milestone_SelectedMilestoneProvider($params['additional_criteria'], $this->getMilestoneFactory(), $user, $project);
        if ($milestone_provider->getMilestone()) {
            $dao->save($params['report']->getId(), $milestone_provider->getMilestoneId());
        } else {
            $dao->delete($params['report']->getId());
        }
    }

    /**
     * @see Tracker_Report::TRACKER_EVENT_REPORT_LOAD_ADDITIONAL_CRITERIA
     */
    public function tracker_event_report_load_additional_criteria($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $dao        = new MilestoneReportCriterionDao();
        $report_id  = $params['report']->getId();
        $field_name = AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME;

        $row = $dao->searchByReportId($report_id)->getRow();
        if ($row) {
            $params['additional_criteria_values'][$field_name]['value'] = $row['milestone_id'];
        }
    }

    public function tracker_event_include_css_file($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = HTTPRequest::instance();
        if ($request->get('pane') === CardwallPaneInfo::IDENTIFIER || $this->isHomepageURL($request)) {
            $params['include_tracker_css_file'] = true;
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function generalSettingsEvent(GeneralSettingsEvent $event): void
    {
        $hierarchyChecker = new AgileDashboard_HierarchyChecker(
            $this->getPlanningFactory(),
            $this->getTrackerFactory()
        );

        $event->cannot_configure_instantiate_for_new_projects = $hierarchyChecker->isPartOfScrumHierarchy($event->tracker);
    }

    public function tracker_event_project_creation_trackers_required($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $hierarchyChecker           = new AgileDashboard_HierarchyChecker(
            $this->getPlanningFactory(),
            $this->getTrackerFactory()
        );
        $params['tracker_ids_list'] = array_merge(
            $params['tracker_ids_list'],
            $hierarchyChecker->getADTrackerIdsByProjectId((int) $params['project_id'])
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function trackerEventTrackersDuplicated(TrackerEventTrackersDuplicated $event): void
    {
        PlanningFactory::build()->duplicatePlannings(
            $event->project_id,
            $event->tracker_mapping,
            $event->ugroups_mapping,
        );
    }

    public function redirectAfterArtifactCreationOrUpdateEvent(RedirectAfterArtifactCreationOrUpdateEvent $event): void
    {
        $planning_factory = PlanningFactory::build();
        $params_extractor = new AgileDashboard_PaneRedirectionExtractor();

        $processor = new EventRedirectAfterArtifactCreationOrUpdateHandler(
            $params_extractor,
            new HomeServiceRedirectionExtractor(),
            new Planning_ArtifactLinker($this->getArtifactFactory(), $planning_factory),
            $planning_factory,
            new RedirectParameterInjector(
                $params_extractor,
                Tracker_ArtifactFactory::instance(),
                $GLOBALS['Response'],
                $this->getTemplateRenderer()
            ),
            $this->getMilestoneFactory(),
            $this->getMilestonePaneFactory()
        );

        $processor->process($event->getRequest(), $event->getRedirect(), $event->getArtifact());
    }

    public function tracker_usage($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker = $params['tracker'];
        assert($tracker instanceof Tracker);
        $tracker_id = $tracker->getId();

        $is_used_in_planning = PlanningFactory::build()->isTrackerIdUsedInAPlanning($tracker_id);
        $is_used_in_backlog  = PlanningFactory::build()->isTrackerUsedInBacklog($tracker_id);

        if ($is_used_in_planning || $is_used_in_backlog) {
            $result['can_be_deleted'] = false;
            $result['message']        = dgettext('tuleap-agiledashboard', 'Backlog');
            $params['result']         = $result;
        }
    }

    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(MilestonesInSidebarDao::class);
    }

    public function buildArtifactFormActionEvent(BuildArtifactFormActionEvent $event): void
    {
        $request  = $event->getRequest();
        $redirect = $event->getRedirect();

        $home_service_redirection_extractor = new HomeServiceRedirectionExtractor();
        if ($home_service_redirection_extractor->mustRedirectToAgiledashboardHomepage($request)) {
            $redirect->query_parameters['agiledashboard'] = $request->get('agiledashboard');
            return;
        }

        $injector = new RedirectParameterInjector(
            new AgileDashboard_PaneRedirectionExtractor(),
            Tracker_ArtifactFactory::instance(),
            $GLOBALS['Response'],
            $this->getTemplateRenderer()
        );

        $injector->injectAndInformUserAboutBacklogItemWillBeLinked($request, $redirect);
    }

    /**
     * @return AgileDashboardPluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new AgileDashboardPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssfile($params)
    {
        if ($this->isAnAgiledashboardRequest()) {
            $css_file_url = $this->getIncludeAssets()->getFileURL('style-fp.css');
            echo '<link rel="stylesheet" type="text/css" href="' . $css_file_url . '" />';
        }
    }

    public function permissionPerGroupDisplayEvent(PermissionPerGroupDisplayEvent $event): void
    {
        $event->addJavascript(
            new \Tuleap\Layout\JavascriptViteAsset(
                new \Tuleap\Layout\IncludeViteAssets(
                    __DIR__ . '/../scripts/permissions-per-group/frontend-assets',
                    '/assets/agiledashboard/permissions-per-group'
                ),
                'src/index.ts'
            )
        );
    }

    private function isAnAgiledashboardRequest()
    {
        return $this->currentRequestIsForPlugin();
    }

    private function isHomepageURL(HTTPRequest $request)
    {
        return (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath() . '/?group_id=') === 0
            && $request->get('action') === false
        );
    }

    /**
     * Builds a new PlanningFactory instance.
     *
     * @return PlanningFactory
     */
    protected function getPlanningFactory()
    {
        return PlanningFactory::build();
    }

    /**
     * Builds a new Planning_MilestoneFactory instance.
     * @return Planning_MilestoneFactory
     */
    public function getMilestoneFactory()
    {
        return Planning_MilestoneFactory::build();
    }

    private function getArtifactFactory()
    {
        return Tracker_ArtifactFactory::instance();
    }

    private function getHierarchyFactory()
    {
        return Tracker_HierarchyFactory::instance();
    }

    private function getBacklogFactory()
    {
        return new AgileDashboard_Milestone_Backlog_BacklogFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->getArtifactFactory(),
            PlanningFactory::build(),
        );
    }

    /**
     * @see Tracker_SemanticManager::TRACKER_EVENT_MANAGE_SEMANTICS
     */
    public function tracker_event_manage_semantics($parameters) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker   = $parameters['tracker'];
        $semantics = $parameters['semantics'];
        \assert($semantics instanceof Tracker_SemanticCollection);

        $semantics->add(AgileDashBoard_Semantic_InitialEffort::load($tracker));
    }

    /**
     * @see Tracker_SemanticFactory::TRACKER_EVENT_SEMANTIC_FROM_XML
     */
    public function tracker_event_semantic_from_xml(&$parameters) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker           = $parameters['tracker'];
        $xml               = $parameters['xml'];
        $full_semantic_xml = $parameters['full_semantic_xml'];
        $xmlMapping        = $parameters['xml_mapping'];
        $type              = $parameters['type'];

        if ($type == AgileDashBoard_Semantic_InitialEffort::NAME) {
            $parameters['semantic'] = $this->getSemanticInitialEffortFactory()->getInstanceFromXML(
                $xml,
                $full_semantic_xml,
                $xmlMapping,
                $tracker,
                []
            );
        }
    }

    private function getSemanticDoneFactory(): SemanticDoneFactory
    {
        return new SemanticDoneFactory(new SemanticDoneDao(), new SemanticDoneValueChecker());
    }

    /**
     * @see Tracker_SemanticFactory::TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS
     */
    public function tracker_event_get_semantic_duplicators($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['duplicators'][] = $this->getSemanticInitialEffortFactory();
    }

    protected function getSemanticInitialEffortFactory()
    {
        return AgileDashboard_Semantic_InitialEffortFactory::instance();
    }

    /**
     * Augment $params['semantics'] with names of AgileDashboard semantics
     *
     * @see Tracker_SemanticManager::TRACKER_EVENT_GET_SEMANTICS_NAMES
     */
    public function tracker_event_get_semantics_names(&$params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['semantics'][] = AgileDashBoard_Semantic_InitialEffort::NAME;
    }

    /**
     *
     * @param array $params
     *  Expected key/ values:
     *      project_id  int             The ID of the project for the import
     *      xml_content SimpleXmlObject A string of valid xml
     *      mapping     array           An array of mappings between xml tracker IDs and their true IDs
     *
     */
    public function import_xml_project_cardwall_done($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = new HTTPRequest();
        $request->set('action', 'import');
        $request->set('xml_content', $params['xml_content']);
        $request->set('mapping', $params['mapping']);
        $request->set('artifact_id_mapping', $params['artifact_id_mapping']);
        $request->set('logger', $params['logger']);
        $request->set('project_id', $params['project_id']);
        $request->set('group_id', $params['project_id']);

        $this->routeLegacyController(new ProvideCurrentUserForXMLImport(UserManager::instance()))
            ->process($request, $GLOBALS['Response'], []);
    }

    #[ListeningToEventClass]
    public function statisticsServiceUsage(StatisticsServiceUsage $event): void
    {
        $dao = new AgileDashboard_Dao();
        $event->csv_exporter->buildDatas($dao->getProjectsWithADActivated(), 'Backlog activated');
    }

    /**
     * @see REST_PROJECT_ADDITIONAL_INFORMATIONS
     */
    public function rest_project_additional_informations($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $root_planning = $this->getPlanningFactory()->getRootPlanning($this->getCurrentUser(), $params['project']->getGroupId());
        if (! $root_planning) {
            return;
        }

        $planning_representation = new \Tuleap\AgileDashboard\REST\v1\PlanningRepresentation();
        $planning_representation->build($root_planning);

        $params['informations'][$this->getName()]['root_planning'] = $planning_representation;
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new AgileDashboard_REST_ResourcesInjector();
        $injector->populate($params['restler']);

        EventManager::instance()->processEvent(
            self::AGILEDASHBOARD_EVENT_REST_RESOURCES,
            $params
        );
    }

    /**
     * @see Event::REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new AgileDashboard_REST_ResourcesInjector();
        $injector->declareProjectPlanningResource($params['resources'], $params['project']);
    }

    private function getPlanningIdFromParameters($params)
    {
        if ($params['milestone_id'] == 0) {
            $planning = $this->getPlanningFactory()->getRootPlanning(
                $params['user'],
                $params['group_id']
            );

            return $planning->getId();
        }

        $artifact  = $this->getArtifactFactory()->getArtifactById($params['milestone_id']);
        $milestone = $this->getMilestoneFactory()->getMilestoneFromArtifact($artifact);

        return $milestone->getPlanningId();
    }

    /** @see Event::GET_PROJECTID_FROM_URL */
    public function get_projectid_from_url($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($params['url'], '/plugins/agiledashboard/') === 0) {
            $params['project_id'] = $params['request']->get('group_id');
        }
    }

    /**
     * @see Tracker_FormElement_Field_Priority::TRACKER_EVENT_FIELD_AUGMENT_DATA_FOR_REPORT
     */
    public function tracker_event_field_augment_data_for_report($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $this->isFieldPriority($params['field'])) {
            return;
        }

        $params['result'] = $this->getFieldPriorityAugmenter()->getAugmentedDataForFieldPriority(
            $this->getCurrentUser(),
            $params['field']->getTracker()->getProject(),
            $params['additional_criteria'],
            $params['artifact_id']
        );
    }

    private function getFieldPriorityAugmenter()
    {
        return new AgileDashboard_FieldPriorityAugmenter(
            $this->getSequenceIdManager(),
            $this->getMilestoneFactory()
        );
    }

    private function isFieldPriority(Tracker_FormElement_Field $field)
    {
        return $field instanceof Tracker_FormElement_Field_Priority;
    }

    private function getSequenceIdManager()
    {
        if (! $this->sequence_id_manager) {
            $this->sequence_id_manager = new AgileDashboard_SequenceIdManager(
                $this->getBacklogFactory(),
                $this->getBacklogItemCollectionFactory(
                    $this->getMilestoneFactory(),
                    new AgileDashboard_Milestone_Backlog_BacklogItemBuilder()
                )
            );
        }

        return $this->sequence_id_manager;
    }

    #[ListeningToEventClass]
    public function cardwallUseStandardJavascriptEvent(CardwallUseStandardJavascriptEvent $event): void
    {
        $request              = HTTPRequest::instance();
        $pane_info_identifier = new AgileDashboard_PaneInfoIdentifier();
        if ($pane_info_identifier->isPaneAPlanningV2($request->get('pane'))) {
            $event->use_standard_javascript = false;
        }
    }

    /** @see Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION */
    public function tracker_artifact_editrenderer_add_view_in_collection(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $user       = $params['user'];
        $request    = $params['request'];
        $artifact   = $params['artifact'];
        $collection = $params['collection'];

        $milestone = $this->getMilestoneFactory()->getBareMilestoneByArtifact($user, $artifact);
        if ($milestone) {
            $collection->add(new Tuleap\AgileDashboard\Milestone\ArtifactView($milestone, $request, $user));
        }
    }

    private function getTrackerFactory(): TrackerFactory
    {
        return TrackerFactory::instance();
    }

    private function getCurrentUser(): PFUser
    {
        return UserManager::instance()->getCurrentUser();
    }

    private function getPlanningPermissionsManager()
    {
        return new PlanningPermissionsManager();
    }

    #[ListeningToEventClass]
    public function testmanagementGetMilestone(\Tuleap\TestManagement\Event\GetMilestone $event): void
    {
        $milestone_factory = $this->getMilestoneFactory();
        $milestone         = $milestone_factory->getBareMilestoneByArtifactId($event->getUser(), $event->getMilestoneId());
        $event->setMilestone($milestone);
    }

    #[ListeningToEventClass]
    public function testmanagementGetItemsFromMilestone(\Tuleap\TestManagement\Event\GetItemsFromMilestone $event): void
    {
        $milestone_factory               = $this->getMilestoneFactory();
        $backlog_factory                 = $this->getBacklogFactory();
        $backlog_item_collection_factory = $this->getBacklogItemCollectionFactory(
            $milestone_factory,
            new AgileDashboard_Milestone_Backlog_BacklogItemBuilder()
        );

        $user         = $event->getUser();
        $milestone_id = $event->getMilestoneId();
        $milestone    = $milestone_factory->getValidatedBareMilestoneByArtifactId($user, $milestone_id);
        if (! $milestone) {
            return;
        }
        $backlog       = $backlog_factory->getSelfBacklog($milestone);
        $backlog_items = $backlog_item_collection_factory->getOpenAndClosedCollection($user, $milestone, $backlog, '');
        $items_ids     = [];

        foreach ($backlog_items as $item) {
            $items_ids[] = $item->id();

            if ($item->hasChildren()) {
                $this->parseChildrenElements($item, $user, $items_ids);
            }
        }

        $event->setItemsIds(array_unique($items_ids));
    }

    private function parseChildrenElements(AgileDashboard_Milestone_Backlog_BacklogItem $item, PFUser $user, array &$item_ids)
    {
        $tracker_artifact_dao = new Tracker_ArtifactDao();

        $children = $tracker_artifact_dao->getChildren($item->getArtifact()->getId())
            ->instanciateWith([$this->getArtifactFactory(), 'getInstanceFromRow']);

        foreach ($children as $child) {
            if ($child->userCanView($user)) {
                $item_ids[] = $child->getId();
            }
        }
    }

    public function trackerArtifactCreated(ArtifactCreated $event)
    {
        $artifact = $event->getArtifact();
        $cleaner  = new DirectArtifactLinkCleaner(
            $this->getMilestoneFactory(),
            new ExplicitBacklogDao(),
            new ArtifactsInExplicitBacklogDao()
        );

        $cleaner->cleanDirectlyMadeArtifactLinks($artifact, $this->getCurrentUser());
    }

    public function trackerArtifactUpdated(ArtifactUpdated $event)
    {
        $artifact = $event->getArtifact();
        $cleaner  = new DirectArtifactLinkCleaner(
            $this->getMilestoneFactory(),
            new ExplicitBacklogDao(),
            new ArtifactsInExplicitBacklogDao()
        );

        $cleaner->cleanDirectlyMadeArtifactLinks($artifact, $event->getUser());
    }

    public function codendi_daily_start($params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        SystemEventManager::instance()->createEvent(
            SystemEvent_BURNUP_DAILY::class,
            '',
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        );
        (new RecentlyVisitedTopBacklogDao())->deleteOldVisits();
    }

    public function get_system_event_class($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        switch ($params['type']) {
            case SystemEvent_BURNUP_DAILY::class:
                $params['class']        = SystemEvent_BURNUP_DAILY::class;
                $params['dependencies'] = [
                    new BurnupDataDAO(),
                    $this->getBurnupCalculator(),
                    $this->getBurnupCountElementsCalculator(),
                    new BurnupCacheDao(),
                    new CountElementsCacheDao(),
                    $this->getLogger(),
                    new BurnupCacheDateRetriever(),
                    new PlanningDao(),
                    \PlanningFactory::build(),
                ];
                break;
            case SystemEvent_BURNUP_GENERATE::class:
                $tracker_artifact_factory = Tracker_ArtifactFactory::instance();
                $params['class']          = SystemEvent_BURNUP_GENERATE::class;
                $params['dependencies']   = [
                    $tracker_artifact_factory,
                    SemanticTimeframeBuilder::build(),
                    new BurnupDataDAO(),
                    $this->getBurnupCalculator(),
                    $this->getBurnupCountElementsCalculator(),
                    new BurnupCacheDao(),
                    new CountElementsCacheDao(),
                    $this->getLogger(),
                    new BurnupCacheDateRetriever(),
                    new PlanningDao(),
                    \PlanningFactory::build(),
                ];
                break;
            default:
                break;
        }
    }

    public function system_event_get_types_for_default_queue($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['types'][] = SystemEvent_BURNUP_DAILY::class;
        $params['types'][] = SystemEvent_BURNUP_GENERATE::class;
    }

    private function getLogger(): \Psr\Log\LoggerInterface
    {
        return BackendLogger::getDefaultLogger();
    }

    private function getBurnupCalculator(): BurnupCalculator
    {
        $changeset_factory = Tracker_Artifact_ChangesetFactoryBuilder::build();

        return new BurnupCalculator(
            $changeset_factory,
            $this->getArtifactFactory(),
            new BurnupDataDAO(),
            $this->getSemanticInitialEffortFactory(),
            $this->getSemanticDoneFactory()
        );
    }

    private function getBurnupCountElementsCalculator(): CountElementsCalculator
    {
        $changeset_factory = Tracker_Artifact_ChangesetFactoryBuilder::build();

        return new CountElementsCalculator(
            $changeset_factory,
            $this->getArtifactFactory(),
            Tracker_FormElementFactory::instance(),
            new BurnupDataDAO()
        );
    }

    public function getMessageFetcherAdditionalWarnings(MessageFetcherAdditionalWarnings $event)
    {
        $message_fetcher = new MessageFetcher(
            $this->getPlanningFactory(),
            $this->getSemanticInitialEffortFactory(),
            $this->getSemanticDoneFactory()
        );

        $field = $event->getField();

        if ($field::class === Tuleap\AgileDashboard\FormElement\Burnup::class) {
            $event->setWarnings($message_fetcher->getWarningsRelatedToPlanningConfiguration($field->getTracker()));
        }
    }

    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event)
    {
        $project = $event->getProject();

        if (! $project->usesService($this->getServiceShortname())) {
            return;
        }

        $ugroup_id = HTTPRequest::instance()->get('group');

        $ugroup_manager = new UGroupManager();
        $ugroup         = $ugroup_manager->getUGroup($project, $ugroup_id);
        $ugroup_name    = ($ugroup) ? $ugroup->getTranslatedName() : '';

        $template_factory      = TemplateRendererFactory::build();
        $admin_permission_pane = $template_factory
            ->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR)
            ->renderToString(
                'project-admin-permission-per-group',
                [
                    'ugroup_id'            => $ugroup_id,
                    'project_id'           => $project->getID(),
                    'selected_ugroup_name' => $ugroup_name,
                ]
            );

        $service = $project->getService($this->getServiceShortname());
        if ($service !== null) {
            $rank_in_project = $service->getRank();
            $event->addPane($admin_permission_pane, $rank_in_project);
        }
    }

    public function trackerArtifactDeleted(ArtifactDeleted $artifact_deleted): void
    {
        $burnup_cache_dao = new BurnupCacheDao();
        $artifact         = $artifact_deleted->getArtifact();

        $burnup_cache_dao->deleteArtifactCacheValue($artifact->getId());

        $artifact_explicit_backlog_dao = new ArtifactsInExplicitBacklogDao();
        $artifact_explicit_backlog_dao->removeArtifactFromExplicitBacklog($artifact->getId());
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup('/projects', function (FastRoute\RouteCollector $r) {
            $r->post('/{project_name:[A-z0-9-]+}/backlog/create', $this->getRouteHandler('routeCreateBacklog'));
        });
        $event->getRouteCollector()->addGroup('/plugins/agiledashboard', function (FastRoute\RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeLegacyController'));
        });
    }

    public function routeCreateBacklog(): DispatchableWithRequest
    {
        $planning_factory = PlanningFactory::build();

        return new CreateBacklogController(
            new RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new FeedbackSerializer(new FeedbackDao()),
            ),
            $this,
            new AgileDashboard_FirstScrumCreator(
                $planning_factory,
                TrackerFactory::instance(),
                ProjectXMLImporter::build(
                    new XMLImportHelper(UserManager::instance()),
                    \ProjectCreator::buildSelfByPassValidation()
                )
            ),
            EventManager::instance(),
            new SapiEmitter(),
            new ProjectByNameRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new RejectNonProjectAdministratorMiddleware(UserManager::instance(), new ProjectAdministratorChecker()),
            new CheckProjectCSRFMiddleware(new CSRFSynchronizerTokenProvider()),
            new ServiceInstrumentationMiddleware($this->getServiceShortname()),
        );
    }

    public function routeLegacyController(?ProvideCurrentUser $current_user_provider = null): AgileDashboardLegacyController
    {
        if ($current_user_provider === null) {
            $current_user_provider = UserManager::instance();
        }

        return new AgileDashboardLegacyController(
            new AgileDashboardRouterBuilder(
                $this->getMilestonePaneFactory(),
                new VisitRecorder(new RecentlyVisitedDao()),
                $this->getAllBreadCrumbsForMilestoneBuilder(),
                $this->getBacklogFactory(),
                $current_user_provider,
            )
        );
    }

    public function getAllBreadCrumbsForMilestoneBuilder(): AllBreadCrumbsForMilestoneBuilder
    {
        return new AllBreadCrumbsForMilestoneBuilder(
            new AgileDashboardCrumbBuilder(),
            new MilestoneCrumbBuilder(
                $this->getPluginPath(),
                $this->getMilestonePaneFactory(),
                $this->getMilestoneFactory()
            ),
        );
    }

    public function getSwitchToQuickLinkCollection(SwitchToLinksCollection $collection): void
    {
        $milestone = $this->getMilestoneFactory()->getMilestoneFromArtifact($collection->getArtifact());
        if ($milestone === null) {
            return;
        }

        $collection->setIconName('fa-map-signs');

        $pane_factory = $this->getMilestonePaneFactory();

        $list_of_pane_info = $pane_factory->getListOfPaneInfo($milestone, $collection->getCurrentUser());
        $first_pane        = array_shift($list_of_pane_info);
        if (! $first_pane) {
            return;
        }

        $collection->setMainUri($first_pane->getUri());
        $collection->addQuickLink(
            new SwitchToQuickLink(
                dgettext('tuleap-agiledashboard', 'Milestone artifact'),
                $collection->getArtifactUri(),
                $collection->getArtifactIconName(),
            )
        );

        foreach ($list_of_pane_info as $pane) {
            $collection->addQuickLink(
                new SwitchToQuickLink(
                    $pane->getTitle(),
                    $pane->getUri(),
                    $pane->getIconName()
                )
            );
        }
    }

    private function getBacklogItemCollectionFactory(
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Backlog_IBuildBacklogItemAndBacklogItemCollection $presenter_builder,
    ): AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory {
        $form_element_factory = Tracker_FormElementFactory::instance();

        return new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->getArtifactFactory(),
            $milestone_factory,
            $this->getPlanningFactory(),
            $presenter_builder,
            new RemainingEffortValueRetriever($form_element_factory),
            new ArtifactsInExplicitBacklogDao(),
            new Tracker_Artifact_PriorityDao(),
            \Tuleap\Tracker\Permission\TrackersPermissionsRetriever::build(),
        );
    }

    public function getMilestonePaneFactory(): Planning_MilestonePaneFactory
    {
        $request = HTTPRequest::instance();

        $planning_factory    = $this->getPlanningFactory();
        $milestone_factory   = $this->getMilestoneFactory();
        $hierarchy_factory   = $this->getHierarchyFactory();
        $submilestone_finder = new AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder(
            $hierarchy_factory,
            $planning_factory,
        );

        $pane_info_factory = new AgileDashboard_PaneInfoFactory(
            $submilestone_finder
        );

        $event_manager = EventManager::instance();

        return new Planning_MilestonePaneFactory(
            $request,
            $milestone_factory,
            new AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory(
                $this->getBacklogFactory(),
                $this->getBacklogItemCollectionFactory(
                    $this->getMilestoneFactory(),
                    new AgileDashboard_Milestone_Backlog_BacklogItemPresenterBuilder()
                ),
                new BurnupFieldRetriever(Tracker_FormElementFactory::instance()),
                $event_manager
            ),
            $submilestone_finder,
            $pane_info_factory,
            $event_manager
        );
    }

    private function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/agiledashboard'
        );
    }

    public function statisticsCollectionCollector(StatisticsCollectionCollector $collector): void
    {
        $collector->addStatistics(
            dgettext('tuleap-agiledashboard', 'Milestones'),
            $this->getMilestoneDao()->countMilestones(),
            $this->getMilestoneDao()->countMilestonesAfter($collector->getTimestamp())
        );
    }

    private function getMilestoneDao(): AgileDashboard_Milestone_MilestoneDao
    {
        return new AgileDashboard_Milestone_MilestoneDao();
    }

    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        if ($event->status === \Project::STATUS_DELETED) {
            $artifact_explicit_backlog_dao = new ArtifactsInExplicitBacklogDao();
            $artifact_explicit_backlog_dao->removeExplicitBacklogOfProject((int) $event->project->getID());
        }
    }

    public function additionalArtifactActionButtonsFetcher(AdditionalArtifactActionButtonsFetcher $event): void
    {
        $artifact = $event->getArtifact();
        $user     = $event->getUser();

        $builder = new AdditionalArtifactActionBuilder(
            new ExplicitBacklogDao(),
            $this->getPlanningFactory(),
            $this->getPlanningPermissionsManager(),
            new ArtifactsInExplicitBacklogDao(),
            new PlannedArtifactDao(),
            new \Tuleap\Layout\JavascriptViteAsset(
                new \Tuleap\Layout\IncludeViteAssets(
                    __DIR__ . '/../scripts/artifact-additional-action/frontend-assets',
                    '/assets/agiledashboard/artifact-additional-action'
                ),
                'src/index.ts'
            ),
            new PlanningTrackerBacklogChecker($this->getPlanningFactory()),
            EventManager::instance(),
        );

        $action = $builder->buildArtifactAction($artifact, $user);

        if ($action !== null) {
            $event->addAction($action);
        }
    }

    public function trackerMasschangeGetExternalActionsEvent(TrackerMasschangeGetExternalActionsEvent $event): void
    {
        $builder = new \Tuleap\AgileDashboard\Masschange\AdditionalMasschangeActionBuilder(
            new ExplicitBacklogDao(),
            $this->getPlanningFactory(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/masschange'),
            EventManager::instance(),
        );

        $additional_action = $builder->buildMasschangeAction($event->getTracker(), $event->getUser());
        if ($additional_action !== null) {
            $event->addExternalActions($additional_action);
        }
    }

    public function serviceEnableForXmlImportRetriever(ServiceEnableForXmlImportRetriever $event): void
    {
        $event->addServiceIfPluginIsNotRestricted($this, $this->getServiceShortname());
    }

    public function trackerMasschangeProcessExternalActionsEvent(TrackerMasschangeProcessExternalActionsEvent $event): void
    {
        $processor = new AdditionalMasschangeActionProcessor(
            new ArtifactsInExplicitBacklogDao(),
            new PlannedArtifactDao(),
            $this->getUnplannedArtifactsAdder(),
            EventManager::instance(),
        );

        $processor->processAction(
            $event->getUser(),
            $event->getTracker(),
            $event->getRequest(),
            $event->getMasschangeAids()
        );
    }

    public function getExternalSubFactoriesEvent(GetExternalSubFactoriesEvent $event)
    {
        $event->addFactory(
            $this->getAddToTopBacklogPostActionFactory()
        );
    }

    public function workflowDeletionEvent(WorkflowDeletionEvent $event): void
    {
        $workflow_id = (int) $event->getWorkflow()->getId();

        (new AddToTopBacklogPostActionDao())->deleteWorkflowPostActions($workflow_id);
    }

    private function getUnplannedArtifactsAdder(): UnplannedArtifactsAdder
    {
        return new UnplannedArtifactsAdder(
            new ExplicitBacklogDao(),
            new ArtifactsInExplicitBacklogDao(),
            new PlannedArtifactDao()
        );
    }

    public function transitionDeletionEvent(TransitionDeletionEvent $event)
    {
        $transition_id = (int) $event->getTransition()->getId();

        (new AddToTopBacklogPostActionDao())->deleteTransitionPostActions($transition_id);
    }

    public function postActionVisitExternalActionsEvent(PostActionVisitExternalActionsEvent $event)
    {
        $post_action = $event->getPostAction();

        if (! $post_action instanceof AddToTopBacklog) {
            return;
        }

        $representation = AddToTopBacklogRepresentation::buildFromObject($post_action);
        $event->setRepresentation($representation);
    }

    public function getExternalPostActionJsonParserEvent(GetExternalPostActionJsonParserEvent $event): void
    {
        $event->addParser(
            new AddToTopBacklogJsonParser(new ExplicitBacklogDao())
        );
    }

    public function getWorkflowExternalPostActionsValueUpdater(GetWorkflowExternalPostActionsValueUpdater $event): void
    {
        $event->addValueUpdater(
            new AddToTopBacklogValueUpdater(
                new AddToTopBacklogValueRepository(
                    new AddToTopBacklogPostActionDao()
                )
            )
        );
    }

    public function getExternalSubFactoryByNameEvent(GetExternalSubFactoryByNameEvent $event): void
    {
        if ($event->getPostActionShortName() === AddToTopBacklog::SHORT_NAME) {
            $event->setFactory(
                $this->getAddToTopBacklogPostActionFactory()
            );
        }
    }

    public function externalPostActionSaveObjectEvent(ExternalPostActionSaveObjectEvent $event): void
    {
        $post_action = $event->getPostAction();
        if (! $post_action instanceof AddToTopBacklog) {
            return;
        }

        $factory = $this->getAddToTopBacklogPostActionFactory();
        $factory->saveObject($post_action);
    }

    public function getPostActionShortNameFromXmlTagNameEvent(GetPostActionShortNameFromXmlTagNameEvent $event): void
    {
        if ($event->getXmlTagName() === AddToTopBacklog::XML_TAG_NAME) {
            $event->setPostActionShortName(AddToTopBacklog::SHORT_NAME);
        }
    }

    private function getAddToTopBacklogPostActionFactory(): AddToTopBacklogPostActionFactory
    {
        if (! $this->add_to_top_backlog_post_action_factory) {
            $this->add_to_top_backlog_post_action_factory = new AddToTopBacklogPostActionFactory(
                new AddToTopBacklogPostActionDao(),
                $this->getUnplannedArtifactsAdder(),
                new ExplicitBacklogDao()
            );
        }
        return $this->add_to_top_backlog_post_action_factory;
    }

    /**
     * @throws TrackerFromXmlException
     */
    public function createTrackerFromXMLEvent(CreateTrackerFromXMLEvent $event): void
    {
        $checker = new CreateTrackerFromXMLChecker(new ExplicitBacklogDao());
        $checker->checkTrackerCanBeCreatedInTrackerCreationContext(
            $event->getProject(),
            $event->getTrackerXml()
        );
    }

    /**
     * @throws ImportNotValidException
     */
    #[ListeningToEventClass]
    public function projectXMLImportPreChecksEvent(ProjectXMLImportPreChecksEvent $event): void
    {
        $this->projectXMLImportCheckEvent($event);
    }

    /**
     * @throws ImportNotValidException
     */
    #[ListeningToEventClass]
    public function projectXMLImportFromArchiveTemplatePreChecksEvent(
        ProjectXMLImportFromArchiveTemplatePreChecksEvent $event,
    ): void {
        $this->projectXMLImportCheckEvent($event);
    }

    /**
     * @throws ImportNotValidException
     */
    private function projectXMLImportCheckEvent(ProjectXMLImportPreChecks $event): void
    {
        $xml_content = $event->getXmlElement();
        $checker     = new CreateTrackerFromXMLChecker(new ExplicitBacklogDao());

        try {
            $checker->checkTrackersCanBeCreatedInProjectImportContext($xml_content);
        } catch (ProjectNotUsingExplicitBacklogException $exception) {
            throw new ImportNotValidException(
                'Explicit backlog management is not used and some "AddToTopBacklog" workflow post actions are defined.'
            );
        }
    }

    public function getExternalPostActionPluginsEvent(GetExternalPostActionPluginsEvent $event): void
    {
        $tracker    = $event->getTracker();
        $project_id = (int) $tracker->getGroupId();

        $is_agile_dashboard_used  = $this->isAllowed($project_id);
        $is_explicit_backlog_used = (new ExplicitBacklogDao())->isProjectUsingExplicitBacklog($project_id);

        $planning_tracker_backlog_checker    = new PlanningTrackerBacklogChecker($this->getPlanningFactory());
        $is_tracker_backlog_of_root_planning = $planning_tracker_backlog_checker->isTrackerBacklogOfProjectRootPlanning(
            $tracker,
            $this->getCurrentUser()
        );

        if (! $is_agile_dashboard_used || ! $is_explicit_backlog_used || ! $is_tracker_backlog_of_root_planning || $this->isScrumAccessBlocked($tracker->getProject())) {
            return;
        }

        $event->addServiceNameUsed('agile_dashboard');
    }

    public function checkPostActionsForTracker(CheckPostActionsForTracker $event): void
    {
        $planning_tracker_backlog_checker = new PlanningTrackerBacklogChecker($this->getPlanningFactory());
        $tracker                          = $event->getTracker();
        $external_post_actions            = $event->getPostActions()->getExternalPostActionsValue();
        foreach ($external_post_actions as $post_action) {
            if (
                $post_action instanceof AddToTopBacklogValue &&
                (
                    ! $planning_tracker_backlog_checker->isTrackerBacklogOfProjectRootPlanning(
                        $tracker,
                        $this->getCurrentUser(),
                    ) ||
                    $this->isScrumAccessBlocked($tracker->getProject())
                )
            ) {
                $message = dgettext(
                    'tuleap-agiledashboard',
                    'The post actions cannot be saved because this tracker is not a top backlog tracker and a "AddToTopBacklog" is defined.'
                );

                $event->setErrorMessage($message);
                $event->setPostActionsNonEligible();
            }
        }
    }

    public function getWorkflowExternalPostActionsValuesForUpdate(GetWorkflowExternalPostActionsValuesForUpdate $event): void
    {
        $project_id = (int) $event->getTransition()->getGroupId();
        if (! (new ExplicitBacklogDao())->isProjectUsingExplicitBacklog($project_id)) {
            return;
        }

        $add_to_top_backlog_post_actions = $this->getAddToTopBacklogPostActionFactory()->loadPostActions(
            $event->getTransition()
        );

        if (count($add_to_top_backlog_post_actions) > 0) {
            $event->addExternalValue(new AddToTopBacklogValue());
        }
    }

    private function getExplicitBacklogConfigurationUpdater(): ConfigurationUpdater
    {
        return new ConfigurationUpdater(
            new ExplicitBacklogDao(),
            new MilestoneReportCriterionDao(),
            new AgileDashboard_BacklogItemDao(),
            Planning_MilestoneFactory::build(),
            new ArtifactsInExplicitBacklogDao(),
            new UnplannedArtifactsAdder(
                new ExplicitBacklogDao(),
                new ArtifactsInExplicitBacklogDao(),
                new PlannedArtifactDao()
            ),
            new AddToTopBacklogPostActionDao(),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            EventManager::instance()
        );
    }

    /**
     * @return MustacheRenderer|TemplateRenderer
     */
    private function getTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR);
    }

    private function isScrumAccessBlocked(Project $project): bool
    {
        $block_scrum_access = new \Tuleap\AgileDashboard\BlockScrumAccess($project);
        EventManager::instance()->dispatch($block_scrum_access);
        return ! $block_scrum_access->isScrumAccessEnabled();
    }

    public function jiraImporterExternalPluginsEvent(JiraImporterExternalPluginsEvent $event): void
    {
        (new SemanticsExporter())->process(
            $event->getXmlTracker(),
            $event->getJiraPlatformConfiguration(),
            $event->getFieldMappingCollection(),
        );
    }

    public function getSemanticProgressUsageEvent(GetSemanticProgressUsageEvent $event): void
    {
        $event->addFutureUsageLocation(
            dgettext('tuleap-agiledashboard', 'the Backlog')
        );
    }

    public function semanticDoneUsedExternalServiceEvent(SemanticDoneUsedExternalServiceEvent $event): void
    {
        $project = $event->getTracker()->getProject();
        if (! $project->usesService($this->getServiceShortname())) {
            return;
        }

        $event->setExternalServicesDescriptions(
            new SemanticDoneUsedExternalService(
                dgettext('tuleap-agiledashboard', 'Backlog service'),
                dgettext('tuleap-agiledashboard', 'burnup and velocity charts')
            )
        );
    }

    public function trackerHierarchyUpdateEvent(TrackerHierarchyUpdateEvent $event): void
    {
        $user = UserManager::instance()->getCurrentUser();

        $checker = new TrackerHierarchyUpdateChecker(
            $this->getPlanningFactory(),
            TrackerFactory::instance(),
        );

        try {
            $checker->canTrackersBeLinkedWithHierarchy(
                $user,
                $event->getParentTracker(),
                $event->getChildrenTrackersIds()
            );
        } catch (TrackersCannotBeLinkedWithHierarchyException $exception) {
            $event->setHierarchyCannotBeUpdated();
            $event->setErrorMessage($exception->getMessage());
        }
    }

    public function afterArtifactCopiedEvent(AfterArtifactCopiedEvent $event): void
    {
        $adder = new AddCopiedArtifactsToTopBacklog(
            new ExplicitBacklogDao(),
            new ArtifactsInExplicitBacklogDao(),
            new PlannedArtifactDao(),
        );

        $adder->addCopiedArtifactsToTopBacklog(
            $event->getArtifactImportedMapping(),
            $event->getProject(),
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectMovableExternalFieldEvent(CollectMovableExternalFieldEvent $event): void
    {
        AgileDashboardMovableFieldsCollector::collectMovableFields($event);
    }

    private function getFormElementFactory(): Tracker_FormElementFactory
    {
        return Tracker_FormElementFactory::instance();
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function beforeStartProjectHeader(BeforeStartProjectHeader $event): void
    {
        if (! $this->shouldDisplaySplitModal($event->project, $event->user)) {
            return;
        }

        $event->layout->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../scripts/split-modal/frontend-assets',
                    '/assets/agiledashboard/split-modal'
                ),
                'src/split-modal.ts',
            ),
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function afterStartProjectContainer(AfterStartProjectContainer $event): void
    {
        if (! $this->shouldDisplaySplitModal($event->project, $event->user)) {
            return;
        }

        TemplateRendererFactory::build()
            ->getRenderer(__DIR__ . '/../templates/')
            ->renderToPage('split-modal', $this->getSplitModalPresenter($event->project));
    }

    private function shouldDisplaySplitModal(Project $project, PFUser $user): bool
    {
        if (! $user->getPreference(self::USER_PREF_DISPLAY_SPLIT_MODAL)) {
            return false;
        }

        $kanban_service  = $project->getService(KanbanService::SERVICE_SHORTNAME);
        $backlog_service = $project->getService($this->getServiceShortname());
        if (! $kanban_service && ! $backlog_service) {
            return false;
        }

        return true;
    }

    private function getSplitModalPresenter(Project $project): SplitModalPresenter
    {
        $kanban_service  = $project->getService(KanbanService::SERVICE_SHORTNAME);
        $backlog_service = $project->getService($this->getServiceShortname());

        return new SplitModalPresenter(
            (bool) $kanban_service,
            (bool) $backlog_service,
            $kanban_service?->getUrl(),
            $backlog_service?->getUrl(),
        );
    }

    #[ListeningToEventClass]
    public function collectTrackerDependantServices(CollectTrackerDependantServices $event): void
    {
        $event->addDependantServicesNames($this->getServiceShortname());
    }

    #[ListeningToEventClass]
    public function archiveWithoutDataCheckerEvent(ArchiveWithoutDataCheckerErrorCollection $event): void
    {
        $event->getLogger()->debug('Checking that agiledashboard does not contain data');

        if (! empty($event->getXmlElement()->xpath('//agiledashboard/top_backlog/artifact'))) {
            $event->addError(
                dgettext('tuleap-agiledashboard', 'Archive should not contain agiledashboard data.'),
            );
        }
    }
}
