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

namespace Tuleap\Timesheeting\Admin;

use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use TemplateRendererFactory;
use Tracker;
use TrackerManager;

class AdminController
{
    /**
     * @var TrackerManager
     */
    private $tracker_manager;

    /**
     * @var TimesheetingEnabler
     */
    private $enabler;

    public function __construct(
        TrackerManager $tracker_manager,
        TimesheetingEnabler $enabler,
        CSRFSynchronizerToken $csrf
    ) {
        $this->tracker_manager = $tracker_manager;
        $this->enabler         = $enabler;
        $this->csrf            = $csrf;
    }

    public function displayAdminForm(Tracker $tracker)
    {
        $renderer  = TemplateRendererFactory::build()->getRenderer(TIMESHEETING_TEMPLATE_DIR);
        $presenter = new AdminPresenter(
            $tracker,
            $this->csrf,
            $this->enabler->isTimesheetingEnabledForTracker($tracker)
        );

        $tracker->displayAdminItemHeader(
            $this->tracker_manager,
            'timesheeting'
        );

        $renderer->renderToPage(
            'tracker-admin',
            $presenter
        );

        $tracker->displayFooter($this->tracker_manager);
    }

    public function editTimesheetingAdminSettings(Tracker $tracker, Codendi_Request $request)
    {
        $this->csrf->check();

        if ($request->get('enable_timesheeting') && ! $this->enabler->isTimesheetingEnabledForTracker($tracker)) {
            $this->enabler->enableTimesheetingForTracker($tracker);

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-timesheeting', 'Timesheeting is enabled for tracker.')
            );
        } elseif (! $request->get('enable_timesheeting') && $this->enabler->isTimesheetingEnabledForTracker($tracker)) {
            $this->enabler->disableTimesheetingForTracker($tracker);

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-timesheeting', 'Timesheeting is disabled for tracker.')
            );
        }
    }
}