<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\JWT\generators\MercureJWTGeneratorBuilder;
use Tuleap\Kanban\BreadCrumbBuilder;
use Tuleap\Kanban\HierarchyChecker;
use Tuleap\Kanban\KanbanActionsChecker;
use Tuleap\Kanban\KanbanColumnDao;
use Tuleap\Kanban\KanbanColumnFactory;
use Tuleap\Kanban\KanbanColumnManager;
use Tuleap\Kanban\KanbanDao;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Kanban\KanbanItemDao;
use Tuleap\Kanban\KanbanManager;
use Tuleap\Kanban\KanbanPermissionsManager;
use Tuleap\Kanban\KanbanStatisticsAggregator;
use Tuleap\Kanban\KanbanUserPreferences;
use Tuleap\Kanban\Plugin\KanbanPluginInfo;
use Tuleap\Kanban\RealTime\KanbanArtifactMessageBuilder;
use Tuleap\Kanban\RealTime\KanbanArtifactMessageSender;
use Tuleap\Kanban\RealTime\KanbanRealtimeArtifactMessageSender;
use Tuleap\Kanban\RealTime\RealTimeArtifactMessageController;
use Tuleap\Kanban\RealTimeMercure\KanbanArtifactMessageBuilderMercure;
use Tuleap\Kanban\RealTimeMercure\KanbanArtifactMessageSenderMercure;
use Tuleap\Kanban\RealTimeMercure\MercureJWTController;
use Tuleap\Kanban\RealTimeMercure\RealTimeArtifactMessageControllerMercure;
use Tuleap\Kanban\RecentlyVisited\RecentlyVisitedKanbanDao;
use Tuleap\Kanban\RecentlyVisited\VisitRetriever;
use Tuleap\Kanban\REST\ResourcesInjector;
use Tuleap\Kanban\TrackerReport\TrackerReportDao;
use Tuleap\Kanban\TrackerReport\TrackerReportUpdater;
use Tuleap\Kanban\Widget\MyKanban;
use Tuleap\Kanban\Widget\ProjectKanban;
use Tuleap\Kanban\Widget\WidgetKanbanConfigDAO;
use Tuleap\Kanban\Widget\WidgetKanbanConfigRetriever;
use Tuleap\Kanban\Widget\WidgetKanbanConfigUpdater;
use Tuleap\Kanban\Widget\WidgetKanbanCreator;
use Tuleap\Kanban\Widget\WidgetKanbanDao;
use Tuleap\Kanban\Widget\WidgetKanbanDeletor;
use Tuleap\Kanban\Widget\WidgetKanbanRetriever;
use Tuleap\Kanban\Widget\WidgetKanbanXMLImporter;
use Tuleap\Kanban\XML\KanbanXmlImporter;
use Tuleap\Layout\HomePage\StatisticsCollectionCollector;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Plugin\ListeningToEventName;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\Routing\CheckProjectCSRFMiddleware;
use Tuleap\Project\Routing\ProjectAccessCheckerMiddleware;
use Tuleap\Project\Routing\ProjectByNameRetrieverMiddleware;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\RealTimeMercure\ClientBuilder;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Statistics\CSV\StatisticsServiceUsage;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Artifact\Event\ArtifactsReordered;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Config\GeneralSettingsEvent;
use Tuleap\Tracker\Creation\DefaultTemplatesXMLFileCollection;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;
use Tuleap\Tracker\RealTime\RealTimeArtifactMessageSender;
use Tuleap\Tracker\RealtimeMercure\RealTimeMercureArtifactMessageSender;
use Tuleap\Tracker\Report\Event\TrackerReportDeleted;
use Tuleap\Tracker\Report\Event\TrackerReportSetToPrivate;
use Tuleap\Tracker\TrackerCrumbInContext;
use Tuleap\Tracker\TrackerEventTrackersDuplicated;
use Tuleap\Tracker\XML\Importer\ImportXMLProjectTrackerDone;
use Tuleap\User\History\HistoryEntryCollection;
use Tuleap\User\History\HistoryRetriever;

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../../cardwall/include/cardwallPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class KanbanPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindTextDomain('tuleap-kanban', __DIR__ . '/../../kanban/site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new KanbanPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['tracker', 'cardwall'];
    }

    #[ListeningToEventClass]
    public function getHistoryEntryCollection(HistoryEntryCollection $collection): void
    {
        $visit_retriever = new VisitRetriever(
            new RecentlyVisitedKanbanDao(),
            $this->getKanbanFactory(),
            TrackerFactory::instance()
        );
        $visit_retriever->getVisitHistory($collection, HistoryRetriever::MAX_LENGTH_HISTORY);
    }

    #[ListeningToEventClass]
    public function statisticsServiceUsage(StatisticsServiceUsage $event): void
    {
        $statistic_aggregator = new KanbanStatisticsAggregator(EventManager::instance());
        foreach ($statistic_aggregator->getStatisticsLabels() as $statistic_key => $statistic_name) {
            $statistic_data = $statistic_aggregator->getStatistics(
                $statistic_key,
                $event->start_date,
                $event->end_date,
            );
            $event->csv_exporter->buildDatas($statistic_data, $statistic_name);
        }
    }

    #[ListeningToEventName(TrackerFactory::TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED)]
    public function trackerEventProjectCreationTrackersRequired(array $params): void
    {
        $hierarchy_checker = new HierarchyChecker(
            $this->getKanbanFactory(),
            TrackerFactory::instance(),
        );

        $params['tracker_ids_list'] = array_merge(
            $params['tracker_ids_list'],
            $hierarchy_checker->getADTrackerIdsByProjectId((int) $params['project_id'])
        );
    }

    #[ListeningToEventName(Event::USER_HISTORY_CLEAR)]
    public function userHistoryClear(array $params): void
    {
        $user = $params['user'];
        assert($user instanceof PFUser);

        $visit_cleaner = new RecentlyVisitedKanbanDao();
        $visit_cleaner->deleteVisitByUserId((int) $user->getId());
    }

    #[ListeningToEventName('tracker_usage')]
    public function trackerUsage(array $params): void
    {
        $tracker = $params['tracker'];

        $is_used_in_kanban = $this->getKanbanManager()->doesKanbanExistForTracker($tracker);

        if ($is_used_in_kanban) {
            $params['result'] = ['can_be_deleted' => false, 'message' => 'Kanban'];
        }
    }

    #[ListeningToEventClass]
    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $event): void
    {
        if ($event->getName() !== MyKanban::NAME && $event->getName() !== ProjectKanban::NAME) {
            return;
        }

        $widget_kanban_dao        = new WidgetKanbanDao();
        $widget_kanban_config_dao = new WidgetKanbanConfigDAO();
        $widget_kanban_creator    = new WidgetKanbanCreator(
            $widget_kanban_dao
        );
        $widget_kanban_retriever  = new WidgetKanbanRetriever(
            $widget_kanban_dao
        );
        $widget_kanban_deletor    = new WidgetKanbanDeletor(
            $widget_kanban_dao
        );

        $widget_config_retriever = new WidgetKanbanConfigRetriever(
            $widget_kanban_config_dao
        );

        $permission_manager = new KanbanPermissionsManager();
        $kanban_factory     = $this->getKanbanFactory();

        $widget_kanban_config_updater = new WidgetKanbanConfigUpdater(
            $widget_kanban_config_dao
        );

        switch ($event->getName()) {
            case MyKanban::NAME:
                $event->setWidget(
                    new MyKanban(
                        $widget_kanban_creator,
                        $widget_kanban_retriever,
                        $widget_kanban_deletor,
                        $kanban_factory,
                        TrackerFactory::instance(),
                        $permission_manager,
                        $widget_config_retriever,
                        $widget_kanban_config_updater,
                        Tracker_ReportFactory::instance()
                    )
                );
                break;
            case ProjectKanban::NAME:
                $event->setWidget(
                    new ProjectKanban(
                        $widget_kanban_creator,
                        $widget_kanban_retriever,
                        $widget_kanban_deletor,
                        $kanban_factory,
                        TrackerFactory::instance(),
                        $permission_manager,
                        $widget_config_retriever,
                        $widget_kanban_config_updater,
                        Tracker_ReportFactory::instance()
                    )
                );
                break;
        }
    }

    #[ListeningToEventClass]
    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $event): void
    {
        $event->addWidget(MyKanban::NAME);
    }

    #[ListeningToEventClass]
    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event): void
    {
        $event->addWidget(ProjectKanban::NAME);
    }

    #[ListeningToEventClass]
    public function configureAtXMLImport(\Tuleap\Widget\Event\ConfigureAtXMLImport $event): void
    {
        if ($event->getWidget()->getId() === ProjectKanban::NAME) {
            $xml_import = new WidgetKanbanXMLImporter();
            $xml_import->configureWidget($event);
        }
    }

    #[ListeningToEventClass]
    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup('/kanban', function (FastRoute\RouteCollector $r) {
            $r->get('/{id:[0-9]+}', $this->getRouteHandler('routeShowKanban'));
            $r->post('/{kanban_id:\d+}/mercure-realtime-token', $this->getRouteHandler('routeGetJWT'));
        });
        $event->getRouteCollector()->addGroup('/projects', function (FastRoute\RouteCollector $r) {
            $r->post('/{project_name:[A-z0-9-]+}/kanban/create', $this->getRouteHandler('routeCreateKanban'));
            $r->get('/{project_name:[A-z0-9-]+}/kanban', $this->getRouteHandler('routeGetProject'));
        });
    }

    public function routeCreateKanban(): \Tuleap\Request\DispatchableWithRequest
    {
        $tracker_factory = TrackerFactory::instance();
        $user_manager    = UserManager::instance();

        return new \Tuleap\Kanban\Home\CreateKanbanController(
            new RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao()),
            ),
            new KanbanManager(new KanbanDao(), $tracker_factory),
            $tracker_factory,
            new SapiEmitter(),
            new ProjectByNameRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new ProjectAccessCheckerMiddleware(
                new \Tuleap\Project\ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    EventManager::instance()
                ),
                $user_manager,
            ),
            new CheckProjectCSRFMiddleware(new \Tuleap\Kanban\Home\CSRFSynchronizerTokenProvider()),
            new \Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware($user_manager, new ProjectAdministratorChecker()),
        );
    }

    public function routeGetProject(): \Tuleap\Request\DispatchableWithRequest
    {
        return new \Tuleap\Kanban\Home\KanbanHomeController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new KanbanFactory(TrackerFactory::instance(), new KanbanDao()),
            new KanbanItemDao(),
            TemplateRendererFactory::build(),
            new SapiEmitter(),
            new ProjectByNameRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new ProjectAccessCheckerMiddleware(
                new \Tuleap\Project\ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    EventManager::instance()
                ),
                UserManager::instance(),
            )
        );
    }

    public function routeShowKanban(): \Tuleap\Request\DispatchableWithRequest
    {
        $tracker_factory = TrackerFactory::instance();

        return new \Tuleap\Kanban\ShowKanbanController(
            $this->getKanbanFactory(),
            $tracker_factory,
            new KanbanPermissionsManager(),
            EventManager::instance(),
            new BreadCrumbBuilder($tracker_factory, $this->getKanbanFactory()),
            new RecentlyVisitedKanbanDao(),
            new \Tuleap\Kanban\NewDropdown\NewDropdownCurrentContextSectionForKanbanProvider(
                $this->getKanbanFactory(),
                $tracker_factory,
                new TrackerNewDropdownLinkPresenterBuilder(),
                new KanbanActionsChecker(
                    $tracker_factory,
                    new KanbanPermissionsManager(),
                    Tracker_FormElementFactory::instance(),
                    \Tuleap\Tracker\Permission\SubmissionPermissionVerifier::instance(),
                )
            )
        );
    }

    #[ListeningToEventClass]
    public function trackerCrumbInContext(TrackerCrumbInContext $crumb): void
    {
        (new \Tuleap\Kanban\BreadCrumbBuilder($this->getTrackerFactory(), $this->getKanbanFactory()))->addKanbanCrumb($crumb);
    }

    public function routeGetJWT(): MercureJWTController
    {
        return new MercureJWTController(
            $this->getKanbanFactory(),
            $this->getLogger(),
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            UserManager::instance(),
            MercureJWTGeneratorBuilder::build(MercureJWTGeneratorBuilder::DEFAULTPATH),
            new SapiEmitter()
        );
    }

    #[ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    #[ListeningToEventClass]
    public function generalSettingsEvent(GeneralSettingsEvent $event): void
    {
        $hierarchyChecker = new HierarchyChecker(
            $this->getKanbanFactory(),
            TrackerFactory::instance(),
        );

        $event->cannot_configure_instantiate_for_new_projects = $hierarchyChecker->isPartOfKanbanHierarchy($event->tracker);
    }

    private function getKanbanFactory(): KanbanFactory
    {
        return new KanbanFactory(
            TrackerFactory::instance(),
            new KanbanDao()
        );
    }

    #[ListeningToEventClass]
    public function trackerArtifactCreated(ArtifactCreated $event): void
    {
        $artifact = $event->getArtifact();
        $sender   = new KanbanRealtimeArtifactMessageSender(
            $this->getRealtimeMessageControllerMercure(),
            $this->getRealtimeMessageController()
        );
        if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY)) {
            $sender->sendMessageArtifact(
                $artifact,
                $this->getCurrentUser(),
                RealTimeArtifactMessageControllerMercure::EVENT_NAME_ARTIFACT_CREATED
            );
        } else {
            $sender->sendMessageArtifact(
                $artifact,
                $this->getCurrentUser(),
                RealTimeArtifactMessageController::EVENT_NAME_ARTIFACT_CREATED
            );
        }
    }

    #[ListeningToEventClass]
    public function trackerArtifactUpdated(ArtifactUpdated $event): void
    {
        $artifact = $event->getArtifact();
        $sender   = new KanbanRealtimeArtifactMessageSender(
            $this->getRealtimeMessageControllerMercure(),
            $this->getRealtimeMessageController()
        );
        if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY)) {
            $sender->sendMessageArtifact(
                $artifact,
                $this->getCurrentUser(),
                RealTimeArtifactMessageControllerMercure::EVENT_NAME_ARTIFACT_UPDATED
            );
        } else {
            $sender->sendMessageArtifact(
                $artifact,
                $this->getCurrentUser(),
                RealTimeArtifactMessageController::EVENT_NAME_ARTIFACT_UPDATED
            );
        }
    }

    private function getCurrentUser(): PFUser
    {
        return UserManager::instance()->getCurrentUser();
    }

    private function getArtifactFactory(): Tracker_ArtifactFactory
    {
        return Tracker_ArtifactFactory::instance();
    }

    #[ListeningToEventClass]
    public function trackerArtifactsReordered(ArtifactsReordered $event): void
    {
        $artifacts_ids = $event->getArtifactsIds();
        $artifacts     = $this->getArtifactFactory()->getArtifactsByArtifactIdList($artifacts_ids);
        $sender        = new KanbanRealtimeArtifactMessageSender(
            $this->getRealtimeMessageControllerMercure(),
            $this->getRealtimeMessageController()
        );
        if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY)) {
            foreach ($artifacts as $artifact) {
                $sender->sendMessageArtifact(
                    $artifact,
                    $this->getCurrentUser(),
                    RealTimeArtifactMessageControllerMercure::EVENT_NAME_ARTIFACT_REORDERED
                );
            }
        } else {
            foreach ($artifacts as $artifact) {
                $sender->sendMessageArtifact(
                    $artifact,
                    $this->getCurrentUser(),
                    RealTimeArtifactMessageController::EVENT_NAME_ARTIFACT_REORDERED
                );
            }
        }
    }

    #[ListeningToEventClass]
    public function trackerReportDeleted(TrackerReportDeleted $event): void
    {
        $report  = $event->getReport();
        $updater = new TrackerReportUpdater(new TrackerReportDao());

        $updater->deleteAllForReport($report);

        $this->deleteReportConfigForKanbanWidget(
            $event->getReport()
        );
    }

    #[ListeningToEventClass]
    public function trackerReportSetToPrivate(TrackerReportSetToPrivate $event): void
    {
        $this->deleteReportConfigForKanbanWidget(
            $event->getReport()
        );
    }

    #[ListeningToEventName('codendi_daily_start')]
    public function codendiDailyStart(array $params): void
    {
        (new RecentlyVisitedKanbanDao())->deleteOldVisits();
    }

    #[ListeningToEventClass]
    public function statisticsCollectionCollector(StatisticsCollectionCollector $collector): void
    {
        $collector->addStatistics(
            dgettext('tuleap-kanban', 'Kanban cards'),
            $this->getKanbanDao()->countKanbanCards(),
            $this->getKanbanDao()->countKanbanCardsAfter($collector->getTimestamp())
        );
    }

    #[ListeningToEventClass]
    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        if ($event->shouldProjectInheritFromTemplate()) {
            (new \Tuleap\Kanban\Legacy\LegacyConfigurationDao())->duplicate(
                (int) $event->getJustCreatedProject()->getID(),
                (int) $event->getTemplateProject()->getID(),
            );
        }
    }

    #[ListeningToEventClass]
    public function defaultTemplatesXMLFileCollection(DefaultTemplatesXMLFileCollection $collection): void
    {
        $this->addKanbanTemplates($collection);
    }

    private function addKanbanTemplates(DefaultTemplatesXMLFileCollection $collection): void
    {
        $collection->add(__DIR__ . '/../resources/templates/Tracker_activity.xml');
    }

    #[ListeningToEventClass]
    public function trackerEventTrackersDuplicated(TrackerEventTrackersDuplicated $event): void
    {
        $this->getKanbanManager()->duplicateKanbans(
            $event->tracker_mapping,
            $event->field_mapping,
            $event->report_mapping,
        );
    }

    private function getKanbanManager(): KanbanManager
    {
        return new KanbanManager(
            new KanbanDao(),
            $this->getTrackerFactory()
        );
    }

    private function getTrackerFactory(): TrackerFactory
    {
        return TrackerFactory::instance();
    }

    private function getKanbanDao(): KanbanDao
    {
        return new KanbanDao();
    }

    private function deleteReportConfigForKanbanWidget(Tracker_Report $report): void
    {
        $widget_kanban_config_updater = new WidgetKanbanConfigUpdater(
            new WidgetKanbanConfigDAO()
        );

        $widget_kanban_config_updater->deleteConfigurationForWidgetMatchingReportId($report);
    }

    #[ListeningToEventClass]
    public function importXMLProjectTrackerDone(ImportXMLProjectTrackerDone $event): void
    {
        $xml             = $event->getXmlElement();
        $tracker_mapping = $event->getCreatedTrackersMapping();
        $value_mapping   = $event->getXmlFieldValuesMapping();
        $logger          = $event->getLogger();
        $project         = $event->getProject();
        $user            = UserManager::instance()->getCurrentUser();

        $kanban = new KanbanXmlImporter(
            new WrapperLogger($logger, "kanban"),
            $this->getKanbanManager(),
            new \Tuleap\Kanban\Legacy\LegacyConfigurationDao(),
            $this->getDashboardKanbanColumnManager(),
            $this->getKanbanFactory(),
            $this->getKanbanColumnFactory()
        );
        $kanban->import($xml, $tracker_mapping, $project, $value_mapping, $user, $event->getMappingsRegistery());
    }

    private function getDashboardKanbanColumnManager(): KanbanColumnManager
    {
        return new KanbanColumnManager(
            new KanbanColumnDao(),
            new BindStaticValueDao(),
            new KanbanActionsChecker(
                $this->getTrackerFactory(),
                new KanbanPermissionsManager(),
                $this->getFormElementFactory(),
                \Tuleap\Tracker\Permission\SubmissionPermissionVerifier::instance(),
            )
        );
    }

    private function getFormElementFactory(): Tracker_FormElementFactory
    {
        return Tracker_FormElementFactory::instance();
    }

    private function getKanbanColumnFactory(): KanbanColumnFactory
    {
        return new KanbanColumnFactory(
            new KanbanColumnDao(),
            new KanbanUserPreferences(),
        );
    }

    private function getRealtimeMessageController(): RealTimeArtifactMessageController
    {
        return new RealTimeArtifactMessageController(
            $this->getKanbanFactory(),
            $this->getKanbanArtifactMessageSender()
        );
    }

    private function getRealtimeMessageControllerMercure(): RealTimeArtifactMessageControllerMercure
    {
        return new RealTimeArtifactMessageControllerMercure(
            $this->getKanbanFactory(),
            $this->getKanbanArtifactMessageSenderMercure()
        );
    }

    private function getKanbanArtifactMessageSender(): KanbanArtifactMessageSender
    {
        $kanban_item_dao                   = new KanbanItemDao();
        $permissions_serializer            = new Tracker_Permission_PermissionsSerializer(
            new Tracker_Permission_PermissionRetrieveAssignee(UserManager::instance())
        );
        $node_js_client                    = new NodeJSClient(
            HttpClientFactory::createClientForInternalTuleapUse(),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            BackendLogger::getDefaultLogger()
        );
        $realtime_artifact_message_builder = new KanbanArtifactMessageBuilder(
            $kanban_item_dao,
            Tracker_Artifact_ChangesetFactoryBuilder::build()
        );
        $backend_logger                    = BackendLogger::getDefaultLogger('realtime_syslog');
        $realtime_artifact_message_sender  = new RealTimeArtifactMessageSender($node_js_client, $permissions_serializer);

        return new KanbanArtifactMessageSender(
            $realtime_artifact_message_sender,
            $realtime_artifact_message_builder,
            $backend_logger
        );
    }

    private function getKanbanArtifactMessageSenderMercure(): KanbanArtifactMessageSenderMercure
    {
        $kanba_item_dao                           = new KanbanItemDao();
        $mercure_client                           = ClientBuilder::build(ClientBuilder::DEFAULTPATH);
        $realtime_artifact_message_builder_kanban = new KanbanArtifactMessageBuilderMercure(
            $kanba_item_dao,
            Tracker_Artifact_ChangesetFactoryBuilder::build()
        );

        $realtime_artifact_message_sender_mercure = new RealTimeMercureArtifactMessageSender($mercure_client);
        return new KanbanArtifactMessageSenderMercure(
            $realtime_artifact_message_sender_mercure,
            $realtime_artifact_message_builder_kanban
        );
    }

    private function getLogger(): \Psr\Log\LoggerInterface
    {
        return BackendLogger::getDefaultLogger();
    }
}
