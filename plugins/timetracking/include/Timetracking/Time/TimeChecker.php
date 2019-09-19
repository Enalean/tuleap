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
use DateTime;
use Tuleap\Timetracking\Exceptions\TimeTrackingBadTimeFormatException;
use Tuleap\Timetracking\Exceptions\TimeTrackingMissingTimeException;
use Tuleap\Timetracking\Exceptions\TimeTrackingBadDateFormatException;

class TimeChecker
{
    public const TIME_PATTERN = '^[0-9]{2}[:][0-9]{2}$';

    public function doesTimeBelongsToUser(Time $time, PFUser $user)
    {
        return $time->getUserId() !== (int) $user->getId();
    }

    /**
     * @throws TimeTrackingBadTimeFormatException
     * @throws TimeTrackingMissingTimeException
     */
    public function checkMandatoryTimeValue($time_value)
    {
        $pattern = "/" . self::TIME_PATTERN . "/";
        if (! $time_value) {
            throw new TimeTrackingMissingTimeException();
        } elseif (! preg_match($pattern, $time_value)) {
            throw new TimeTrackingBadTimeFormatException();
        }
    }

    public function checkDateFormat($date)
    {
        $date_checked = DateTime::createFromFormat('Y-m-d', $date);
        if (! $date_checked) {
            throw new TimeTrackingBadDateFormatException();
        }
    }
}
