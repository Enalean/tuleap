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

class DiskUsageProjectsPresenter
{
    public $header;

    public $search_fields;
    public $data_projects;
    public $nb_projects;
    public $start_size_url;
    public $end_size_url;
    public $evolution_url;
    public $evolution_rate_url;

    public $pane_title;
    public $table_rank_title;
    public $table_name_title;
    public $table_start_size_title;
    public $table_end_size_title;
    public $table_evolution_size_title;
    public $table_evolution_rate_size_title;
    public $table_project_details_title;
    public $no_data;

    public $pagination;

    public function __construct(
        AdminHeaderPresenter $header,
        DiskUsageProjectsSearchFieldsPresenter $search_fields,
        array $data_projects,
        $nb_projects,
        $order,
        $offset,
        $limit
    ) {
        $page = '/plugins/statistics/disk_usage.php';
        $menu = 'projects';

        $this->header        = $header;
        $this->search_fields = $search_fields;
        $this->data_projects = $data_projects;
        $this->nb_projects   = $nb_projects;

        $this->start_size_url     = $page . '?' . http_build_query($this->buildUrlParams($menu, 'start_size'));
        $this->end_size_url       = $page . '?' . http_build_query($this->buildUrlParams($menu, 'end_size'));
        $this->evolution_url      = $page . '?' . http_build_query($this->buildUrlParams($menu, 'evolution'));
        $this->evolution_rate_url = $page . '?' . http_build_query($this->buildUrlParams($menu, 'evolution_rate'));

        $this->pane_title                      = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'data_projects_pane_title');
        $this->table_rank_title                = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_rank_title');
        $this->table_name_title                = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_name_title');
        $this->table_start_size_title          = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_start_size_title');
        $this->table_end_size_title            = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_end_size_title');
        $this->table_evolution_size_title      = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_size_evolution_title');
        $this->table_evolution_rate_size_title = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_size_evolution_rate_title');
        $this->table_project_details_title     = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_project_details_title');
        $this->no_data                         = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'data_projects_no_data');

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
            $page,
            $this->buildUrlParams($menu, $order)
        );
    }

    private function buildUrlParams($menu, $order)
    {
        $params = array(
            'menu'  => $menu,
            'order' => $order
        );
        return array_merge($this->search_fields->fields_values_url, $params);
    }
}
