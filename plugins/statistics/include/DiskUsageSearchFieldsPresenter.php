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

class DiskUsageSearchFieldsPresenter
{
    public $title;
    public $services_label;
    public $start_date_label;
    public $end_date_label;
    public $search;

    public $service_values;
    public $fields_values_url;
    public $start_date_value;
    public $end_date_value;

    public function __construct(
        array $service_values,
        array $fields_values_url,
        $start_date_value,
        $end_date_value
    ) {
        $this->service_values     = $service_values;
        $this->fields_values_url  = $fields_values_url;
        $this->start_date_value   = $start_date_value;
        $this->end_date_value     = $end_date_value;

        $this->title            = $GLOBALS['Language']->getText('global', 'search_title');
        $this->services_label   = dgettext('tuleap-statistics', 'Services');
        $this->start_date_label = dgettext('tuleap-statistics', 'Start date');
        $this->end_date_label   = dgettext('tuleap-statistics', 'End date');
        $this->search           = $GLOBALS['Language']->getText('global', 'btn_search');
    }
}
