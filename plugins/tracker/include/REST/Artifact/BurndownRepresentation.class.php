<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Tracker_Chart_Data_Burndown;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
class BurndownRepresentation
{
    public const ROUTE = 'burndown';

    /**
     * @var string {@type date}
     */
    public $start_date;

    /**
     * @var int Time needed to complete the milestone (in days)
     */
    public $duration;

    /**
     * @var float Capacity of the team for the milestone
     */
    public $capacity;

    /**
     * @var array {@type float}
     */
    public $points = [];

    /**
     * @var bool Is cache calcul asked
     */
    public $is_under_calculation;

    /**
     * @var array {@type int} Opening days in week (Sunday as 0 and Saturday as 6)
     */
    public $opening_days;

    /**
     * @var array {@type BurndownPointRepresentation}
     */
    public $points_with_date = [];

    public function __construct(Tracker_Chart_Data_Burndown $data_burndown)
    {
        $this->start_date           = JsonCast::toDate($data_burndown->getDatePeriod()->getStartDate());
        $this->duration             = JsonCast::toInt($data_burndown->getDatePeriod()->getDuration());
        $this->capacity             = JsonCast::toFloat($data_burndown->getCapacity());
        $this->points               = self::getPoints($data_burndown);
        $this->is_under_calculation = JsonCast::toBoolean($data_burndown->isUnderCalcul());
        foreach ($data_burndown->getRemainingEffortsAtDate() as $timestamp => $burndown_effort) {
            $this->points_with_date[] = new BurndownPointRepresentation($burndown_effort, $timestamp);
        }

        $this->opening_days = [1, 2, 3, 4, 5];
    }

    /**
     * @return float[]
     */
    private static function getPoints(Tracker_Chart_Data_Burndown $data_burndown): array
    {
        $points = [];
        foreach ($data_burndown->getRemainingEffortWithoutNullValues() as $remaining_effort) {
            $points[] = JsonCast::toFloat($remaining_effort);
        }
        return $points;
    }
}
