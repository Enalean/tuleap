<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\REST\v1\TimetrackingManagement;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class TimetrackingManagementWidgetSaver
{
    public function __construct(
        private SaveQueryWithDates $save_with_dates,
        private SaveQueryWithPredefinedTimePeriod $save_with_time_period,
    ) {
    }

    /**
     * @return Ok<true>|Err<Fault>
     */
    public function save(int $query_id, Period $period, UserList $users): Ok|Err
    {
        if ($period->isPredefined() && $period->getPeriod()) {
            $this->save_with_time_period->saveQueryWithPredefinedTimePeriod($query_id, $period->getPeriod(), $users);
            return Result::ok(true);
        }

        if ($period->getStartDate() && $period->getEndDate()) {
            $this->save_with_dates->saveQueryWithDates($query_id, $period->getStartDate(), $period->getEndDate(), $users);
        }
        return Result::ok(true);
    }
}
