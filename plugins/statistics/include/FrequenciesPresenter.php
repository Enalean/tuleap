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

class FrequenciesPresenter
{
    const TEMPLATE = 'admin';

    public $title;
    public $search_fields;
    public $frequencies_tab_label;
    public $disk_usage_tab_label;
    public $project_quota_tab_label;
    public $service_usage_tab_label;
    public $graph_url;

    public function __construct(
        $title,
        FrequenciesSearchFieldsPresenter $search_fields,
        $year,
        $month,
        $day,
        $datastr,
        $advsrch,
        $startdate,
        $enddate,
        $filter
    ) {
        $this->title         = $title;
        $this->search_fields = $search_fields;

        $this->frequencies_tab_label = $GLOBALS['Language']->getText('plugin_statistics', 'frequencies_title');
        $this->disk_usage_tab_label = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'show_statistics');
        $this->project_quota_tab_label = $GLOBALS['Language']->getText('plugin_statistics', 'quota_title');
        $this->service_usage_tab_label = $GLOBALS['Language']->getText('plugin_statistics', 'services_usage');

        $this->graph_url = 'frequence_stat_graph.php?year='.urlencode($year).
                                                    '&month='.urlencode($month).
                                                    '&day='.urlencode($day).
                                                    '&data='.urlencode($datastr).
                                                    '&advsrch='.urlencode($advsrch).
                                                    '&start='.urlencode($startdate).
                                                    '&end='.urlencode($enddate).
                                                    '&filter='.urlencode($filter);
    }
}
