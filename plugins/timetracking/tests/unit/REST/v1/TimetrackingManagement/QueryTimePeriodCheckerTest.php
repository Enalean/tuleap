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
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

final class QueryTimePeriodCheckerTest extends TestCase
{
    public function testItReturnsAFaultWhenStartDateIsInvalid(): void
    {
        $result = (new QueryTimePeriodChecker())->ensureTimePeriodIsValid('start', '2024-06-26T15:46:00z');
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryInvalidDateFormatFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenEndDateIsInvalid(): void
    {
        $result = (new QueryTimePeriodChecker())->ensureTimePeriodIsValid('2024-06-26T15:46:00z', 'end');
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryInvalidDateFormatFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenEndDateIsLesserThanStartDate(): void
    {
        $result = (new QueryTimePeriodChecker())->ensureTimePeriodIsValid('2024-06-27T15:46:00z', '2024-06-26T15:46:00z');
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryEndDateLesserThanStartDateFault::class, $result->error);
    }

    public function testItReturnsTrueWhenDatesAreValidAndEndDateIsGreaterThanStartDate(): void
    {
        $start_date = '2024-06-26T15:46:00z';
        $end_date   = '2024-06-27T15:46:00z';

        $result = (new QueryTimePeriodChecker())->ensureTimePeriodIsValid($start_date, $end_date);

        $start_date_immutable = DateTimeImmutable::createFromFormat(DateTime::ATOM, $start_date);
        $end_date_immutable   = DateTimeImmutable::createFromFormat(DateTime::ATOM, $end_date);

        if (! $start_date_immutable || ! $end_date_immutable) {
            return;
        }

        $period = new Period($start_date_immutable, $end_date_immutable);

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            $period,
            $result->value
        );
    }
}
