<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin;

class GlobalAdminPresenter
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $table_title;

    /**
     * @var string
     */
    public $switch_label;

    public function __construct()
    {
        $this->title        = dgettext('tuleap-tracker', 'Tracker global admininistration');
        $this->table_title  = dgettext('tuleap-tracker', 'Artifact links types');
        $this->switch_label = dgettext('tuleap-tracker', 'Activate artifact links types for all the trackers of this project?');
    }
}
