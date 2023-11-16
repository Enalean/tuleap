<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use DateTime;
use Tuleap\Date\DateHelper;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;

final class DateTimeValueRounder
{
    public function getFlooredTimestampFromDateTime($value)
    {
        $date_value = $this->floorDateTime($value);
        if ($date_value === null) {
            return $this->getFlooredTimestampFromDate($value);
        }

        return $date_value->getTimestamp();
    }

    public function getFlooredTimestampFromDate($value)
    {
        $date_value = $this->floorDate($value);

        return $date_value->getTimestamp();
    }

    public function getCeiledTimestampFromDateTime($value)
    {
        $date_value = $this->floorDateTime($value);
        if ($date_value === null) {
            return $this->getCeiledTimestampFromDate($value);
        }
        $seconds_in_a_minute = 60;
        $timestamp_value     = $date_value->getTimestamp();

        return ($timestamp_value + $seconds_in_a_minute - 1);
    }

    public function getCeiledTimestampFromDate($value)
    {
        $date_value       = $this->floorDate($value);
        $seconds_in_a_day = DateHelper::SECONDS_IN_A_DAY;
        $timestamp_value  = $date_value->getTimestamp();

        return ($timestamp_value + $seconds_in_a_day - 1);
    }

    private function floorDateTime($value)
    {
        $date_value = DateTime::createFromFormat(DateFormat::DATETIME, $value);

        if ($date_value === false) {
            return null;
        }
        $date_value->setTime($date_value->format("H"), $date_value->format("i"), 0);

        return $date_value;
    }

    private function floorDate($value)
    {
        $date_value = DateTime::createFromFormat(DateFormat::DATE, $value);

        if ($date_value === false) {
            return null;
        }

        $date_value->setTime(0, 0, 0);

        return $date_value;
    }
}
