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

class SCMStatisticsPresenter
{
    const TEMPLATE = 'scm-statistics';

    public $scm_statistics_label;
    public $start_date_label;
    public $end_date_label;
    public $project_label;
    public $project_placeholder;
    public $csv_export_button;

    public $scm_statistics_selected_project;
    public $scm_statistics_start_date;
    public $scm_statistics_end_date;

    public function __construct(
        $selected_project,
        $start_date,
        $end_date
    ) {
        $this->scm_statistics_start_date       = $start_date;
        $this->scm_statistics_end_date         = $end_date;
        $this->scm_statistics_selected_project = $selected_project;

        $this->scm_statistics_label = $GLOBALS['Language']->getText('plugin_statistics', 'scm_title');
        $this->start_date_label     = $GLOBALS['Language']->getText('plugin_statistics', 'start_date');
        $this->end_date_label       = $GLOBALS['Language']->getText('plugin_statistics', 'end_date');
        $this->project_label        = $GLOBALS['Language']->getText('plugin_statistics', 'project_label');
        $this->project_placeholder  = $GLOBALS['Language']->getText('plugin_statistics', 'project_placeholder');
        $this->project_help         = $GLOBALS['Language']->getText('plugin_statistics', 'scm_project_id_info');
        $this->csv_export_button    = $GLOBALS['Language']->getText('plugin_statistics', 'csv_export_button');
    }
}
