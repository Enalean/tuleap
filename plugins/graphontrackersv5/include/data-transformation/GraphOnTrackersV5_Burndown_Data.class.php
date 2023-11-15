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

use Tuleap\Date\DatePeriodWithWeekEnd;

/**
 * this class build data required to build a burndown
 *
 */
class GraphOnTrackersV5_Burndown_Data
{
    private $artifact_ids     = [];
    private $remaining_effort = [];
    private $min_day          = PHP_INT_MAX;
    private $max_day          = 0;
    private DatePeriodWithWeekEnd $date_period;

    public function __construct($query_result, array $artifact_ids, DatePeriodWithWeekEnd $date_period)
    {
        $this->artifact_ids = $artifact_ids;
        while ($row = db_fetch_array($query_result)) {
            $day         = $row['day'];
            $artifact_id = $row['id'];
            if (! isset($this->remaining_effort[$day][$artifact_id])) {
                $this->remaining_effort[$day][$artifact_id] = $row['value'];
            }
        }
        $this->date_period = $date_period;
    }

    public function getRemainingEffort()
    {
        return $this->remaining_effort;
    }

    public function getMinDay()
    {
        return $this->min_day;
    }

    public function getMaxDay()
    {
        return $this->max_day;
    }

    public function getArtifactIds()
    {
        return $this->artifact_ids;
    }

    /**
     * @return DatePeriodWithWeekEnd
     */
    public function getDatePeriod()
    {
        return $this->date_period;
    }
}
