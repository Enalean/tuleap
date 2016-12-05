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

use CSRFSynchronizerToken;
use Tuleap\Layout\PaginationPresenter;

class ProjectQuotaPresenter
{
    public $header;
    public $frequencies_tab_label;
    public $disk_usage_tab_label;
    public $project_quota_tab_label;
    public $service_usage_tab_label;
    public $filter_label;
    public $search_label;
    public $project_label;
    public $project_placeholder;
    public $quotas;
    public $empty_state;
    public $motivation_column;
    public $quota_column;
    public $requester_column;
    public $project_column;
    public $has_quota;
    public $pagination;
    public $csrf;
    public $requester_label;
    public $no_motivation;
    public $add_quota_label;
    public $cancel_label;
    public $close_label;
    public $delete_label;
    public $motivation_placeholder;
    public $project_quota_label;
    public $details_label;
    public $delete_quota_label;
    public $quota_unit;
    public $max_quota_label;
    public $default_quota;
    public $selected_project;

    public function __construct(
        AdminHeaderPresenter $header,
        $selected_project,
        array $quotas,
        PaginationPresenter $pagination,
        $default_quota,
        $max_quota,
        CSRFSynchronizerToken $csrf
    ) {
        $this->header           = $header;
        $this->quotas           = $quotas;
        $this->pagination       = $pagination;
        $this->csrf             = $csrf;
        $this->default_quota    = $default_quota;
        $this->selected_project = $selected_project;

        $this->has_quota = count($quotas) > 0;

        $this->frequencies_tab_label   = $GLOBALS['Language']->getText('plugin_statistics', 'frequencies_title');
        $this->disk_usage_tab_label    = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'show_statistics');
        $this->project_quota_tab_label = $GLOBALS['Language']->getText('plugin_statistics', 'quota_title');
        $this->service_usage_tab_label = $GLOBALS['Language']->getText('plugin_statistics', 'services_usage');
        $this->filter_label            = $GLOBALS['Language']->getText('global', 'search_title');
        $this->search_label            = $GLOBALS['Language']->getText('global', 'btn_search');
        $this->project_label           = $GLOBALS['Language']->getText('plugin_statistics', 'project_label');
        $this->motivation_label        = $GLOBALS['Language']->getText('plugin_statistics', 'motivation');
        $this->quota_label             = $GLOBALS['Language']->getText('plugin_statistics', 'quota');
        $this->requester_label         = $GLOBALS['Language']->getText('plugin_statistics', 'requester');
        $this->project_placeholder     = $GLOBALS['Language']->getText('plugin_statistics', 'project_placeholder');
        $this->no_motivation           = $GLOBALS['Language']->getText('plugin_statistics', 'no_motivation');
        $this->add_quota_label         = $GLOBALS['Language']->getText('plugin_statistics', 'add_disk_quota');
        $this->delete_quota_label      = $GLOBALS['Language']->getText('plugin_statistics', 'delete_quota');
        $this->details_label           = $GLOBALS['Language']->getText('plugin_statistics', 'details');
        $this->project_quota_label     = $GLOBALS['Language']->getText('plugin_statistics', 'project_quota');
        $this->motivation_placeholder  = $GLOBALS['Language']->getText('plugin_statistics', 'comment');
        $this->quota_unit              = $GLOBALS['Language']->getText('plugin_statistics', 'quota_unit');
        $this->max_quota_label         = $GLOBALS['Language']->getText('plugin_statistics', 'max_quota', $max_quota);
        $this->empty_filter_results    = $GLOBALS['Language']->getText('plugin_statistics', 'empty_filter_results');

        $this->cancel_label      = $GLOBALS['Language']->getText('global', 'btn_cancel');
        $this->close_label       = $GLOBALS['Language']->getText('global', 'btn_close');
        $this->project_column    = $GLOBALS['Language']->getText('global', 'Project');
        $this->delete_label      = $GLOBALS['Language']->getText('plugin_statistics', 'delete');
        $this->requester_column  = $GLOBALS['Language']->getText('plugin_statistics', 'requester');
        $this->quota_column      = $GLOBALS['Language']->getText('plugin_statistics', 'quota');
        $this->motivation_column = $GLOBALS['Language']->getText('plugin_statistics', 'motivation');
        $this->date_column       = $GLOBALS['Language']->getText('plugin_statistics', 'date');
        $this->empty_state       = $GLOBALS['Language']->getText('plugin_statistics', 'no_projects', $default_quota);
    }
}
