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

namespace Tuleap\Timetracking\Time;

use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use PFUser;
use Tracker_Artifact;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;

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

    /**
     * @var TimeRetriever
     */
    private $time_retriever;

    public function __construct(
        PermissionsRetriever $permissions_retriever,
        TimeUpdater $time_updater,
        TimeRetriever $time_retriever
    ) {
        $this->permissions_retriever = $permissions_retriever;
        $this->time_updater          = $time_updater;
        $this->time_retriever        = $time_retriever;
    }

    public function addTimeForUser(Codendi_Request $request, PFUser $user, Tracker_Artifact $artifact)
    {
        $this->checkCsrf($artifact);

        if (! $this->permissions_retriever->userCanAddTimeInTracker($user, $artifact->getTracker())) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-timetracking', "You are not allowed to add a time.")
            );

            $this->redirectToArtifactView($artifact);
        }

        $added_step = $request->get('timetracking-new-time-step');
        $added_time = $request->get('timetracking-new-time-time');
        $added_date = $request->get('timetracking-new-time-date') ?: date('Y-m-d', $_SERVER['REQUEST_TIME']);

        $this->checkMandatoryTimeValue($artifact, $added_time);

        $this->checkExistingTimeForUserInArtifactAtGivenDate($user, $artifact, $added_date);

        $this->time_updater->addTimeForUserInArtifact($user, $artifact, $added_date, $added_time, $added_step);

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-timetracking', "Time successfully added.")
        );

        $this->redirectToArtifactViewInTimetrackingPane($artifact);
    }

    private function getExistingTimeForUserInArtifactAtGivenDate(PFUser $user, Tracker_Artifact $artifact, $date)
    {
        return $this->time_retriever->getExistingTimeForUserInArtifactAtGivenDate($user, $artifact, $date);
    }

    public function deleteTimeForUser(Codendi_Request $request, PFUser $user, Tracker_Artifact $artifact)
    {
        $this->checkCsrf($artifact);

        if (! $this->permissions_retriever->userCanAddTimeInTracker($user, $artifact->getTracker())) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-timetracking', "You are not allowed to delete a time.")
            );

            $this->redirectToArtifactView($artifact);
        }

        $time = $this->getTimeFromRequest($request, $user, $artifact);

        $this->checkTimeBelongsToUser($time, $user, $artifact);

        $this->time_updater->deleteTime($time);

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-timetracking', "Time successfully deleted.")
        );

        $this->redirectToArtifactViewInTimetrackingPane($artifact);
    }

    public function editTimeForUser(Codendi_Request $request, PFUser $user, Tracker_Artifact $artifact)
    {
        $this->checkCsrf($artifact);

        if (! $this->permissions_retriever->userCanAddTimeInTracker($user, $artifact->getTracker())) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-timetracking', "You are not allowed to edit this time.")
            );

            $this->redirectToArtifactView($artifact);
        }

        $time = $this->getTimeFromRequest($request, $user, $artifact);

        $this->checkTimeBelongsToUser($time, $user, $artifact);

        $updated_step = $request->get('timetracking-edit-time-step');
        $updated_time = $request->get('timetracking-edit-time-time');
        $updated_date = $request->get('timetracking-edit-time-date') ?: date('Y-m-d', $_SERVER['REQUEST_TIME']);

        $this->checkMandatoryTimeValue($artifact, $updated_time);

        if ($time->getDay() !== $updated_date) {
            $this->checkExistingTimeForUserInArtifactAtGivenDate($user, $artifact, $updated_date);
        }

        $this->time_updater->updateTime($time, $updated_date, $updated_time, $updated_step);

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-timetracking', "Time successfully updated.")
        );

        $this->redirectToArtifactViewInTimetrackingPane($artifact);
    }

    /**
     * @return Time
     */
    private function getTimeFromRequest(Codendi_Request $request, PFUser $user, Tracker_Artifact $artifact)
    {
        $time_id = $request->get('time-id');
        $time    = $this->time_retriever->getTimeByIdForUser($user, $time_id);

        if (! $time) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-timetracking', "Time not found.")
            );

            $this->redirectToArtifactViewInTimetrackingPane($artifact);
        }

        return $time;
    }

    private function checkCsrf(Tracker_Artifact $artifact)
    {
        $csrf = new CSRFSynchronizerToken($artifact->getUri());
        $csrf->check();
    }

    private function checkMandatoryTimeValue(Tracker_Artifact $artifact, $time_value)
    {
        if (! $time_value) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-timetracking', "The time is missing")
            );

            $this->redirectToArtifactViewInTimetrackingPane($artifact);
        }
    }

    private function redirectToArtifactViewInTimetrackingPane(Tracker_Artifact $artifact)
    {
        $url = TRACKER_BASE_URL . '/?' . http_build_query(array(
            'aid'  => $artifact->getId(),
            'view' => 'timetracking'
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

    private function checkExistingTimeForUserInArtifactAtGivenDate(PFUser $user, Tracker_Artifact $artifact, $date)
    {
        $existing_time = $this->getExistingTimeForUserInArtifactAtGivenDate($user, $artifact, $date);

        if ($existing_time) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-timetracking', "A time already exists for the day %s. Skipping."),
                    $existing_time->getDay()
                )
            );

            $this->redirectToArtifactViewInTimetrackingPane($artifact);
        }
    }

    private function checkTimeBelongsToUser(Time $time, PFUser $user, Tracker_Artifact $artifact)
    {
        if ($time->getUserId() !== $user->getId()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-timetracking', "This time does not belong to you.")
            );

            $this->redirectToArtifactViewInTimetrackingPane($artifact);
        }
    }
}
