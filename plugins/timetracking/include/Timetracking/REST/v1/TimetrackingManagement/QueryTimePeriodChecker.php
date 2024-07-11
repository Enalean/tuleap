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

use DateTime;
use DateTimeImmutable;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class QueryTimePeriodChecker
{
    /**
     * @return Ok<Period>|Err<Fault>
     */
    public function ensureTimePeriodIsValid(string $start_date, string $end_date): Ok|Err
    {
        $period_start = DateTimeImmutable::createFromFormat(DateTime::ATOM, $start_date);
        $period_end   = DateTimeImmutable::createFromFormat(DateTime::ATOM, $end_date);

        if (! $period_start || ! $period_end) {
            return Result::err(QueryInvalidDateFormatFault::build());
        }

        if ($period_start > $period_end) {
            return Result::err(QueryEndDateLesserThanStartDateFault::build());
        }

        return Result::ok(new Period($period_start, $period_end));
    }
}
