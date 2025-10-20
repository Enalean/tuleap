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

namespace Tuleap\AgileDashboard\FormElement;

use Tuleap\AgileDashboard\FormElement\Burnup\Count\CountElementsInfo;
use Tuleap\Date\DatePeriodWithOpenDays;

class BurnupData
{
    /**
     * @var DatePeriodWithOpenDays
     */
    private $date_period;

    /**
     * @var bool
     */
    private $is_under_calculation;

    /**
     * @var BurnupEffort[]
     */
    private $efforts = [];

    /**
     * @var CountElementsInfo[]
     */
    private $count_elements = [];

    public function __construct(DatePeriodWithOpenDays $date_period, bool $is_under_calculation)
    {
        $this->is_under_calculation = $is_under_calculation;
        $this->date_period          = $date_period;
    }

    public function getDatePeriod(): DatePeriodWithOpenDays
    {
        return $this->date_period;
    }

    public function isBeingCalculated(): bool
    {
        return $this->is_under_calculation;
    }

    public function addEffort(BurnupEffort $effort, int $timestamp): void
    {
        $this->efforts[$timestamp] = $effort;
    }

    public function addCountElements(CountElementsInfo $count_elements, int $timestamp): void
    {
        $this->count_elements[$timestamp] = $count_elements;
    }

    /**
     * @return BurnupEffort[]
     */
    public function getEfforts(): array
    {
        return $this->efforts;
    }

    /**
     * @return CountElementsInfo[]
     */
    public function getCountElements(): array
    {
        return $this->count_elements;
    }
}
