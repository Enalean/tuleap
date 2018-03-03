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

use PFUser;
use Tracker_Artifact;

class TimeUpdater
{
    public function __construct(TimeDao $time_dao)
    {
        $this->time_dao = $time_dao;
    }

    public function addTimeForUserInArtifact(
        PFUser $user,
        Tracker_Artifact $artifact,
        $added_date,
        $added_time,
        $added_step
    ) {
        $minutes = $this->getMinutes($added_time);

        return $this->time_dao->addTime(
            $user->getId(),
            $artifact->getId(),
            $added_date,
            $minutes,
            $added_step
        );
    }

    public function deleteTime(Time $time)
    {
        return $this->time_dao->deleteTime($time->getId());
    }

    public function updateTime(Time $time, $updated_date, $updated_time, $updated_step)
    {
        return $this->time_dao->updateTime(
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
