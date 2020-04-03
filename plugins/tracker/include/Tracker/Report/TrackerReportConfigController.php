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

use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use Response;
use Tuleap\Admin\AdminPageRenderer;
use Valid_UInt;

class TrackerReportConfigController
{
    public const EXPERT_QUERY_LIMIT_MAX = 80;

    /**
     * @var TrackerReportConfig
     */
    private $config;
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_rendered;

    public function __construct(
        TrackerReportConfig $config,
        AdminPageRenderer $admin_page_rendered
    ) {
        $this->config              = $config;
        $this->admin_page_rendered = $admin_page_rendered;
    }

    public function display(CSRFSynchronizerToken $csrf_token)
    {
        $title = $GLOBALS['Language']->getText('plugin_tracker_config', 'title');

        $this->admin_page_rendered->renderANoFramedPresenter(
            $title,
            TRACKER_TEMPLATE_DIR,
            'siteadmin-config/tracker-report-config',
            new TrackerReportConfigPresenter(
                $csrf_token,
                $title,
                $this->config->getExpertQueryLimit()
            )
        );
    }

    public function update(Codendi_Request $request, Response $response)
    {
        $query_limit       = null;
        $valid_query_limit = new Valid_UInt('query_limit');
        if ($request->valid($valid_query_limit)) {
            $query_limit = $request->get('query_limit');
            if (
                $query_limit
                && $query_limit <= self::EXPERT_QUERY_LIMIT_MAX
                && $this->config->setExpertQueryLimit($query_limit)
            ) {
                $response->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_tracker_report_config', 'successfully_updated'));
            } else {
                $response->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_tracker_report_config', 'add_error'));
            }
        } else {
            $response->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_tracker_report_config', 'add_error'));
        }

        $response->redirect($_SERVER['REQUEST_URI']);
    }
}
