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

namespace Tuleap\Timesheeting;

use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use TemplateRendererFactory;
use TrackerFactory;
use TrackerManager;
use Tuleap\Timesheeting\Admin\AdminPresenter;
use Tuleap\Timesheeting\Admin\TimesheetingEnabler;

class Router
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var TrackerManager
     */
    private $tracker_manager;

    /**
     * @var TimesheetingEnabler
     */
    private $enabler;

    public function __construct(
        TrackerFactory $tracker_factory,
        TrackerManager $tracker_manager,
        TimesheetingEnabler $enabler
    ) {
        $this->tracker_factory = $tracker_factory;
        $this->tracker_manager = $tracker_manager;
        $this->enabler         = $enabler;
    }

    public function route(Codendi_Request $request)
    {
        $tracker_id = $request->get('tracker');
        $tracker    = $this->tracker_factory->getTrackerById($tracker_id);

        if (! $tracker) {
            $this->redirectToTuleapHomepage();
        }

        $user   = $request->getCurrentUser();
        $action = $request->get('action');
        $csrf   = new CSRFSynchronizerToken($this->getTrackerAdminUrl($tracker_id));

        switch ($action) {
            case "admin-timesheeting":
                if (! $tracker->userIsAdmin($user)) {
                    $this->redirectToTrackerHomepage($tracker_id);
                }

                $renderer  = TemplateRendererFactory::build()->getRenderer(TIMESHEETING_TEMPLATE_DIR);
                $presenter = new AdminPresenter(
                    $tracker,
                    $csrf,
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

                break;
            case "edit-timesheeting":
                if (! $tracker->userIsAdmin($user)) {
                    $this->redirectToTrackerHomepage($tracker_id);
                }

                $csrf->check();

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

                $this->redirectToTimesheetingAdminPage($tracker_id);
                break;
            default:
                $this->redirectToTrackerAdminPage($tracker_id);

                break;
        }
    }

    private function redirectToTuleapHomepage()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-timesheeting', 'The request is not valid.')
        );

        $GLOBALS['Response']->redirect('/');
    }

    private function redirectToTrackerHomepage($tracker_id)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-timesheeting', "Access denied. You don't have permissions to perform this action.")
        );

        $url = TRACKER_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $tracker_id
        ));

        $GLOBALS['Response']->redirect($url);
    }

    private function redirectToTrackerAdminPage($tracker_id)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-timesheeting', 'The request is not valid.')
        );

        $url = $this->getTrackerAdminUrl($tracker_id);

        $GLOBALS['Response']->redirect($url);
    }

    private function redirectToTimesheetingAdminPage($tracker_id)
    {
        $url = TIMESHEETING_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $tracker_id,
                'action' => 'admin-timesheeting'
            ));

        $GLOBALS['Response']->redirect($url);

    }

    private function getTrackerAdminUrl($tracker_id)
    {
        return  TRACKER_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $tracker_id,
                'func' => 'admin'
        ));
    }
}