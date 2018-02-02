<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Timesheeting\Admin\AdminController;
use Tuleap\Timesheeting\Admin\AdminDao;
use Tuleap\Timesheeting\Admin\TimesheetingEnabler;
use Tuleap\Timesheeting\Admin\TimesheetingUgroupDao;
use Tuleap\Timesheeting\Admin\TimesheetingUgroupRetriever;
use Tuleap\Timesheeting\Admin\TimesheetingUgroupSaver;
use Tuleap\Timesheeting\ArtifactView\ArtifactViewBuilder;
use Tuleap\Timesheeting\Permissions\PermissionsRetriever;
use Tuleap\Timesheeting\Time\DateFormatter;
use Tuleap\Timesheeting\Time\TimeController;
use Tuleap\Timesheeting\Time\TimeDao;
use Tuleap\Timesheeting\Time\TimePresenterBuilder;
use Tuleap\Timesheeting\Time\TimeRetriever;
use Tuleap\Timesheeting\Time\TimeUpdater;
use Tuleap\Timesheeting\TimesheetingPluginInfo;
use Tuleap\Timesheeting\Router;
use Tuleap\Timesheeting\Widget\UserWidget;

require_once 'autoload.php';
require_once 'constants.php';

class timesheetingPlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);

        bindtextdomain('tuleap-timesheeting', __DIR__.'/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook('cssfile');
        $this->addHook('permission_get_name');
        $this->addHook('project_admin_ugroup_deletion');
        $this->addHook('widget_instance');
        $this->addHook('widgets');
        $this->addHook('fill_project_history_sub_events');

        if (defined('TRACKER_BASE_URL')) {
            $this->addHook(TRACKER_EVENT_FETCH_ADMIN_BUTTONS);
            $this->addHook(Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION);
        }

        return parent::getHooksAndCallbacks();
    }

    public function getPluginInfo() {
        if (! is_a($this->pluginInfo, 'TimesheetingPluginInfo')) {
            $this->pluginInfo = new TimesheetingPluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getDependencies()
    {
        return array('tracker');
    }

    public function cssfile($params)
    {
        $include_tracker_css_file = false;
        EventManager::instance()->processEvent(
            TRACKER_EVENT_INCLUDE_CSS_FILE,
            array('include_tracker_css_file' => &$include_tracker_css_file)
        );
        // Only show the stylesheet if we're actually in the tracker pages.
        // This stops styles inadvertently clashing with the main site.
        if ($include_tracker_css_file) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    /**
     * @see TRACKER_EVENT_FETCH_ADMIN_BUTTONS
     */
    public function trackerEventFetchAdminButtons($params)
    {
        $url = '/plugins/timesheeting/?'. http_build_query(array(
                'tracker' => $params['tracker_id'],
                'action'  => 'admin-timesheeting'
        ));

        $params['items']['timesheeting'] = array(
            'url'         => $url,
            'short_title' => dgettext('tuleap-timesheeting', 'Timesheeting'),
            'title'       => dgettext('tuleap-timesheeting', 'Timesheeting'),
            'description' => dgettext('tuleap-timesheeting', 'Timesheeting for Tuleap artifacts'),
            'img'         => TIMESHEETING_BASE_URL . '/images/icon-timesheeting.png'
        );
    }

    public function process(Codendi_Request $request)
    {
        $router = new Router(
            TrackerFactory::instance(),
            Tracker_ArtifactFactory::instance(),
            $this->getAdminController(),
            $this->getTimeController()
        );

        $router->route($request);
    }

    /**
     * @return AdminController
     */
    private function getAdminController()
    {
        return new AdminController(
            new TrackerManager(),
            $this->getTimesheetingEnabler(),
            new User_ForgeUserGroupFactory(new UserGroupDao()),
            new PermissionsNormalizer(),
            new TimesheetingUgroupSaver(new TimesheetingUgroupDao()),
            $this->getTimesheetingUgroupRetriever(),
            new ProjectHistoryDao()
        );
    }

    /**
     * @return TimeController
     */
    private function getTimeController()
    {
        return new TimeController(
            $this->getPermissionsRetriever(),
            new TimeUpdater(new TimeDao())
        );
    }

    /**
     * @return TimesheetingUgroupRetriever
     */
    private function getTimesheetingUgroupRetriever()
    {
        return new TimesheetingUgroupRetriever(new TimesheetingUgroupDao());
    }

    /**
     * @return PermissionsRetriever
     */
    private function getPermissionsRetriever()
    {
        return new PermissionsRetriever($this->getTimesheetingUgroupRetriever());
    }

    /** @see Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION */
    public function tracker_artifact_editrenderer_add_view_in_collection(array $params)
    {
        $user       = $params['user'];
        $request    = $params['request'];
        $artifact   = $params['artifact'];

        $permissions_retriever = $this->getPermissionsRetriever();
        $time_retriever        = new TimeRetriever(new TimeDao(), $permissions_retriever);
        $date_formatter        = new DateFormatter();
        $builder               = new ArtifactViewBuilder(
            $this,
            $this->getTimesheetingEnabler(),
            $permissions_retriever,
            $time_retriever,
            new TimePresenterBuilder($date_formatter),
            $date_formatter
        );

        $view = $builder->build($user, $request, $artifact);

        if ($view) {
            $collection = $params['collection'];
            $collection->add($view);
        }
    }

    /**
     * @return TimesheetingEnabler
     */
    private function getTimesheetingEnabler()
    {
        return new TimesheetingEnabler(new AdminDao());
    }

    public function permission_get_name(array $params)
    {
        if (! $params['name']) {
            switch($params['permission_type']) {
                case AdminController::WRITE_ACCESS:
                    $params['name'] = dgettext('tuleap-timesheeting', 'Write');
                    break;
                case AdminController::READ_ACCESS:
                    $params['name'] = dgettext('tuleap-timesheeting', 'Read');
                    break;
                default:
                    break;
            }
        }
    }

    public function project_admin_ugroup_deletion(array $params)
    {
        $ugroup = $params['ugroup'];

        $dao = new TimesheetingUgroupDao();
        $dao->deleteByUgroupId($ugroup->getId());
    }

    public function widgets(array $params)
    {
        switch ($params['owner_type']) {
            case UserDashboardController::LEGACY_DASHBOARD_TYPE:
                $params['codendi_widgets'][] = UserWidget::NAME;
                break;
        }
    }

    public function widgetInstance(array $params)
    {
        if ($params['widget'] === UserWidget::NAME) {
            $params['instance'] = new UserWidget();
        }
    }

    public function fill_project_history_sub_events($params)
    {
        array_push(
            $params['subEvents']['event_others'],
            'timesheeting_enabled',
            'timesheeting_disabled',
            'timesheeting_permissions_updated'
        );
    }
}
