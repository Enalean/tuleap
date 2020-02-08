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
use PFUser;
use Tracker_Artifact;
use Tuleap\Timetracking\Exceptions\TimeTrackingMissingTimeException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToDeleteException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToAddException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToEditException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotBelongToUserException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNoTimeException;

class TimeController
{
    /**
     * @var TimeUpdater
     */
    private $time_updater;

    /**
     * @var TimeRetriever
     */
    private $time_retriever;

    public function __construct(
        TimeUpdater $time_updater,
        TimeRetriever $time_retriever
    ) {
        $this->time_updater          = $time_updater;
        $this->time_retriever        = $time_retriever;
    }

    /**
     * @throws TimeTrackingMissingTimeException
     * @throws TimeTrackingNotAllowedToAddException
     * @throws \Tuleap\Timetracking\Exceptions\TimeTrackingBadTimeFormatException
     */
    public function addTimeForUser(
        Codendi_Request $request,
        PFUser $user,
        Tracker_Artifact $artifact,
        CSRFSynchronizerToken $csrf
    ) {
        $csrf->check();

        $added_step = $request->get('timetracking-new-time-step');
        $added_time = $request->get('timetracking-new-time-time');
        $added_date = $request->get('timetracking-new-time-date') ?: date('Y-m-d', $_SERVER['REQUEST_TIME']);

        $this->time_updater->addTimeForUserInArtifact($user, $artifact, $added_date, $added_time, $added_step);
    }

    /**
     * @throws TimeTrackingNoTimeException
     * @throws TimeTrackingNotAllowedToDeleteException
     * @throws TimeTrackingNotBelongToUserException
     */
    public function deleteTimeForUser(
        Codendi_Request $request,
        PFUser $user,
        Tracker_Artifact $artifact,
        CSRFSynchronizerToken $csrf
    ) {
        $csrf->check();

        $time = $this->getTimeFromRequest($request, $user);

        $this->time_updater->deleteTime($user, $artifact, $time);
    }

    /**
     * @throws TimeTrackingMissingTimeException
     * @throws TimeTrackingNoTimeException
     * @throws TimeTrackingNotAllowedToEditException
     * @throws TimeTrackingNotBelongToUserException
     * @throws \Tuleap\Timetracking\Exceptions\TimeTrackingBadTimeFormatException
     */
    public function editTimeForUser(
        Codendi_Request $request,
        PFUser $user,
        Tracker_Artifact $artifact,
        CSRFSynchronizerToken $csrf
    ) {
        $csrf->check();

        $time = $this->getTimeFromRequest($request, $user);

        $updated_step = $request->get('timetracking-edit-time-step');
        $updated_time = $request->get('timetracking-edit-time-time');
        $updated_date = $request->get('timetracking-edit-time-date') ?: date('Y-m-d', $_SERVER['REQUEST_TIME']);

        $this->time_updater->updateTime($user, $artifact, $time, $updated_date, $updated_time, $updated_step);
    }

    /**
     * @return Time
     * @throws TimeTrackingNoTimeException
     */
    private function getTimeFromRequest(Codendi_Request $request, PFUser $user)
    {
        $time_id = $request->get('time-id');
        $time    = $this->time_retriever->getTimeByIdForUser($user, $time_id);

        if (! $time) {
            throw new TimeTrackingNoTimeException();
        }

        return $time;
    }
}
