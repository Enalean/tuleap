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

namespace Tuleap\Tracker\FormElement;

class ChartFieldUsage
{
    private $use_start_date;
    private $use_duration;
    private $use_capacity;
    private $use_hierarchy;
    private $use_remaining_effort;

    public function __construct(
        $use_start_date,
        $use_duration,
        $use_capacity,
        $use_hierarchy,
        $use_remaining_effort,
    ) {
        $this->use_start_date       = $use_start_date;
        $this->use_duration         = $use_duration;
        $this->use_capacity         = $use_capacity;
        $this->use_hierarchy        = $use_hierarchy;
        $this->use_remaining_effort = $use_remaining_effort;
    }

    public function getUseStartDate()
    {
        return $this->use_start_date;
    }

    public function getUseDuration()
    {
        return $this->use_duration;
    }

    public function getUseCapacity()
    {
        return $this->use_capacity;
    }

    public function getUseHierarchy()
    {
        return $this->use_hierarchy;
    }

    public function getUseRemainingEffort()
    {
        return $this->use_remaining_effort;
    }
}
