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

class FrequenciesSearchFieldsPresenter
{
    public $title;
    public $type_label;
    public $type_values;
    public $date_label;
    public $search;

    public function __construct(array $type_values, $date_value)
    {
        $this->type_values = $type_values;
        $this->date_value  = $date_value;

        $this->title      = $GLOBALS['Language']->getText('admin_main', 'search');
        $this->type_label = $GLOBALS['Language']->getText('plugin_statistics', 'type_label');
        $this->date_label = $GLOBALS['Language']->getText('plugin_statistics', 'date_label');
        $this->search     = $GLOBALS['Language']->getText('admin_main', 'search');
    }
}
