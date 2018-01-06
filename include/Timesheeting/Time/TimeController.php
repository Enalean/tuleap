<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Timesheeting\Time;

use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use PFUser;
use Tracker_Artifact;
use Tuleap\Timesheeting\Permissions\PermissionsRetriever;

class TimeController
{
    /**
     * @var PermissionsRetriever
     */
    private $permissions_retriever;

    /**
     * @var TimeUpdater
     */
    private $time_updater;

    public function __construct(PermissionsRetriever $permissions_retriever, TimeUpdater $time_updater)
    {
        $this->permissions_retriever = $permissions_retriever;
        $this->time_updater          = $time_updater;
    }

    public function addTimeForUser(Codendi_Request $request, PFUser $user, Tracker_Artifact $artifact)
    {
        $csrf = new CSRFSynchronizerToken($artifact->getUri());
        $csrf->check();

        if (! $this->permissions_retriever->userCanAddTimeInTracker($user, $artifact->getTracker())) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-timesheeting',"You are not allowed to add a time.")
            );

            $this->redirectToArtifactView($artifact);
        }

        $added_step = $request->get('timesheeting-new-time-step');
        $added_time = $request->get('timesheeting-new-time-time');

        if (! $added_time) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-timesheeting',"The time is missing")
            );

            $this->redirectToArtifactViewInTimesheetingPane($artifact);
        }

        $this->time_updater->addTimeForUserInArtifact($user, $artifact, $added_time, $added_step);

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-timesheeting',"Time successfully added.")
        );

        $this->redirectToArtifactViewInTimesheetingPane($artifact);
    }

    private function redirectToArtifactViewInTimesheetingPane(Tracker_Artifact $artifact)
    {
        $url = TRACKER_BASE_URL . '/?' . http_build_query(array(
            'aid'  => $artifact->getId(),
            'view' => 'timesheeting'
        ));

        $GLOBALS['Response']->redirect($url);
    }

    private function redirectToArtifactView(Tracker_Artifact $artifact)
    {
        $url = TRACKER_BASE_URL . '/?' . http_build_query(array(
            'aid'  => $artifact->getId()
        ));

        $GLOBALS['Response']->redirect($url);
    }
}
