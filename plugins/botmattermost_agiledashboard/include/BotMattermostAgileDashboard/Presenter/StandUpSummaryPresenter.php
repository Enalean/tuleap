<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\BotMattermostAgileDashboard\Presenter;

class StandUpSummaryPresenter
{
    public $last_plannings;
    public $project_name;

    public $quick_access_text;

    public $table_header_id;
    public $table_header_status_open;
    public $table_header_status_closed;
    public $table_header_days_remaining;
    public $table_header_title;
    public $table_header_status;
    public $table_header_last_update;

    public $no_update;
    public $no_current_plannings;
    public $no_current_milestones;

    public function __construct($last_plannings, $project_name, string $last_planning_name)
    {
        $this->last_plannings = $last_plannings;
        $this->project_name   = $project_name;

        $this->quick_access_text = dgettext('tuleap-botmattermost_agiledashboard', 'Quick access');

        $this->table_header_id             = dgettext('tuleap-botmattermost_agiledashboard', 'Artifact ID');
        $this->table_header_status_open    = dgettext('tuleap-botmattermost_agiledashboard', 'Open');
        $this->table_header_status_closed  = dgettext('tuleap-botmattermost_agiledashboard', 'Closed');
        $this->table_header_days_remaining = dgettext('tuleap-botmattermost_agiledashboard', 'Days Until End');
        $this->table_header_title          = dgettext('tuleap-botmattermost_agiledashboard', 'Title');
        $this->table_header_status         = dgettext('tuleap-botmattermost_agiledashboard', 'Status');
        $this->table_header_last_update    = dgettext('tuleap-botmattermost_agiledashboard', 'Last modification');

        $this->no_update             = dgettext('tuleap-botmattermost_agiledashboard', 'No recent update on');
        $this->no_current_plannings  = dgettext('tuleap-botmattermost_agiledashboard', 'No plannings in project');
        $this->no_current_milestones = sprintf(dgettext('tuleap-botmattermost_agiledashboard', 'No milestones in %1$s planning in project %2$s'), $last_planning_name, $project_name);
    }
}
