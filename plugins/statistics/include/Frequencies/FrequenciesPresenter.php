<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\Statistics\Frequencies;

use Tuleap\Statistics\AdminHeaderPresenter;

class FrequenciesPresenter
{
    public const TEMPLATE = 'frequencies';

    public $header;
    public $search_fields;
    public $graph_url;
    public $frequencies_label;

    public function __construct(
        AdminHeaderPresenter $header,
        FrequenciesSearchFieldsPresenter $search_fields,
        $datastr,
        $startdate,
        $enddate,
        $filter
    ) {
        $this->header        = $header;
        $this->search_fields = $search_fields;

        $this->frequencies_label = $GLOBALS['Language']->getText('plugin_statistics', 'frequencies_title');

        $this->graph_url = 'frequence_stat_graph.php?year=&month=&day=' .
                                                    '&data=' . urlencode($datastr) .
                                                    '&advsrch=1' .
                                                    '&start=' . urlencode($startdate) .
                                                    '&end=' . urlencode($enddate) .
                                                    '&filter=' . urlencode($filter);
    }
}
