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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FromPayloadPeriodBuilderTest extends TestCase
{
    public function testItReturnsAFaultWhenPredefinedTimePeriodAndTwoDatesAreProvided(): void
    {
        $result = (new FromPayloadPeriodBuilder())
            ->getValidatedPeriod(
                new QueryPUTRepresentation(
                    '2024-06-26T15:46:00z',
                    '2024-06-26T15:46:00z',
                    'today',
                    []
                )
            );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryPredefinedTimePeriodAndDatesProvidedFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenPredefinedTimePeriodAndStartDateAreProvided(): void
    {
        $result = (new FromPayloadPeriodBuilder())
            ->getValidatedPeriod(
                new QueryPUTRepresentation(
                    'start_date',
                    '',
                    'today',
                    []
                )
            );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryPredefinedTimePeriodAndDatesProvidedFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenPredefinedTimePeriodAndEndDateAreProvided(): void
    {
        $result = (new FromPayloadPeriodBuilder())
            ->getValidatedPeriod(
                new QueryPUTRepresentation(
                    '',
                    '2024-06-26T15:46:00z',
                    'today',
                    []
                )
            );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryPredefinedTimePeriodAndDatesProvidedFault::class, $result->error);
    }

    public function testItReturnsAPredefinedTimePeriodWhenValidPredefinedTimePeriodIsProvided(): void
    {
        $result = (new FromPayloadPeriodBuilder())
            ->getValidatedPeriod(
                new QueryPUTRepresentation(
                    null,
                    null,
                    'last_week',
                    [
                        ['id' => 101],
                        ['id' => 102],
                    ]
                )
            );

        self::assertTrue(Result::isOk($result));
        self::assertEquals(PredefinedTimePeriod::LAST_WEEK, $result->value->getPeriod());
    }

    public function testItReturnsAFaultWhenNothingIsProvided(): void
    {
        $result = (new FromPayloadPeriodBuilder())
            ->getValidatedPeriod(
                new QueryPUTRepresentation(
                    null,
                    null,
                    null,
                    []
                )
            );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryPredefinedTimePeriodAndDatesProvidedFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenOnlyStartDateIsProvided(): void
    {
        $result = (new FromPayloadPeriodBuilder())
            ->getValidatedPeriod(
                new QueryPUTRepresentation(
                    'hello',
                    null,
                    null,
                    []
                )
            );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryOnlyOneDateProvidedFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenOnlyEndDateIsProvided(): void
    {
        $result = (new FromPayloadPeriodBuilder())
            ->getValidatedPeriod(
                new QueryPUTRepresentation(
                    null,
                    '2024-06-26T15:46:00z',
                    null,
                    []
                )
            );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryOnlyOneDateProvidedFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenStartDateAndEndDateAreInvalid(): void
    {
        $result = (new FromPayloadPeriodBuilder())
            ->getValidatedPeriod(
                new QueryPUTRepresentation(
                    'start',
                    'end',
                    null,
                    []
                )
            );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryInvalidDateFormatFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenStartDateIsInvalid(): void
    {
        $result = (new FromPayloadPeriodBuilder())
            ->getValidatedPeriod(
                new QueryPUTRepresentation(
                    'start',
                    '2024-06-26T15:46:00z',
                    null,
                    []
                )
            );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryInvalidDateFormatFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenEndDateIsInvalid(): void
    {
        $result = (new FromPayloadPeriodBuilder())
            ->getValidatedPeriod(
                new QueryPUTRepresentation(
                    '2024-06-26T15:46:00z',
                    'end',
                    null,
                    []
                )
            );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryInvalidDateFormatFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenEndDateIsLesserThanStartDate(): void
    {
        $result = (new FromPayloadPeriodBuilder())
            ->getValidatedPeriod(
                new QueryPUTRepresentation(
                    '2024-06-27T15:46:00z',
                    '2024-06-26T15:46:00z',
                    null,
                    []
                )
            );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryEndDateLesserThanStartDateFault::class, $result->error);
    }

    public function testItReturnsAPeriodWhenTwoValidDatesAreProvided(): void
    {
        $start_date = '2024-06-26T15:46:00z';
        $end_date   = '2024-06-26T15:46:00z';

        $result = (new FromPayloadPeriodBuilder())
            ->getValidatedPeriod(
                new QueryPUTRepresentation(
                    $start_date,
                    $end_date,
                    null,
                    [
                        ['id' => 101],
                        ['id' => 102],
                    ]
                )
            );

        $start_date_immutable = DateTimeImmutable::createFromFormat(DateTime::ATOM, $start_date);
        $end_date_immutable   = DateTimeImmutable::createFromFormat(DateTime::ATOM, $end_date);

        if (! $start_date_immutable || ! $end_date_immutable) {
            throw new \Exception('Unable to build a start_date or end_date.');
        }

        self::assertTrue(Result::isOk($result));
        self::assertEquals(Period::fromDates($start_date_immutable, $end_date_immutable), $result->value);
    }
}
