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

use Tuleap\Layout\PaginationPresenter;

class DiskUsageTopProjectsPresenter
{
    public $header;
    public $search_fields;
    public $data_top_projects;
    public $nb_projects;
    public $start_size_url;
    public $end_size_url;
    public $evolution_url;
    public $evolution_rate_url;

    public function __construct(
        AdminHeaderPresenter $header,
        DiskUsageTopProjectsSearchFieldsPresenter $search_fields,
        array $data_top_projects,
        $nb_projects,
        $order,
        $offset,
        $limit
    ) {
        $this->header             = $header;
        $this->search_fields      = $search_fields;
        $this->data_top_projects  = $data_top_projects;
        $this->nb_projects        = $nb_projects;

        $this->start_size_url     = $this->search_fields->fields_values_url.'&order=start_size';
        $this->end_size_url       = $this->search_fields->fields_values_url.'&order=end_size';
        $this->evolution_url      = $this->search_fields->fields_values_url.'&order=evolution';
        $this->evolution_rate_url = $this->search_fields->fields_values_url.'&order=evolution_rate';

        $this->pane_title                      = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'data_top_projects_pane_title');
        $this->table_rank_title                = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_rank_title');
        $this->table_id_title                  = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_id_title');
        $this->table_name_title                = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_name_title');
        $this->table_start_size_title          = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_start_size_title');
        $this->table_end_size_title            = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_end_size_title');
        $this->table_evolution_size_title      = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_size_evolution_title');
        $this->table_evolution_rate_size_title = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_size_evolution_rate_title');
        $this->no_data                         = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'data_top_projects_no_data');

        $this->order_is_start_size     = $order === 'start_size';
        $this->order_is_end_size       = $order === 'end_size';
        $this->order_is_evolution      = $order === 'evolution';
        $this->order_is_evolution_rate = $order === 'evolution_rate';

        $nb_displayed     = $offset + $limit > $nb_projects ? $nb_projects - $offset : $limit;
        $this->pagination = new PaginationPresenter(
            $limit,
            $offset,
            $nb_displayed,
            $nb_projects,
            '/plugins/statistics/disk_usage.php',
            array(
                'menu' => 'top_projects',
                ''     => $search_fields->fields_values_url
            )
        );
    }
}