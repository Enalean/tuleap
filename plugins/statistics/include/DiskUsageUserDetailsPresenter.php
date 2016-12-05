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

class DiskUsageUserDetailsPresenter
{
    public $header;
    public $search_fields;
    public $user_details_pane_title;
    public $graph_url;
    public $graph_image_title;
    public $name_label;
    public $start_size_label;
    public $end_size_label;
    public $size_evolution_label;
    public $size_evolution_rate_label;

    public function __construct(
        AdminHeaderPresenter $header,
        DiskUsageUserDetailsSearchFieldsPresenter $search_fields,
        $graph_url,
        array $data_user_details,
        $error_message
    ) {
        $this->header            = $header;
        $this->search_fields     = $search_fields;
        $this->graph_url         = $graph_url;
        $this->data_user_details = $data_user_details;
        $this->error_message     = $error_message;

        $this->user_details_pane_title   = $GLOBALS['Language']->getText('plugin_statistics_show_one_user', 'user_growth');
        $this->graph_image_title         = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'graph_image_title');
        $this->name_label                = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_name_title');
        $this->start_size_label          = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_start_size_title');
        $this->end_size_label            = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_end_size_title');
        $this->size_evolution_label      = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_size_evolution_title');
        $this->size_evolution_rate_label = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_size_evolution_rate_title');
    }
}
