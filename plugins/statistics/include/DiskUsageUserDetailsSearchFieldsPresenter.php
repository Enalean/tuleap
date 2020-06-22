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

class DiskUsageUserDetailsSearchFieldsPresenter
{
    public $title;
    public $user_id_label;
    public $group_by_label;
    public $start_date_label;
    public $end_date_label;
    public $search;

    public $user_name_value;
    public $group_by_values;
    public $start_date_value;
    public $end_date_value;

    public function __construct(
        $user_name_value,
        array $group_by_values,
        $start_date_value,
        $end_date_value
    ) {
        $this->user_name_value  = $user_name_value;
        $this->start_date_value = $start_date_value;
        $this->end_date_value   = $end_date_value;
        $this->group_by_values  = $group_by_values;

        $this->title                 = $GLOBALS['Language']->getText('global', 'search_title');
        $this->user_id_label         = dgettext('tuleap-statistics', 'User');
        $this->start_date_label      = dgettext('tuleap-statistics', 'Start date');
        $this->end_date_label        = dgettext('tuleap-statistics', 'End date');
        $this->search                = $GLOBALS['Language']->getText('global', 'btn_search');
        $this->group_by_label        = dgettext('tuleap-statistics', 'Group by');
    }
}
