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

class DataExportRouter
{
    /**
     * @var DataExportPresenterBuilder
     */
    private $data_export_builder;

    public function __construct(
        DataExportPresenterBuilder $data_export_builder
    ) {
        $this->data_export_builder = $data_export_builder;
    }

    public function route(HTTPRequest $request)
    {
        try {
            $this->displayExportData($request);
        } catch (StartDateGreaterThanEndDateException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_statistics', 'period_error')
            );
            $GLOBALS['Response']->redirect('/plugins/statistics/data_export.php');
        }
    }

    private function displayExportData(HTTPRequest $request)
    {
        $services_usage_start_date       = $request->get('services_usage_start_date');
        $services_usage_end_date         = $request->get('services_usage_end_date');
        $scm_statistics_start_date       = $request->get('scm_statistics_start_date');
        $scm_statistics_end_date         = $request->get('scm_statistics_end_date');
        $scm_statistics_selected_project = $request->get('scm_statistics_project_select');

        $title = $GLOBALS['Language']->getText('plugin_statistics', 'index_page_title');

        $data_export_presenter = $this->data_export_builder->build(
            $title,
            $services_usage_start_date,
            $services_usage_end_date,
            $scm_statistics_start_date,
            $scm_statistics_end_date,
            $scm_statistics_selected_project
        );

        $admin_page_renderer = new AdminPageRenderer();
        $admin_page_renderer->renderANoFramedPresenter(
            $title,
            ForgeConfig::get('codendi_dir') . '/plugins/statistics/templates',
            'data-export',
            $data_export_presenter
        );
    }
}
