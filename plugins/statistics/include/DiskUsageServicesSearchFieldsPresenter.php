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

class DiskUsageServicesSearchFieldsPresenter extends DiskUsageSearchFieldsPresenter
{
    public $group_by_label;
    public $relative_y_axis_label;

    public $group_by_values;
    public $relative_y_axis_value;

    public function __construct(
        array $service_values,
        $fields_values_url,
        $group_by_values,
        $start_date_value,
        $end_date_value,
        $relative_y_axis_value
    ) {
        parent::__construct($service_values, $fields_values_url, $start_date_value, $end_date_value);

        $this->group_by_values       = $group_by_values;
        $this->relative_y_axis_value = $relative_y_axis_value;

        $this->group_by_label        = $GLOBALS['Language']->getText('plugin_statistics', 'group_by_label');
        $this->relative_y_axis_label = $GLOBALS['Language']->getText('plugin_statistics', 'relative_y_axis_label');
    }
}
