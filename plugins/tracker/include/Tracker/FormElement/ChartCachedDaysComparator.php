<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Logger;
use Tuleap\Tracker\Chart\Data\IProvideChartData;

class ChartCachedDaysComparator
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function areDaysIdentical(IProvideChartData $chart_data, $number_of_cached_days)
    {
        $days = $chart_data->getTimePeriod()->getCountDayUntilDate($_SERVER['REQUEST_TIME']);

        if ($this->isTodayAWeekDayAndIsTodayBeforeTimePeriodEnd($chart_data)) {
            $this->logger->debug("Period is current");
            $this->logger->debug("Day cached: " . $number_of_cached_days);
            $this->logger->debug("Period days: " . $days);
            $this->logger->debug("Period days without last computed value: " . ($days - 1));

            return $this->compareCachedDaysWhenLastDayIsAComputedValue((int) $number_of_cached_days, $days);
        } else {
            $this->logger->debug("Period is in past");
            $this->logger->debug("Day cached: " . $number_of_cached_days);
            $this->logger->debug("Period days: " . $days);

            return $this->compareCachedDaysWithPeriodDays((int) $number_of_cached_days, $days);
        }
    }

    private function isTodayAWeekDayAndIsTodayBeforeTimePeriodEnd(IProvideChartData $chart_data)
    {
        return $chart_data->getTimePeriod()->isTodayWithinTimePeriod()
            && $chart_data->getTimePeriod()->isNotWeekendDay($_SERVER['REQUEST_TIME']);
    }

    private function compareCachedDaysWhenLastDayIsAComputedValue($cache_days, $number_of_days_for_period)
    {
        return $cache_days === $number_of_days_for_period - 1;
    }

    private function compareCachedDaysWithPeriodDays($cache_days, $number_of_days_for_period)
    {
        return $cache_days === $number_of_days_for_period;
    }
}
