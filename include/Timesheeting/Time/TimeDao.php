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

use DataAccess;
use DataAccessObject;

class TimeDao extends DataAccessObject
{

    public function __construct(DataAccess $da = null)
    {
        parent::__construct($da);

        $this->enableExceptionsOnError();
    }

    public function addTime($user_id, $artifact_id, $day, $minutes, $step)
    {
        $user_id     = $this->da->escapeInt($user_id);
        $artifact_id = $this->da->escapeInt($artifact_id);
        $day         = $this->da->quoteSmart($day);
        $minutes     = $this->da->escapeInt($minutes);
        $step        = $this->da->quoteSmart($step);

        $sql = "REPLACE INTO plugin_timesheeting_times (user_id, artifact_id, minutes, step, day)
                VALUES ($user_id, $artifact_id, $minutes, $step, $day)";

        return $this->update($sql);
    }
}
