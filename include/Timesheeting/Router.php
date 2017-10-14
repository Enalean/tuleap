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
use Feedback;
use Tracker;
use TrackerFactory;
use TrackerManager;
use Tuleap\Timesheeting\Admin\AdminController;
use Tuleap\Timesheeting\Admin\TimesheetingEnabler;

class Router
{
    /**
     * @var AdminController
     */
    private $admin_controller;

    public function __construct(AdminController $admin_controller)
    {
        $this->admin_controller = $admin_controller;
    }

    public function route(Codendi_Request $request, Tracker $tracker)
    {
        $user       = $request->getCurrentUser();
        $action     = $request->get('action');
        $tracker_id = $tracker->getId();

        switch ($action) {
            case "admin-timesheeting":
                if (! $tracker->userIsAdmin($user)) {
                    $this->redirectToTrackerHomepage($tracker_id);
                }

                $this->admin_controller->displayAdminForm($tracker);

                break;
            case "edit-timesheeting":
                if (! $tracker->userIsAdmin($user)) {
                    $this->redirectToTrackerHomepage($tracker_id);
                }

                $this->admin_controller->editTimesheetingAdminSettings($tracker, $request);

                $this->redirectToTimesheetingAdminPage($tracker_id);
                break;
            default:
                $this->redirectToTrackerAdminPage($tracker_id);

                break;
        }
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

    private function redirectToTrackerAdminPage(Tracker $tracker)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-timesheeting', 'The request is not valid.')
        );

        $GLOBALS['Response']->redirect($tracker->getAdministrationUrl());
    }

    private function redirectToTimesheetingAdminPage($tracker_id)
    {
        $url = TIMESHEETING_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $tracker_id,
                'action' => 'admin-timesheeting'
        ));

        $GLOBALS['Response']->redirect($url);

    }
}