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

class DiskUsageServicesPresenter
{
    public $header;
    public $search_fields;
    public $graph_url;
    public $data_services;
    public $total_start_size;
    public $total_end_size;
    public $total_evolution;

    public $pane_title;
    public $table_service_title;
    public $table_start_size_title;
    public $table_end_size_title;
    public $table_size_evolution_title;
    public $total_start_size_tooltip;
    public $total_end_size_tooltip;
    public $total_evolution_tooltip;
    public $no_data;
    public $graph_image_title;

    public function __construct(
        AdminHeaderPresenter $header,
        DiskUsageServicesSearchFieldsPresenter $search_fields,
        $graph_url,
        array $data_services,
        $total_start_size,
        $total_end_size,
        $total_evolution
    ) {
        $this->header           = $header;
        $this->search_fields    = $search_fields;
        $this->graph_url        = $graph_url;
        $this->data_services    = $data_services;
        $this->total_start_size = $total_start_size;
        $this->total_end_size   = $total_end_size;
        $this->total_evolution  = $total_evolution;

        $this->pane_title                 = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'data_services_pane_title');
        $this->table_service_title        = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_service_title');
        $this->table_start_size_title     = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_start_size_title');
        $this->table_end_size_title       = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_end_size_title');
        $this->table_size_evolution_title = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_size_evolution_title');
        $this->total_start_size_tooltip   = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'total_start_size_tooltip');
        $this->total_end_size_tooltip     = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'total_end_size_tooltip');
        $this->total_evolution_tooltip    = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'total_evolution_tooltip');
        $this->no_data                    = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'data_services_no_data');
        $this->graph_image_title          = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'graph_image_title');
    }
}
