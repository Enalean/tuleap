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

class FrequenciesSearchFieldsPresenter
{
    public $title;
    public $type_label;
    public $type_values;
    public $start_date_label;
    public $end_date_label;
    public $start_date_value;
    public $end_date_value;
    public $filter_label;
    public $filter_values;
    public $search;

    public function __construct(
        array $type_values,
        array $filter_values,
        $start_date_value,
        $end_date_value
    ) {
        $this->type_values      = $type_values;
        $this->start_date_value = $start_date_value;
        $this->end_date_value   = $end_date_value;
        $this->filter_values    = $filter_values;

        $this->title            = $GLOBALS['Language']->getText('global', 'search_title');
        $this->type_label       = dgettext('tuleap-statistics', 'Type');
        $this->start_date_label = dgettext('tuleap-statistics', 'Start date');
        $this->end_date_label   = dgettext('tuleap-statistics', 'End date');
        $this->filter_label     = dgettext('tuleap-statistics', 'Filter');
        $this->search           = $GLOBALS['Language']->getText('global', 'btn_search');
    }
}
