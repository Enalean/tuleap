<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use ForgeConfig;
use HttpRequest;
use Statistics_DiskUsageManager;
use Tuleap\Admin\AdminPageRenderer;

class DiskUsageRouter
{
    /**
     * @var Statistics_DiskUsageManager
     */
    private $usage_manager;
    /**
     * @var DiskUsageServicesPresenterBuilder
     */
    private $services_builder;
    /**
     * @var DiskUsageTopProjectsPresenterBuilder
     */
    private $top_projects_builder;

    public function __construct(
        Statistics_DiskUsageManager $usage_manager,
        DiskUsageServicesPresenterBuilder $services_builder,
        DiskUsageTopProjectsPresenterBuilder $top_projects_builder
    ) {
        $this->usage_manager        = $usage_manager;
        $this->services_builder     = $services_builder;
        $this->top_projects_builder = $top_projects_builder;
    }

    public function route(HTTPRequest $request)
    {
        if ($request->get('menu')) {
            $menu = $request->get('menu');

            switch ($menu) {
                case 'services':
                    $this->displayServices($request);
                    break;
                case 'top_projects':
                    $this->displayTopProjects($request);
                    break;
            }
        }
    }

    private function displayServices(HTTPRequest $request)
    {
        $group_id               = $request->get('group_id');
        $selected_services      = $request->get('services');
        $selected_group_by_date = $request->get('group_by');
        $start_date             = $request->get('start_date');
        $end_date               = $request->get('end_date');
        $relative_y_axis        = $request->get('relative_y_axis');

        $title = $GLOBALS['Language']->getText('plugin_statistics', 'index_page_title');

        $disk_usage_services_presenter = $this->services_builder->buildServices(
            $title,
            $group_id,
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

    private function displayTopProjects($request)
    {
        $limit = 25;

        $selected_services = $request->get('services');
        $start_date        = $request->get('start_date');
        $end_date          = $request->get('end_date');
        $order             = $request->get('order');
        $offset            = $request->get('offset');

        $title = $GLOBALS['Language']->getText('plugin_statistics', 'index_page_title');

        $disk_usage_top_projects_presenter = $this->top_projects_builder->buildTopProjects(
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
            'disk-usage-top-projects',
            $disk_usage_top_projects_presenter
        );
    }
}
