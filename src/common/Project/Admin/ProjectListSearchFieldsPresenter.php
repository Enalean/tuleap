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

namespace Tuleap\Project\Admin;

class ProjectListSearchFieldsPresenter
{
    public $name;
    public $name_label;
    public $status_label;
    public $status_values;
    public $search;

    public function __construct($name, $status_values)
    {
        $this->name       = $name;
        $this->name_label = $GLOBALS['Language']->getText('admin_projectlist', 'filter_name');

        $this->status_label  = $GLOBALS['Language']->getText('admin_projectlist', 'status');
        $this->status_values = $status_values;

        $this->title  = $GLOBALS['Language']->getText('global', 'search_title');
        $this->search = $GLOBALS['Language']->getText('global', 'btn_search');
    }
}
