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

use Tuleap\Layout\IncludeAssets;
use Tuleap\Plugin\PluginWithLegacyInternalRouting;
use Tuleap\Timetracking\Admin\AdminController;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Admin\TimetrackingEnabler;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Admin\TimetrackingUgroupSaver;
use Tuleap\Timetracking\ArtifactView\ArtifactViewBuilder;
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
use Tuleap\Tracker\REST\v1\Event\GetTrackersWithCriteria;

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

    public function getHooksAndCallbacks()
    {
        $this->addHook('cssfile');
        $this->addHook('permission_get_name');
        $this->addHook('project_admin_ugroup_deletion');
        $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
        $this->addHook(\Tuleap\REST\Event\GetAdditionalCriteria::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetProjectsWithCriteria::NAME);
        $this->addHook(GetTrackersWithCriteria::NAME);
        $this->addHook('fill_project_history_sub_events');
        $this->addHook(Event::REST_RESOURCES);

        $this->listenToCollectRouteEventWithDefaultController();

        if (defined('TRACKER_BASE_URL')) {
            $this->addHook(TRACKER_EVENT_FETCH_ADMIN_BUTTONS);
            $this->addHook(Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION);
        }

        return parent::getHooksAndCallbacks();
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
        return array('tracker');
    }

    public function cssfile($params)
    {
        if (
            strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL) === 0
        ) {
            $style_css_url = $this->getAssets()->getFileURL('style-fp.css');

            echo '<link rel="stylesheet" type="text/css" href="' . $style_css_url . '" />';
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/timetracking',
            '/assets/timetracking'
        );
    }

    /**
     * @see TRACKER_EVENT_FETCH_ADMIN_BUTTONS
     */
    public function trackerEventFetchAdminButtons($params)
    {
        $url = TIMETRACKING_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $params['tracker_id'],
                'action'  => 'admin-timetracking'
        ));

        $params['items']['timetracking'] = array(
            'url'         => $url,
            'short_title' => dgettext('tuleap-timetracking', 'Time tracking'),
            'title'       => dgettext('tuleap-timetracking', 'Time tracking'),
            'description' => dgettext('tuleap-timetracking', 'Time tracking for Tuleap artifacts'),
            'data-test'   => 'timetracking'
        );
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
        return new TimetrackingUgroupRetriever(new TimetrackingUgroupDao());
    }

    /**
     * @return PermissionsRetriever
     */
    private function getPermissionsRetriever()
    {
        return new PermissionsRetriever($this->getTimetrackingUgroupRetriever());
    }

    /** @see Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION */
    public function tracker_artifact_editrenderer_add_view_in_collection(array $params) // @codingStandardsIgnoreLine
    {
        $user       = $params['user'];
        $request    = $params['request'];
        $artifact   = $params['artifact'];

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

    public function permission_get_name(array $params) // @codingStandardsIgnoreLine
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

    public function getAdditionalCriteria(\Tuleap\REST\Event\GetAdditionalCriteria $get_projects)
    {
        $get_projects->addCriteria(ProjectResource::TIMETRACKING_CRITERION, "'with_time_tracking': true");
    }

    /**
     * @throws \Luracast\Restler\RestException
     */
    public function getProjectsWithCriteria(\Tuleap\Widget\Event\GetProjectsWithCriteria $get_projects)
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
    public function getTrackersWithCriteria(GetTrackersWithCriteria $get_trackers)
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

    public function project_admin_ugroup_deletion(array $params) // @codingStandardsIgnoreLine
    {
        $ugroup = $params['ugroup'];

        $dao = new TimetrackingUgroupDao();
        $dao->deleteByUgroupId($ugroup->getId());
    }

    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event)
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

    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $event)
    {
        $event->addWidget(UserWidget::NAME);
        $event->addWidget(TimeTrackingOverview::NAME);
    }

    public function fill_project_history_sub_events($params) // @codingStandardsIgnoreLine
    {
        array_push(
            $params['subEvents']['event_others'],
            'timetracking_enabled',
            'timetracking_disabled',
            'timetracking_permissions_updated'
        );
    }

    /** @see Event::REST_RESOURCES */
    public function restResources(array $params)
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }
}
