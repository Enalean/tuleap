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
use Tracker_Artifact;

class TimeChecker
{
    /*
    * @var TimeRetriever
    */
    private $time_retriever;

    public function __construct(TimeRetriever $time_retriever)
    {
        $this->time_retriever = $time_retriever;
    }

    public function doesTimeBelongsToUser(Time $time, PFUser $user)
    {
        return $time->getUserId() !== (int) $user->getId();
    }

    public function checkMandatoryTimeValue($time_value)
    {
        return ($time_value != null);
    }

    public function getExistingTimeForUserInArtifactAtGivenDate(PFUser $user, Tracker_Artifact $artifact, $date)
    {
        return $this->time_retriever->getExistingTimeForUserInArtifactAtGivenDate($user, $artifact, $date);
    }
}
