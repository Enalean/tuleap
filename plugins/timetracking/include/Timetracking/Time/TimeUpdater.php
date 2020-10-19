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

use PFUser;
use Tuleap\Timetracking\Exceptions\TimeTrackingBadTimeFormatException;
use Tuleap\Timetracking\Exceptions\TimeTrackingMissingTimeException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToAddException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToDeleteException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotAllowedToEditException;
use Tuleap\Timetracking\Exceptions\TimeTrackingNotBelongToUserException;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Tracker\Artifact\Artifact;

class TimeUpdater
{

    /**
     * @var TimeDao
     */
    private $time_dao;

    /**
     * @var TimeChecker
     */
    private $time_checker;

    /**
     * @var PermissionsRetriever
     */
    private $permissions_retriever;

    public function __construct(TimeDao $time_dao, TimeChecker $time_checker, PermissionsRetriever $permissions_retriever)
    {
        $this->time_dao              = $time_dao;
        $this->time_checker          = $time_checker;
        $this->permissions_retriever = $permissions_retriever;
    }

    /**
     * @throws TimeTrackingBadTimeFormatException
     * @throws TimeTrackingMissingTimeException
     * @throws TimeTrackingNotAllowedToAddException
     * @throws \Tuleap\Timetracking\Exceptions\TimeTrackingBadDateFormatException
     */
    public function addTimeForUserInArtifact(
        PFUser $user,
        Artifact $artifact,
        $added_date,
        $added_time,
        $added_step
    ) {
        if (! $this->permissions_retriever->userCanAddTimeInTracker($user, $artifact->getTracker())) {
            throw new TimeTrackingNotAllowedToAddException();
        }

        $this->time_checker->checkMandatoryTimeValue($added_time);
        $this->time_checker->checkDateFormat($added_date);

        $minutes = $this->getMinutes($added_time);

        $this->time_dao->addTime(
            $user->getId(),
            $artifact->getId(),
            $added_date,
            $minutes,
            $added_step
        );
    }

    /**
     * @throws TimeTrackingNotAllowedToDeleteException
     * @throws TimeTrackingNotBelongToUserException
     */
    public function deleteTime(PFUser $user, Artifact $artifact, Time $time)
    {
        if (! $this->permissions_retriever->userCanAddTimeInTracker($user, $artifact->getTracker())) {
            throw new TimeTrackingNotAllowedToDeleteException();
        }

        if ($this->time_checker->doesTimeBelongsToUser($time, $user)) {
            throw new TimeTrackingNotBelongToUserException();
        }

        $this->time_dao->deleteTime($time->getId());
    }

    /**
     * @throws TimeTrackingNotAllowedToEditException
     * @throws TimeTrackingNotBelongToUserException
     * @throws TimeTrackingBadTimeFormatException
     * @throws TimeTrackingMissingTimeException
     * @throws \Tuleap\Timetracking\Exceptions\TimeTrackingBadDateFormatException
     */
    public function updateTime(PFUser $user, Artifact $artifact, Time $time, $updated_date, $updated_time, $updated_step)
    {
        if (! $this->permissions_retriever->userCanAddTimeInTracker($user, $artifact->getTracker())) {
            throw new TimeTrackingNotAllowedToEditException();
        }

        $this->time_checker->checkMandatoryTimeValue($updated_time);
        $this->time_checker->checkDateFormat($updated_date);

        if ($this->time_checker->doesTimeBelongsToUser($time, $user)) {
            throw new TimeTrackingNotBelongToUserException();
        }

        $this->time_dao->updateTime(
            $time->getId(),
            $updated_date,
            $this->getMinutes($updated_time),
            $updated_step
        );
    }

    private function getMinutes($time_value)
    {
        $time_parts = explode(':', $time_value);

        return $time_parts[0] * 60 + $time_parts[1];
    }
}
