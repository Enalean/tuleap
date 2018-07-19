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

class TimeChecker
{
    const PATTERN = '^[0-9]{2}[:][0-9]{2}$';

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
        $pattern = "/" . self::PATTERN . "/";
        if (! $time_value) {
            throw new TimeTrackingMissingTimeException(dgettext('tuleap-timetracking', "The time is missing"));
        } else if (! preg_match($pattern, $time_value)) {
            throw new TimeTrackingBadTimeFormatException();
        }
    }
}
