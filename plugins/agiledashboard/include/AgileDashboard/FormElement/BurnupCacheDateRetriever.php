<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\FormElement;

use DateTime;
use Tuleap\Date\DatePeriodWithOpenDays;

final class BurnupCacheDateRetriever
{
    public function getYesterday(): int
    {
        $date = new DateTime('yesterday 23:59:00');

        return $date->getTimestamp();
    }

    /**
     * @return int[]
     */
    public function getWorkedDaysToCacheForPeriod(DatePeriodWithOpenDays $burnup_period, DateTime $end_time): array
    {
        $start_date = $this->getFirstDayToCache($burnup_period);
        $end_date   = $this->getLastDayToCache($burnup_period);
        $end_time->setTime(23, 59, 00);

        $day = [];

        while ($start_date <= $end_date && $start_date <= $end_time) {
            if (DatePeriodWithOpenDays::isOpenDay($start_date->getTimestamp())) {
                $day[] = $start_date->getTimestamp();
            }

            $start_date->modify('+1 day');
        }

        return $day;
    }

    private function getFirstDayToCache(DatePeriodWithOpenDays $burnup_period): DateTime
    {
        $start_date = new DateTime();
        $start_date->setTimestamp((int) $burnup_period->getStartDate());
        $start_date->setTime(23, 59, 00);

        return $start_date;
    }

    private function getLastDayToCache(DatePeriodWithOpenDays $burnup_period): DateTime
    {
        $end_date = new DateTime();
        $end_date->setTimestamp((int) $burnup_period->getEndDate());
        $end_date->modify('+1 day');

        return $end_date;
    }
}
