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
use Tuleap\Timetracking\Widget\Management\PredefinedTimePeriod;

final readonly class FromPayloadPeriodBuilder
{
    /**
     * @return Ok<Period>|Err<Fault>
     */
    public function getValidatedPeriod(QueryPUTRepresentation $representation): Ok|Err
    {
        if ($representation->start_date === null && $representation->end_date === null && $representation->predefined_time_period === null) {
            return Result::err(QueryPredefinedTimePeriodAndDatesProvidedFault::build());
        }

        if ($representation->start_date !== null && $representation->end_date !== null && $representation->predefined_time_period !== null) {
            return Result::err(QueryPredefinedTimePeriodAndDatesProvidedFault::build());
        }

        if ($representation->predefined_time_period != null) {
            $predefined_time_period = PredefinedTimePeriod::from($representation->predefined_time_period);

            return Result::ok(Period::fromPredefinedTimePeriod($predefined_time_period));
        }

        if ($representation->start_date == null || $representation->end_date == null) {
            return Result::err(QueryOnlyOneDateProvidedFault::build());
        }

        $start_date = DateTimeImmutable::createFromFormat(DateTime::ATOM, $representation->start_date);
        $end_date   = DateTimeImmutable::createFromFormat(DateTime::ATOM, $representation->end_date);

        if (! $start_date || ! $end_date) {
            return Result::err(QueryInvalidDateFormatFault::build());
        }

        if ($start_date > $end_date) {
            return Result::err(QueryEndDateLesserThanStartDateFault::build());
        }

        return Result::ok(Period::fromDates($start_date, $end_date));
    }
}
