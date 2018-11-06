<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

class ProjectCrossTrackerSearchPresenter
{
    /** @var  int */
    public $report_id;
    public $date_format;
    /**
     * @var string
     */
    public $is_widget_admin;

    public function __construct($report_id, $is_admin)
    {
        $this->report_id       = $report_id;
        $this->date_format     = $GLOBALS['Language']->getText('system', 'datefmt_short');
        $this->is_widget_admin = $is_admin ? 'true' : 'false';
    }
}
