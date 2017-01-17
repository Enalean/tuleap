<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\EqualComparison;

use DateTime;
use DateHelper;
use CodendiDataAccess;

class DateTimeConditionBuilder
{
    public function buildConditionForDateOrDateTime($value)
    {
        $format     = "Y-m-d H:i";
        $date_value = DateTime::createFromFormat($format, $value);

        if ($date_value !== false) {
            $date_value->setTime($date_value->format("H"), $date_value->format("i"), 0);
            $seconds_in_a_minute = 60;
            $timestamp_value     = $this->escapeTimestamp($date_value);

            return "BETWEEN $timestamp_value AND $timestamp_value + $seconds_in_a_minute - 1";
        } else {
            $format           = "Y-m-d";
            $date_value       = DateTime::createFromFormat($format, $value);
            $date_value->setTime(0, 0, 0);
            $seconds_in_a_day = DateHelper::SECONDS_IN_A_DAY;
            $timestamp_value  = $this->escapeTimestamp($date_value);

            return "BETWEEN $timestamp_value AND $timestamp_value + $seconds_in_a_day - 1";
        }
    }

    private function escapeTimestamp($date_value)
    {
        return CodendiDataAccess::instance()->escapeInt($date_value->format("U"));
    }
}
