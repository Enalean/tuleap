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

namespace Tuleap\Timesheeting;

use Codendi_Request;
use Feedback;
use PFUser;
use Tracker;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use TrackerFactory;
use Tuleap\Timesheeting\Admin\AdminController;
use Tuleap\Timesheeting\Time\TimeController;

class Router
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var AdminController
     */
    private $admin_controller;

    /**
     * @var TimeController
     */
    private $time_controller;

    public function __construct(
        TrackerFactory $tracker_factory,
        Tracker_ArtifactFactory $artifact_factory,
        AdminController $admin_controller,
        TimeController $time_controller
    ) {
        $this->tracker_factory  = $tracker_factory;
        $this->artifact_factory = $artifact_factory;
        $this->admin_controller = $admin_controller;
        $this->time_controller  = $time_controller;
    }

    public function route(Codendi_Request $request)
    {
        $user       = $request->getCurrentUser();
        $action     = $request->get('action');

        switch ($action) {
            case "admin-timesheeting":
                $tracker = $this->getTrackerFromRequest($request, $user);

                $this->admin_controller->displayAdminForm($tracker);

                break;
            case "edit-timesheeting":
                $tracker = $this->getTrackerFromRequest($request, $user);

                $this->admin_controller->editTimesheetingAdminSettings($tracker, $request);

                $this->redirectToTimesheetingAdminPage($tracker);
                break;
            case "add-time":
                $artifact = $this->getArtifactFromRequest($request, $user);
                $this->time_controller->addTimeForUser($request, $user, $artifact);

                break;
            case "delete-time":
                $artifact = $this->getArtifactFromRequest($request, $user);
                $this->time_controller->deleteTimeForUser($request, $user, $artifact);

                break;
            case "edit-time":
                $artifact = $this->getArtifactFromRequest($request, $user);
                $this->time_controller->editTimeForUser($request, $user, $artifact);

                break;
            default:
                $this->redirectToTuleapHomepage();

                break;
        }
    }

    /**
     * @return Tracker
     */
    private function getTrackerFromRequest(Codendi_Request $request, PFUser $user)
    {
        $tracker_id = $request->get('tracker');
        $tracker    = $this->tracker_factory->getTrackerById($tracker_id);

        if (! $tracker) {
            $this->redirectToTuleapHomepage();
        }

        if (! $tracker->userIsAdmin($user)) {
            $this->redirectToTrackerHomepage($tracker_id);
        }

        return $tracker;
    }

    /**
     * @return Tracker_Artifact
     */
    private function getArtifactFromRequest(Codendi_Request $request, PFUser $user)
    {
        $artifact_id = $request->get('artifact');
        $artifact    = $this->artifact_factory->getArtifactById($artifact_id);

        if (! $artifact || ! $artifact->userCanView($user)) {
            $this->redirectToTuleapHomepage();
        }

        return $artifact;
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

    private function redirectToTimesheetingAdminPage(Tracker $tracker)
    {
        $url = TIMESHEETING_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $tracker->getId(),
                'action' => 'admin-timesheeting'
        ));

        $GLOBALS['Response']->redirect($url);

    }

    private function redirectToTuleapHomepage()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-timesheeting', 'The request is not valid.')
        );

        $GLOBALS['Response']->redirect('/');
    }
}
