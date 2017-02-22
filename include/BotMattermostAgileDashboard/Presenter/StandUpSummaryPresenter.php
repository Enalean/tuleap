<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

    public function __construct($last_plannings, $project_name)
    {
        $this->last_plannings = $last_plannings;
        $this->project_name   = $project_name;

        $this->quick_access_text = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_quick_access');

        $this->table_header_id             = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_artifact_id');
        $this->table_header_status_open    = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_status_open');
        $this->table_header_status_closed  = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_status_closed');
        $this->table_header_days_remaining = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_days_remaining');
        $this->table_header_title          = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_artifact_title');
        $this->table_header_status         = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_artifact_status');
        $this->table_header_last_update    = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_artifact_last_modification');

        $this->no_update             = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_no_update');
        $this->no_current_plannings  = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_no_current_plannings');
        $this->no_current_milestones = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_no_current_milestones');
    }
}