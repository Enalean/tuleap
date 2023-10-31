<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Tuleap\Plugin\PluginWithLegacyInternalRouting;
use Tuleap\Timetracking\Admin\AdminController;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Admin\TimetrackingEnabler;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Admin\TimetrackingUgroupSaver;
use Tuleap\Timetracking\ArtifactView\ArtifactView;
use Tuleap\Timetracking\ArtifactView\ArtifactViewBuilder;
use Tuleap\Timetracking\JiraImporter\Configuration\JiraTimetrackingConfigurationRetriever;
use Tuleap\Timetracking\JiraImporter\JiraXMLExport;
use Tuleap\Timetracking\JiraImporter\Worklog\WorklogRetriever;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\Plugin\TimetrackingPluginInfo;
use Tuleap\Timetracking\REST\ResourcesInjector;
use Tuleap\Timetracking\REST\v1\ProjectResource;
use Tuleap\Timetracking\Router;
use Tuleap\Timetracking\Time\DateFormatter;
use Tuleap\Timetracking\Time\TimeChecker;
use Tuleap\Timetracking\Time\TimeController;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimePresenterBuilder;
use Tuleap\Timetracking\Time\TimeRetriever;
use Tuleap\Timetracking\Time\TimetrackingReportDao;
use Tuleap\Timetracking\Time\TimeUpdater;
use Tuleap\Timetracking\Widget\TimeTrackingOverview;
use Tuleap\Timetracking\Widget\UserWidget;
use Tuleap\Timetracking\XML\XMLImport;
use Tuleap\Timetracking\XML\XMLExport;
use Tuleap\Tracker\Artifact\Renderer\GetAdditionalCssAssetsForArtifactDisplay;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfigurationForExternalPluginsEvent;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraImporterExternalPluginsEvent;
use Tuleap\Tracker\REST\v1\Event\GetTrackersWithCriteria;
use Tuleap\Tracker\XML\Exporter\TrackerEventExportFullXML;
use Tuleap\Tracker\XML\Importer\ImportXMLProjectTrackerDone;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\CssViteAsset;

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once 'constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

class timetrackingPlugin extends PluginWithLegacyInternalRouting // @codingStandardsIgnoreLine
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);

        bindtextdomain('tuleap-timetracking', __DIR__ . '/../site-content');
    }

    public function getPluginInfo()
    {
        if (! is_a($this->pluginInfo, TimetrackingPluginInfo::class)) {
            $this->pluginInfo = new TimetrackingPluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getDependencies()
    {
        return ['tracker'];
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getAdditionalCssAssetsForArtifactDisplay(
        GetAdditionalCssAssetsForArtifactDisplay $event,
    ): void {
        if ($event->getViewIdentifier() !== ArtifactView::IDENTIFIER) {
            return;
        }
        $event->addCssAsset(
            CssViteAsset::fromFileName(
                new IncludeViteAssets(__DIR__ . '/../scripts/timetracking-tab-styles/frontend-assets', '/assets/timetracking/timetracking-tab-styles'),
                'themes/style.scss'
            ),
        );
    }

    #[\Tuleap\Plugin\ListeningToEventName(Tracker::TRACKER_EVENT_FETCH_ADMIN_BUTTONS)]
    public function trackerEventFetchAdminButtons($params): void
    {
        $url = TIMETRACKING_BASE_URL . '/?' . http_build_query([
            'tracker' => $params['tracker_id'],
            'action'  => 'admin-timetracking',
        ]);

        $params['items']['timetracking'] = [
            'url'         => $url,
            'short_title' => dgettext('tuleap-timetracking', 'Time tracking'),
            'title'       => dgettext('tuleap-timetracking', 'Time tracking'),
            'description' => dgettext('tuleap-timetracking', 'Time tracking for Tuleap artifacts'),
            'data-test'   => 'timetracking',
        ];
    }

    public function process(): void
    {
        $router = new Router(
            TrackerFactory::instance(),
            Tracker_ArtifactFactory::instance(),
            $this->getAdminController(),
            $this->getTimeController()
        );

        $router->route(HTTPRequest::instance());
    }

    /**
     * @return AdminController
     */
    private function getAdminController()
    {
        return new AdminController(
            new TrackerManager(),
            $this->getTimetrackingEnabler(),
            new User_ForgeUserGroupFactory(new UserGroupDao()),
            new PermissionsNormalizer(),
            new TimetrackingUgroupSaver(new TimetrackingUgroupDao()),
            $this->getTimetrackingUgroupRetriever(),
            new ProjectHistoryDao()
        );
    }

    /**
     * @return TimeController
     */
    private function getTimeController()
    {
        $time_dao     = new TimeDao();
        $time_updater = new TimeUpdater(
            $time_dao,
            new TimeChecker(),
            $this->getPermissionsRetriever()
        );

        return new TimeController(
            $time_updater,
            new TimeRetriever($time_dao, $this->getPermissionsRetriever(), new AdminDao(), \ProjectManager::instance())
        );
    }

    /**
     * @return TimetrackingUgroupRetriever
     */
    private function getTimetrackingUgroupRetriever()
    {
        return new TimetrackingUgroupRetriever(
            new TimetrackingUgroupDao(),
            new UGroupManager()
        );
    }

    /**
     * @return PermissionsRetriever
     */
    private function getPermissionsRetriever()
    {
        return new PermissionsRetriever($this->getTimetrackingUgroupRetriever());
    }

    #[\Tuleap\Plugin\ListeningToEventName(Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION)]
    public function trackerArtifactEditrendererAddViewInCollection(array $params): void // @codingStandardsIgnoreLine
    {
        $user     = $params['user'];
        $request  = $params['request'];
        $artifact = $params['artifact'];

        $permissions_retriever = $this->getPermissionsRetriever();
        $time_retriever        = new TimeRetriever(new TimeDao(), $permissions_retriever, new AdminDao(), \ProjectManager::instance());
        $date_formatter        = new DateFormatter();
        $builder               = new ArtifactViewBuilder(
            $this,
            $this->getTimetrackingEnabler(),
            $permissions_retriever,
            $time_retriever,
            new TimePresenterBuilder($date_formatter, UserManager::instance()),
            $date_formatter
        );

        $view = $builder->build($user, $request, $artifact);

        if ($view) {
            $collection = $params['collection'];
            $collection->add($view);
        }
    }

    /**
     * @return TimetrackingEnabler
     */
    private function getTimetrackingEnabler()
    {
        return new TimetrackingEnabler(new AdminDao());
    }

    #[\Tuleap\Plugin\ListeningToEventName('permission_get_name')]
    public function permissionGetName(array $params): void // @codingStandardsIgnoreLine
    {
        if (! $params['name']) {
            switch ($params['permission_type']) {
                case AdminController::WRITE_ACCESS:
                    $params['name'] = dgettext('tuleap-timetracking', 'Write');
                    break;
                case AdminController::READ_ACCESS:
                    $params['name'] = dgettext('tuleap-timetracking', 'Read');
                    break;
                default:
                    break;
            }
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getAdditionalCriteria(\Tuleap\REST\Event\GetAdditionalCriteria $get_projects): void
    {
        $get_projects->addCriteria(ProjectResource::TIMETRACKING_CRITERION, "'with_time_tracking': true");
    }

    /**
     * @throws \Luracast\Restler\RestException
     */
    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getProjectsWithCriteria(\Tuleap\Widget\Event\GetProjectsWithCriteria $get_projects): void
    {
        if (! isset($get_projects->getQuery()[ProjectResource::TIMETRACKING_CRITERION])) {
            return;
        }
        $projects_ressource = new ProjectResource();
        $projects           = $projects_ressource->getProjects(
            $get_projects->getLimit(),
            $get_projects->getOffset(),
            $get_projects->getQuery()
        );
        $get_projects->addProjectsWithCriteria($projects);
    }

    /**
     * @throws Rest_Exception_InvalidTokenException
     * @throws User_PasswordExpiredException
     * @throws User_StatusInvalidException
     * @throws \Luracast\Restler\RestException
     */
    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getTrackersWithCriteria(GetTrackersWithCriteria $get_trackers): void
    {
        if (! isset($get_trackers->getQuery()[ProjectResource::TIMETRACKING_CRITERION])) {
            return;
        }
        $project_ressource       = new ProjectResource();
        $tracker_representations = $project_ressource->getTrackers(
            $get_trackers->getQuery(),
            $get_trackers->getRepresentation(),
            $get_trackers->getProject(),
            $get_trackers->getLimit(),
            $get_trackers->getOffset()
        );

        if (isset($tracker_representations["trackers"])) {
            $get_trackers->setTotalTrackers($tracker_representations["total_trackers"]);
            $get_trackers->addTrackersWithCriteria($tracker_representations["trackers"]);
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('project_admin_ugroup_deletion')]
    public function projectAdminUgroupDeletion(array $params): void // @codingStandardsIgnoreLine
    {
        $ugroup = $params['ugroup'];

        $dao = new TimetrackingUgroupDao();
        $dao->deleteByUgroupId($ugroup->getId());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event): void
    {
        if ($get_widget_event->getName() === UserWidget::NAME) {
            $get_widget_event->setWidget(new UserWidget());
        }
        if ($get_widget_event->getName() === TimeTrackingOverview::NAME) {
            $get_widget_event->setWidget(
                new TimeTrackingOverview(
                    new TimetrackingReportDao(),
                    TemplateRendererFactory::build()->getRenderer(TIMETRACKING_TEMPLATE_DIR)
                )
            );
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $event): void
    {
        $event->addWidget(UserWidget::NAME);
        $event->addWidget(TimeTrackingOverview::NAME);
    }

    #[\Tuleap\Plugin\ListeningToEventName('fill_project_history_sub_events')]
    public function fillProjectHistorySubEvents($params): void // @codingStandardsIgnoreLine
    {
        array_push(
            $params['subEvents']['event_others'],
            'timetracking_enabled',
            'timetracking_disabled',
            'timetracking_permissions_updated'
        );
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function importXMLProjectTrackerDone(ImportXMLProjectTrackerDone $event): void
    {
        $xml                      = $event->getXmlElement();
        $created_trackers_objects = $event->getCreatedTrackersObjects();
        $user_finder              = $event->getUserFinder();
        $artifact_id_mapping      = $event->getArtifactsIdMapping();
        $project                  = $event->getProject();

        $xml_import = new XMLImport(
            new XML_RNGValidator(),
            new TimetrackingEnabler(
                new AdminDao()
            ),
            new TimetrackingUgroupSaver(
                new TimetrackingUgroupDao()
            ),
            new UGroupManager(),
            $user_finder,
            new TimeDao(),
            $event->getLogger()
        );

        $xml_import->import(
            $xml,
            $project,
            $created_trackers_objects,
            $artifact_id_mapping
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function trackerEventExportFullXML(TrackerEventExportFullXML $event): void
    {
        $timetracking_ugroup_retriever = new TimetrackingUgroupRetriever(
            new TimetrackingUgroupDao(),
            new UGroupManager()
        );

        $xml_export = new XMLExport(
            new TimetrackingEnabler(
                new AdminDao()
            ),
            $timetracking_ugroup_retriever,
            Tracker_ArtifactFactory::instance(),
            new TimeRetriever(
                new TimeDao(),
                new PermissionsRetriever($timetracking_ugroup_retriever),
                new AdminDao(),
                \ProjectManager::instance()
            ),
            UserXMLExporter::build(),
            UserManager::instance()
        );

        $xml_export->export(
            $event->getXmlElement(),
            $event->getUser(),
            $event->getExportedTrackers()
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function jiraImporterExternalPluginsEvent(JiraImporterExternalPluginsEvent $event): void
    {
        $xml_exporter = new JiraXMLExport(
            new WorklogRetriever(
                $event->getJiraClient(),
                $event->getLogger()
            ),
            new XML_SimpleXMLCDATAFactory(),
            $event->getJiraUserRetriever(),
            $event->getLogger()
        );

        $xml_exporter->exportJiraTimetracking(
            $event->getXmlTracker(),
            $event->getJiraPlatformConfiguration(),
            $event->getIssueRepresentationCollection()
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function platformConfigurationForExternalPluginsEvent(PlatformConfigurationForExternalPluginsEvent $event): void
    {
        $configuration = (new JiraTimetrackingConfigurationRetriever($event->getJiraClient(), $event->getLogger()))
            ->getJiraTimetrackingConfiguration();

        if ($configuration !== null) {
            $event->addConfigurationInCollection($configuration);
        }
    }
}
