<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Request;

use Tuleap\AgileDashboard\Milestone\Criterion\Status\ISearchOnStatus;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;

/**
 * @psalm-immutable
 */
final class FilteringQuery
{
    /**
     * @var PeriodQuery|null
     */
    private $period_query;
    /**
     * @var ISearchOnStatus
     */
    private $status_query;

    private function __construct(?PeriodQuery $period_query, ISearchOnStatus $status_query)
    {
        $this->period_query = $period_query;
        $this->status_query = $status_query;
    }

    public static function fromStatusQuery(ISearchOnStatus $status_query): self
    {
        return new self(null, $status_query);
    }

    public static function fromPeriodQuery(PeriodQuery $period_query): self
    {
        return new self($period_query, new StatusOpen());
    }

    public function getStatusFilter(): ISearchOnStatus
    {
        return $this->status_query;
    }

    public function isFuturePeriod(): bool
    {
        if (! $this->period_query) {
            return false;
        }
        return $this->period_query->isFuture();
    }

    public function isCurrentPeriod(): bool
    {
        if (! $this->period_query) {
            return false;
        }
        return $this->period_query->isCurrent();
    }
}
