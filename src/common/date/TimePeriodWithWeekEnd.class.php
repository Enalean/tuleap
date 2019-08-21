<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

/**
 * A time period, starting at a given date, and with a given duration.
 */
class TimePeriodWithWeekEnd implements TimePeriod
{
    /**
     * @var int The time period start date, as a Unix timestamp.
     */
    private $start_date;

    /**
     * @var int The time period duration, in days.
     */
    private $duration;

    public function __construct($start_date, $duration)
    {
        $this->start_date = $start_date;
        $this->duration   = $this->formatDuration($duration);
    }

    private function formatDuration($duration)
    {
        if (is_numeric($duration)) {
            return (int) ceil((float) $duration);
        }

        return $duration;
    }

    /**
     * @return int
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * @return int
     */
    public function getEndDate()
    {
        $day_offsets = $this->getDayOffsets();
        $last_offset = end($day_offsets);
        return strtotime("+$last_offset days", $this->getStartDate());
    }

    /**
     * To be used to iterate consistently over the time period
     *
     * @return array of int
     */
    public function getDayOffsets()
    {
        if ($this->duration < 0) {
            return array(0);
        } else {
            return range(0, $this->duration);
        }
    }
}
