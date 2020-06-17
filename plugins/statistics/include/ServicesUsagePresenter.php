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

class ServicesUsagePresenter
{
    public $start_date_label;
    public $end_date_label;
    public $csv_export_button;
    public $services_usage_start_date;
    public $services_usage_end_date;

    public function __construct($start_date, $end_date)
    {
        $this->service_usage_label = dgettext('tuleap-statistics', 'Service usage');
        $this->start_date_label    = dgettext('tuleap-statistics', 'Start date');
        $this->end_date_label      = dgettext('tuleap-statistics', 'End date');
        $this->csv_export_button   = dgettext('tuleap-statistics', 'Export CSV');

        $this->services_usage_start_date = $start_date;
        $this->services_usage_end_date   = $end_date;
    }
}
