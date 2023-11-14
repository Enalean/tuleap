<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use DateTime;
use Tuleap\Date\DatePeriodWithoutWeekEnd;

class BurndownCacheDateRetriever
{
    public function getYesterday()
    {
        $date = new DateTime();
        $date->setTime(0, 0, 0);
        $date->modify("-1 second");
        return $date->getTimestamp();
    }

    public function getWorkedDaysToCacheForPeriod(DatePeriodWithoutWeekEnd $burndown_period, DateTime $yesterday)
    {
        $start_date = $this->getFirstDayToCache($burndown_period);
        $end_date   = $this->getLastDayToCache($burndown_period);

        $day = [];

        while ($start_date <= $end_date && $start_date <= $yesterday) {
            if (DatePeriodWithoutWeekEnd::isNotWeekendDay($this->removeLastSecondOfCachedDayForJPGraph($start_date))) {
                $day[] = $this->removeLastSecondOfCachedDayForJPGraph($start_date);
            }

            $start_date->modify('+1 day');
        }

        return $day;
    }

    private function getFirstDayToCache(DatePeriodWithoutWeekEnd $burndown_period)
    {
        $start_date = new DateTime();
        $start_date->setTimestamp((int) $burndown_period->getStartDate());
        $this->addOneDayToDateTime($start_date);

        return $start_date;
    }

    private function getLastDayToCache(DatePeriodWithoutWeekEnd $burndown_period)
    {
        $end_date = new DateTime();
        $end_date->setTimestamp((int) $burndown_period->getEndDate());
        $this->addOneDayToDateTime($end_date);

        return $end_date;
    }

    private function addOneDayToDateTime(DateTime $date)
    {
        $date->setTime(0, 0, 0);
        $date->modify('+1 day');

        return $date;
    }

    private function removeLastSecondOfCachedDayForJPGraph(DateTime $date)
    {
        $return_date = new DateTime();
        $return_date->setTimestamp($date->getTimestamp());
        $return_date->modify('-1 second');

        return $return_date->getTimestamp();
    }
}
