<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\layout\HomePage;

class HomePageStatistic
{
    /**
     * @var string
     */
    private $label;
    /**
     * @var int
     */
    private $total;
    /**
     * @var int
     */
    private $last_month_growth;

    public function __construct(string $label, int $total, int $last_month_growth)
    {
        $this->label             = $label;
        $this->total             = $total;
        $this->last_month_growth = $last_month_growth;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getLastMonthGrowth(): int
    {
        return $this->last_month_growth;
    }

    public function hasGrowth(): bool
    {
        return $this->last_month_growth > 0;
    }
}
