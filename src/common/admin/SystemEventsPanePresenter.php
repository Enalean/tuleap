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

namespace Tuleap\Admin\SystemEvents;

class HomepagePanePresenter
{
    const TEMPLATE = 'homepage_pane';

    public $sections;
    public $pane_title;
    public $view_all_label;

    public function __construct(array $sections)
    {
        $this->sections       = $sections;

        $this->pane_title     = $GLOBALS['Language']->getText('admin_main', 'system_events_pane_title');
        $this->view_all_label = $GLOBALS['Language']->getText('admin_main', 'system_events_view_all_label');
    }
}
