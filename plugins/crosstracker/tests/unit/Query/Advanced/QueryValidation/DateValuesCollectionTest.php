<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\QueryValidation;

use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DateValuesCollectionTest extends TestCase
{
    public function testItReturnsOkForSimpleValue(): void
    {
        $result      = DateValuesCollection::fromValueWrapper(new SimpleValueWrapper('1997-03-21 20:25'), false);
        $date_values = $result->unwrapOr(null)->date_values ?? [];
        self::assertCount(1, $date_values);
        self::assertSame('1997-03-21 20:25', $date_values[0]);
    }

    public function testItReturnsOkForCurrentDateTimeWhenAllowed(): void
    {
        $result      = DateValuesCollection::fromValueWrapper(new CurrentDateTimeValueWrapper(null, null), true);
        $date_values = $result->unwrapOr(null)->date_values ?? [];
        self::assertCount(1, $date_values);
        self::assertNotEmpty($date_values[0]);
    }

    public function testItReturnsErrForCurrentDateTimeWhenForbidden(): void
    {
        $result = DateValuesCollection::fromValueWrapper(new CurrentDateTimeValueWrapper(null, null), false);
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidComparisonToCurrentDateTimeFault::class, $result->error);
    }

    public function testItReturnsOkForBetweenValues(): void
    {
        $result      = DateValuesCollection::fromValueWrapper(
            new BetweenValueWrapper(
                new SimpleValueWrapper('1971-10-29 18:04'),
                new SimpleValueWrapper('1989-03-23 08:13'),
            ),
            false
        );
        $date_values = $result->unwrapOr(null)->date_values ?? [];
        self::assertCount(2, $date_values);
        self::assertContains('1971-10-29 18:04', $date_values);
        self::assertContains('1989-03-23 08:13', $date_values);
    }

    public static function generateBetweenValuesIncludingCurrentDateTime(): iterable
    {
        $valid_date_string    = '1989-12-31 04:15';
        $simple_value_wrapper = new SimpleValueWrapper($valid_date_string);
        $now_value            = new CurrentDateTimeValueWrapper(null, null);
        yield 'BETWEEN(NOW(), simple value)' => [
            new BetweenValueWrapper($now_value, $simple_value_wrapper),
            $valid_date_string,
        ];
        yield 'BETWEEN(simple value, NOW())' => [
            new BetweenValueWrapper($simple_value_wrapper, $now_value),
            $valid_date_string,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateBetweenValuesIncludingCurrentDateTime')]
    public function testItAllowsBetweenValuesIncludingCurrentDateTime(
        BetweenValueWrapper $wrapper,
        string $valid_date_string,
    ): void {
        $result      = DateValuesCollection::fromValueWrapper($wrapper, true);
        $date_values = $result->unwrapOr(null)->date_values ?? [];
        self::assertCount(2, $date_values);
        self::assertContains($valid_date_string, $date_values);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateBetweenValuesIncludingCurrentDateTime')]
    public function testItRejectsBetweenValuesIncludingCurrentDateTimeWhenForbidden(
        BetweenValueWrapper $wrapper,
        string $unused_data_provider_value,
    ): void {
        $result = DateValuesCollection::fromValueWrapper($wrapper, false);
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidComparisonToCurrentDateTimeFault::class, $result->error);
    }

    public function testItReturnsErrForCurrentUser(): void
    {
        $result = DateValuesCollection::fromValueWrapper(
            new CurrentUserValueWrapper(
                ProvideCurrentUserStub::buildWithUser(
                    UserTestBuilder::buildWithDefaults()
                )
            ),
            false
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidComparisonToCurrentUserFault::class, $result->error);
    }

    public function testItReturnsErrForStatusOpen(): void
    {
        $result = DateValuesCollection::fromValueWrapper(new StatusOpenValueWrapper(), false);
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidComparisonToStatusOpenFault::class, $result->error);
    }

    public function testItThrowsForInValueAsDateFieldsNeverSupportIn(): void
    {
        $this->expectException(\LogicException::class);

        DateValuesCollection::fromValueWrapper(
            new InValueWrapper([
                new SimpleValueWrapper('any'),
                new SimpleValueWrapper('value'),
            ]),
            false
        );
    }
}
