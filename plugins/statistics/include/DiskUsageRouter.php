<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\Statistics;

use Feedback;
use ForgeConfig;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;

class DiskUsageRouter
{
    /**
     * @var DiskUsageGlobalPresenterBuilder
     */
    public $global_builder;
    /**
     * @var DiskUsageServicesPresenterBuilder
     */
    private $services_builder;
    /**
     * @var DiskUsageProjectsPresenterBuilder
     */
    private $projects_builder;

    /**
     * @var DiskUsageTopUsersPresenterBuilder
     */
    private $top_users_builder;

    /**
     * @var DiskUsageUserDetailsPresenterBuilder
     */
    private $user_details_builder;

    public function __construct(
        DiskUsageServicesPresenterBuilder $services_builder,
        DiskUsageProjectsPresenterBuilder $projects_builder,
        DiskUsageTopUsersPresenterBuilder $top_users_builder,
        DiskUsageGlobalPresenterBuilder $global_builder,
        DiskUsageUserDetailsPresenterBuilder $user_details_builder
    ) {
        $this->services_builder     = $services_builder;
        $this->projects_builder     = $projects_builder;
        $this->top_users_builder    = $top_users_builder;
        $this->global_builder       = $global_builder;
        $this->user_details_builder = $user_details_builder;
    }

    public function route(HTTPRequest $request)
    {
        if ($request->get('menu')) {
            $menu = $request->get('menu');

            try {
                switch ($menu) {
                    case 'services':
                        $this->displayServices($request);
                        break;
                    case 'projects':
                        $this->displayProjects($request);
                        break;
                    case 'top_users':
                        $this->displayTopUsers($request);
                        break;
                    case 'global':
                        $this->displayGlobalData();
                        break;
                    case 'one_user_details':
                        $this->displayUserDetails($request);
                        break;
                }
            } catch (StartDateGreaterThanEndDateException $exception) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $GLOBALS['Language']->getText('plugin_statistics', 'period_error')
                );
                $GLOBALS['Response']->redirect('/plugins/statistics/disk_usage.php?menu=' . $menu);
            }
        }
    }

    private function displayServices(HTTPRequest $request)
    {
        $project_id             = $request->get('project_id');
        $selected_project       = $request->get('project_filter');
        $selected_services      = $request->get('services');
        $selected_group_by_date = $request->get('group_by');
        $start_date             = $request->get('start_date');
        $end_date               = $request->get('end_date');
        $relative_y_axis        = $request->get('relative_y_axis');

        $title = $GLOBALS['Language']->getText('plugin_statistics', 'index_page_title');

        $disk_usage_services_presenter = $this->services_builder->buildServices(
            $project_id,
            $title,
            $selected_project,
            $selected_services,
            $selected_group_by_date,
            $start_date,
            $end_date,
            $relative_y_axis
        );

        $admin_page_renderer = new AdminPageRenderer();
        $admin_page_renderer->renderANoFramedPresenter(
            $title,
            ForgeConfig::get('codendi_dir') . '/plugins/statistics/templates',
            'disk-usage-services',
            $disk_usage_services_presenter
        );
    }

    private function displayProjects($request)
    {
        $limit = 25;

        $selected_services = $request->get('services');
        $start_date        = $request->get('start_date');
        $end_date          = $request->get('end_date');
        $order             = $request->get('order');
        $offset            = $request->get('offset');

        $title = $GLOBALS['Language']->getText('plugin_statistics', 'index_page_title');

        $disk_usage_projects_presenter = $this->projects_builder->buildProjects(
            $title,
            $selected_services,
            $start_date,
            $end_date,
            $order,
            $offset,
            $limit
        );

        $admin_page_renderer = new AdminPageRenderer();
        $admin_page_renderer->renderANoFramedPresenter(
            $title,
            ForgeConfig::get('codendi_dir') . '/plugins/statistics/templates',
            'disk-usage-projects',
            $disk_usage_projects_presenter
        );
    }

    public function displayTopUsers(HTTPRequest $request)
    {
        $end_date = $request->get('end_date');

        $title = $GLOBALS['Language']->getText('plugin_statistics', 'index_page_title');

        $top_users_presenter = $this->top_users_builder->build(
            $title,
            $end_date
        );

        $admin_page_renderer = new AdminPageRenderer();
        $admin_page_renderer->renderANoFramedPresenter(
            $title,
            ForgeConfig::get('codendi_dir') . '/plugins/statistics/templates',
            'disk-usage-top-users',
            $top_users_presenter
        );
    }

    private function displayGlobalData()
    {
        $title = $GLOBALS['Language']->getText('plugin_statistics', 'index_page_title');

        $disk_usage_global_presenter = $this->global_builder->build($title);

        $admin_page_renderer = new AdminPageRenderer();
        $admin_page_renderer->renderANoFramedPresenter(
            $title,
            ForgeConfig::get('codendi_dir') . '/plugins/statistics/templates',
            'disk-usage-global',
            $disk_usage_global_presenter
        );
    }

    public function displayUserDetails(HTTPRequest $request)
    {
        $user              = $request->get('user');
        $selected_group_by = $request->get('group_by');
        $start_date        = $request->get('start_date');
        $end_date          = $request->get('end_date');

        $title = $GLOBALS['Language']->getText('plugin_statistics', 'index_page_title');

        $user_details_presenter = $this->user_details_builder->build(
            $title,
            $user,
            $selected_group_by,
            $start_date,
            $end_date
        );

        $admin_page_renderer = new AdminPageRenderer();
        $admin_page_renderer->renderANoFramedPresenter(
            $title,
            ForgeConfig::get('codendi_dir') . '/plugins/statistics/templates',
            'disk-usage-user-details',
            $user_details_presenter
        );
    }
}
