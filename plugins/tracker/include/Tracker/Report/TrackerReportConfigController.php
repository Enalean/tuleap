<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
    public const int EXPERT_QUERY_LIMIT_MAX = 80;

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
        AdminPageRenderer $admin_page_rendered,
    ) {
        $this->config              = $config;
        $this->admin_page_rendered = $admin_page_rendered;
    }

    public function display(CSRFSynchronizerToken $csrf_token)
    {
        $title = dgettext('tuleap-tracker', 'Trackers');

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
                $response->addFeedback(Feedback::INFO, dgettext('tuleap-tracker', 'Successfully updated.'));
            } else {
                $response->addFeedback(Feedback::ERROR, dgettext('tuleap-tracker', 'The limit value is incorrect.'));
            }
        } else {
            $response->addFeedback(Feedback::ERROR, dgettext('tuleap-tracker', 'The limit value is incorrect.'));
        }

        $response->redirect($_SERVER['REQUEST_URI']);
    }
}
