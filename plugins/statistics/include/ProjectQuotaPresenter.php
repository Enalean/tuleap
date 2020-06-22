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

        $this->frequencies_tab_label   = dgettext('tuleap-statistics', 'Frequencies');
        $this->disk_usage_tab_label    = dgettext('tuleap-statistics', 'Disk usage');
        $this->project_quota_tab_label = dgettext('tuleap-statistics', 'Project quota');
        $this->service_usage_tab_label = dgettext('tuleap-statistics', 'Service usage');
        $this->filter_label            = $GLOBALS['Language']->getText('global', 'search_title');
        $this->search_label            = $GLOBALS['Language']->getText('global', 'btn_search');
        $this->project_label           = dgettext('tuleap-statistics', 'Project');
        $this->motivation_label        = dgettext('tuleap-statistics', 'Motivation');
        $this->quota_label             = dgettext('tuleap-statistics', 'Quota');
        $this->requester_label         = dgettext('tuleap-statistics', 'Requester');
        $this->project_placeholder     = dgettext('tuleap-statistics', 'MyProject');
        $this->no_motivation           = dgettext('tuleap-statistics', 'No motivation');
        $this->add_quota_label         = dgettext('tuleap-statistics', 'Add quota');
        $this->delete_quota_label      = dgettext('tuleap-statistics', 'Delete quota');
        $this->details_label           = dgettext('tuleap-statistics', 'Details');
        $this->project_quota_label     = dgettext('tuleap-statistics', 'Project quota');
        $this->motivation_placeholder  = dgettext('tuleap-statistics', 'Comment...');
        $this->quota_unit              = dgettext('tuleap-statistics', 'GB');
        $this->max_quota_label         = sprintf(dgettext('tuleap-statistics', 'Maximum quota is %1$s GB'), $max_quota);
        $this->empty_filter_results    = dgettext('tuleap-statistics', 'There isn\'t any quota for the selected project');

        $this->cancel_label      = $GLOBALS['Language']->getText('global', 'btn_cancel');
        $this->close_label       = $GLOBALS['Language']->getText('global', 'btn_close');
        $this->project_column    = $GLOBALS['Language']->getText('global', 'Project');
        $this->delete_label      = dgettext('tuleap-statistics', 'Delete');
        $this->requester_column  = dgettext('tuleap-statistics', 'Requester');
        $this->quota_column      = dgettext('tuleap-statistics', 'Quota');
        $this->motivation_column = dgettext('tuleap-statistics', 'Motivation');
        $this->date_column       = dgettext('tuleap-statistics', 'Date');
        $this->empty_state       = sprintf(dgettext('tuleap-statistics', 'There isn\'t any projects with a custom quota. All projects have the default quota (%1$s GB).'), $default_quota);
    }
}
