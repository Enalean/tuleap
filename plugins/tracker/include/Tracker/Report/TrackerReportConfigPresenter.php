<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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
use Tuleap\Tracker\Config\ReportPresenter;

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

        $this->report_config_pane_title = dgettext('tuleap-tracker', 'Report configuration');
        $this->report_config_desc       = dgettext('tuleap-tracker', 'Set a limit on expert queries to avoid too complex ones. Be careful, higher the limit is, higher the time to process the query by the server can be.');
        $this->query_limit_label        = dgettext('tuleap-tracker', 'Limit');
        $this->artifacts_deletion_label = dgettext('tuleap-tracker', 'Artifacts deletion');
        $this->save_conf                = $GLOBALS['Language']->getText('admin_main', 'save_conf');

        $this->sections = new ReportPresenter();
    }
}
