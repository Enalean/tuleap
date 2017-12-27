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

namespace Tuleap\AgileDashboard\v1\Artifact;

use Tuleap\AgileDashboard\FormElement\BurnupData;
use Tuleap\REST\JsonCast;

class BurnupRepresentation
{
    /**
     * @var string {@type date}
     */
    public $start_date;
    /**
     * @var int
     */
    public $duration;
    /**
     * @var float
     */
    public $capacity;
    /**
     * @var bool
     */
    public $is_under_calculation = true;
    /**
     * @var array {@type BurnupPointRepresentation}
     */
    public $points = array();

    public function __construct($capacity, BurnupData $burnup_data = null)
    {
        $this->capacity = JsonCast::toFloat($capacity);
        if ($burnup_data === null) {
            return;
        }
        $this->start_date           = JsonCast::toDate($burnup_data->getTimePeriod()->getStartDate());
        $this->duration             = JsonCast::toInt($burnup_data->getTimePeriod()->getDuration());
        $this->is_under_calculation = JsonCast::toBoolean($burnup_data->isBeingCalculated());
        foreach ($burnup_data->getEfforts() as $timestamp => $burnup_effort) {
            $this->points[] = new BurnupPointRepresentation($burnup_effort, $timestamp);
        }
    }
}
