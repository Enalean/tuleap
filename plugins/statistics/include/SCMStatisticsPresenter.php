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
    public const TEMPLATE = 'scm-statistics';

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

        $this->scm_statistics_label = dgettext('tuleap-statistics', 'SCM statistics');
        $this->start_date_label     = dgettext('tuleap-statistics', 'Start date');
        $this->end_date_label       = dgettext('tuleap-statistics', 'End date');
        $this->project_label        = dgettext('tuleap-statistics', 'Project');
        $this->project_placeholder  = dgettext('tuleap-statistics', 'MyProject');
        $this->project_help         = dgettext('tuleap-statistics', 'Project Id is optional, you can leave it empty to calculate statistics for the whole platform.');
        $this->csv_export_button    = dgettext('tuleap-statistics', 'Export CSV');
    }
}
