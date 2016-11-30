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

class AdminHeaderPresenter
{
    const TEMPLATE = 'admin-header';

    public $title;

    public $frequencies_tab_label;
    public $disk_usage_tab_label;
    public $project_quota_tab_label;
    public $service_usage_tab_label;
    public $scm_statistics_tab_label;
    public $usage_progress_tab_label;

    public $frequencies_tab_active;
    public $disk_usage_tab_active;
    public $project_quota_tab_active;
    public $service_usage_tab_active;
    public $scm_statistics_tab_active;
    public $usage_progress_tab_active;

    public $disk_usage_services_menu_span_label;
    public $disk_usage_services_menu_label;
    public $disk_usage_projects_menu_label;
    public $disk_usage_users_menu_span_label;
    public $disk_usage_users_top_users_menu_label;
    public $disk_usage_users_user_details_label;
    public $disk_usage_global_menu_span_label;
    public $disk_usage_global_menu_label;

    public function __construct(
        $title,
        $active_tab
    ) {
        $this->title = $title;

        $this->frequencies_tab_active    = ($active_tab === 'frequencies');
        $this->disk_usage_tab_active     = ($active_tab === 'disk_usage');
        $this->project_quota_tab_active  = ($active_tab === 'project_quota');
        $this->service_usage_tab_active  = ($active_tab === 'service_usage');
        $this->scm_statistics_tab_active = ($active_tab === 'scm_statistics');
        $this->usage_progress_tab_active = ($active_tab === 'usage_progress');

        $this->frequencies_tab_label    = $GLOBALS['Language']->getText('plugin_statistics', 'frequencies_title');
        $this->disk_usage_tab_label     = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'show_statistics');
        $this->project_quota_tab_label  = $GLOBALS['Language']->getText('plugin_statistics', 'quota_title');
        $this->service_usage_tab_label  = $GLOBALS['Language']->getText('plugin_statistics', 'services_usage');
        $this->scm_statistics_tab_label = $GLOBALS['Language']->getText('plugin_statistics', 'scm_title');
        $this->usage_progress_tab_label = $GLOBALS['Language']->getText('plugin_statistics', 'usage_progress_title');

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
