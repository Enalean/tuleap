<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\Report;

use CSRFSynchronizerToken;
use Tuleap\Tracker\Config\SectionsPresenter;

class TrackerReportConfigPresenter
{
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    public $title;
    public $query_limit;
    public $sections;
    public $report_config_pane_title;
    public $report_config_desc;

    public function __construct(
        CSRFSynchronizerToken $csrf_token,
        $title,
        $query_limit
    ) {
        $this->csrf_token          = $csrf_token;
        $this->title               = $title;
        $this->query_limit         = $query_limit;
        $this->default_query_limit = 15;

        $this->report_config_pane_title = $GLOBALS['Language']->getText('plugin_tracker_report_config', 'report_config_pane_title');
        $this->report_config_desc       = $GLOBALS['Language']->getText('plugin_tracker_report_config', 'report_config_desc');
        $this->query_limit_label        = $GLOBALS['Language']->getText('plugin_tracker_report_config', 'query_limit_label');
        $this->save_conf                = $GLOBALS['Language']->getText('admin_main', 'save_conf');

        $this->sections = new SectionsPresenter();
    }
}
