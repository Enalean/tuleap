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

class DiskUsageTopUsersPresenter
{
    public $header;
    public $data_top_users;
    public $end_date_label;
    public $end_date_value;
    public $search_title;
    public $search;
    public $top_users_pane_title;
    public $table_rank_title;
    public $table_id_title;
    public $table_name_title;
    public $table_end_size_title;

    public function __construct(
        AdminHeaderPresenter $header,
        $end_date_value,
        array $data_top_users
    ) {
        $this->header         = $header;
        $this->end_date_value = $end_date_value;
        $this->data_top_users = $data_top_users;

        $this->search_title                = $GLOBALS['Language']->getText('admin_main', 'search');
        $this->end_date_label              = $GLOBALS['Language']->getText('plugin_statistics', 'end_date');
        $this->search                      = $GLOBALS['Language']->getText('admin_main', 'search');
        $this->top_users_pane_title        = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'top_users_pane_title');
        $this->table_rank_title            = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_rank_title');
        $this->table_name_title            = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_name_title');
        $this->table_end_size_title        = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'table_end_size_title');
        $this->no_data                     = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'no_top_users_data');
        $this->disk_usage_user_details_btn = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_user_details_btn');
    }
}
