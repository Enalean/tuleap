<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\People;

use PHPUnit\Framework\Attributes\TestWith;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Timetracking\Tests\Stub\GetWidgetInformationStub;
use Tuleap\Timetracking\Tests\Stub\SearchQueryByWidgetIdStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserTimesTimeframeRetrieverTest extends TestCase
{
    public function testFaultWhenWidgetNotFound(): void
    {
        $now = new \DateTimeImmutable('2025-09-05');

        $manager = UserTestBuilder::buildWithDefaults();

        $timeframe = new UserTimesTimeframeRetriever(
            GetWidgetInformationStub::notFound(),
            SearchQueryByWidgetIdStub::notFound(),
            $now,
        )->getTimeframe(1, $manager);

        self::assertTrue(Result::isErr($timeframe));
    }

    public function testFaultWhenWidgetDoesNotBelongToManager(): void
    {
        $now = new \DateTimeImmutable('2025-09-05');

        $alice   = UserTestBuilder::aUser()->withId(101)->build();
        $manager = UserTestBuilder::aUser()->withId(102)->build();

        $timeframe = new UserTimesTimeframeRetriever(
            GetWidgetInformationStub::notFound(),
            SearchQueryByWidgetIdStub::notFound(),
            $now,
        )->getTimeframe(1, $manager);

        self::assertTrue(Result::isErr($timeframe));
    }

    public function testFaultWhenNotFound(): void
    {
        $now = new \DateTimeImmutable('2025-09-05');

        $manager = UserTestBuilder::buildWithDefaults();

        $timeframe = new UserTimesTimeframeRetriever(
            GetWidgetInformationStub::withWidgetBelongingToUser($manager),
            SearchQueryByWidgetIdStub::notFound(),
            $now,
        )->getTimeframe(1, $manager);

        self::assertTrue(Result::isErr($timeframe));
    }

    public function testSpecificTimeframe(): void
    {
        $now = new \DateTimeImmutable('2025-09-05');

        $manager = UserTestBuilder::buildWithDefaults();

        $timeframe = new UserTimesTimeframeRetriever(
            GetWidgetInformationStub::withWidgetBelongingToUser($manager),
            SearchQueryByWidgetIdStub::build(1, 1234567890, 1243567890, null),
            $now,
        )->getTimeframe(1, $manager);

        self::assertTrue(Result::isOk($timeframe));
        self::assertSame('2009-02-14', $timeframe->value->start->format('Y-m-d'));
        self::assertSame('2009-05-29', $timeframe->value->end->format('Y-m-d'));
    }

    #[TestWith([PredefinedTimePeriod::TODAY, '2025-09-05', '2025-09-05'])]
    #[TestWith([PredefinedTimePeriod::YESTERDAY, '2025-09-04', '2025-09-04'])]
    #[TestWith([PredefinedTimePeriod::LAST_7_DAYS, '2025-08-29', '2025-09-05'])]
    #[TestWith([PredefinedTimePeriod::CURRENT_WEEK, '2025-09-01', '2025-09-07'])]
    #[TestWith([PredefinedTimePeriod::LAST_WEEK, '2025-08-25', '2025-08-31'])]
    #[TestWith([PredefinedTimePeriod::LAST_MONTH, '2025-08-01', '2025-08-31'])]
    public function testPredefinedPeriod(
        PredefinedTimePeriod $predefined_time_period,
        string $expected_start,
        string $expected_end,
    ): void {
        $now = new \DateTimeImmutable('2025-09-05');

        $manager = UserTestBuilder::buildWithDefaults();

        $timeframe = new UserTimesTimeframeRetriever(
            GetWidgetInformationStub::withWidgetBelongingToUser($manager),
            SearchQueryByWidgetIdStub::build(1, null, null, $predefined_time_period->value),
            $now,
        )->getTimeframe(1, $manager);

        self::assertTrue(Result::isOk($timeframe));
        self::assertSame($expected_start, $timeframe->value->start->format('Y-m-d'));
        self::assertSame($expected_end, $timeframe->value->end->format('Y-m-d'));
    }
}
