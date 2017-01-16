<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

class AdminHeaderPresenter
{
    const TEMPLATE = 'admin-header';

    public $title;

    public $frequencies_tab_label;
    public $disk_usage_tab_label;
    public $project_quota_tab_label;

    public $frequencies_tab_active;
    public $disk_usage_tab_active;
    public $project_quota_tab_active;
    public $data_export_tab_active;

    public $disk_usage_services_menu_span_label;
    public $disk_usage_services_menu_label;
    public $disk_usage_projects_menu_label;
    public $disk_usage_users_menu_span_label;
    public $disk_usage_users_top_users_menu_label;
    public $disk_usage_users_user_details_label;
    public $disk_usage_global_menu_span_label;
    public $disk_usage_global_menu_label;
    public $project_over_quota_tab_label;
    public $all_project_quota_tab_label;

    public function __construct(
        $title,
        $active_tab
    ) {
        $this->title = $title;

        $this->frequencies_tab_active    = ($active_tab === 'frequencies');
        $this->disk_usage_tab_active     = ($active_tab === 'disk_usage');
        $this->project_quota_tab_active  = ($active_tab === 'project_quota');
        $this->service_usage_tab_active  = ($active_tab === 'service_usage');
        $this->data_export_tab_active    = ($active_tab === 'data_export');

        $this->frequencies_tab_label        = $GLOBALS['Language']->getText('plugin_statistics', 'frequencies_title');
        $this->disk_usage_tab_label         = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'show_statistics');
        $this->project_quota_tab_label      = $GLOBALS['Language']->getText('plugin_statistics', 'quota_title');
        $this->all_project_quota_tab_label  = $GLOBALS['Language']->getText('plugin_statistics', 'project_quota_tab_label');
        $this->project_over_quota_tab_label = $GLOBALS['Language']->getText('plugin_statistics', 'project_over_quota_tab_label');
        $this->data_export_tab_label        = $GLOBALS['Language']->getText('plugin_statistics', 'data_export_title');

        $this->disk_usage_services_menu_span_label   = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_services_menu_span_label');
        $this->disk_usage_services_menu_label        = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_services_menu_label');
        $this->disk_usage_projects_menu_label        = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_projects_menu_label');
        $this->disk_usage_users_menu_span_label      = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_users_menu_span_label');
        $this->disk_usage_users_top_users_menu_label = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_users_top_users_menu_label');
        $this->disk_usage_users_user_details_label   = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_users_user_details_label');
        $this->disk_usage_global_menu_span_label     = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_global_menu_span_label');
        $this->disk_usage_global_menu_label          = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_global_menu_label');
    }
}
