<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

use Tuleap\REST\JsonCast;

class BurndownRepresentation
{
    const ROUTE = 'burndown';

    /**
     * @var int Time needed to complete the milestone (in days)
     */
    public $duration;

    /**
     * @var float Capacity of the team for the milestone
     */
    public $capacity;

    /**
     * @var array Values for each days {@type float}
     */
    public $points = array();

    /**
     * @var boolean Is cache calcul asked
     */
    public $is_under_calculation;

    public function build($duration, $capacity, array $points, $is_under_calculation)
    {
        $this->duration             = JsonCast::toInt($duration);
        $this->capacity             = JsonCast::toFloat($capacity);
        $this->points               = array_map(array('\Tuleap\REST\JsonCast', 'toFloat'), $points);
        $this->is_under_calculation = JsonCast::toBoolean($is_under_calculation);

        return $this;
    }
}
