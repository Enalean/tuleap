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

namespace Tuleap\Timetracking\REST\v1\PeopleTimetracking;

use DateTime;
use DateTimeImmutable;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Timetracking\Tests\Stub\SaveQueryWithDatesStub;
use Tuleap\Timetracking\Tests\Stub\SaveQueryWithPredefinedTimePeriodStub;
use Tuleap\Timetracking\Widget\People\PredefinedTimePeriod;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PeopleTimetrackingWidgetSaverTest extends TestCase
{
    private const ALICE_ID   = 101;
    private const BOB_ID     = 102;
    private const CHARLIE_ID = 103;

    private \PFUser $alice;
    private \PFUser $bob;
    private \PFUser $charlie;

    #[\Override]
    protected function setUp(): void
    {
        $this->alice   = UserTestBuilder::aUser()->withId(self::ALICE_ID)->build();
        $this->bob     = UserTestBuilder::aUser()->withId(self::BOB_ID)->build();
        $this->charlie = UserTestBuilder::aUser()->withId(self::CHARLIE_ID)->build();
    }

    public function testItReturnsTrueWhenQueryWasSavedWithDates(): void
    {
        $save_with_dates       = SaveQueryWithDatesStub::build();
        $save_with_time_period = SaveQueryWithPredefinedTimePeriodStub::shouldNotBeCalled();

        $start_date_immutable = DateTimeImmutable::createFromFormat(DateTime::ATOM, '2024-06-26T15:46:00z');
        $end_date_immutable   = DateTimeImmutable::createFromFormat(DateTime::ATOM, '2024-06-27T15:46:00z');

        if (! $start_date_immutable || ! $end_date_immutable) {
            throw new \Exception('Unable to build a start_date or end_date.');
        }

        $result = (new PeopleTimetrackingWidgetSaver($save_with_dates, $save_with_time_period))->save(
            89,
            Period::fromDates($start_date_immutable, $end_date_immutable),
            new UserList(
                [$this->alice, $this->bob, $this->charlie],
                [],
                [],
            ),
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($result->value);

        self::assertFalse($save_with_time_period->hasBeenCalled());
        self::assertTrue($save_with_dates->hasBeenCalled());
    }

    public function testItReturnsTrueWhenQueryWasSavedWithPredefinedTimePeriod(): void
    {
        $save_with_dates       = SaveQueryWithDatesStub::shouldNotBeCalled();
        $save_with_time_period = SaveQueryWithPredefinedTimePeriodStub::build();

        $result = (new PeopleTimetrackingWidgetSaver($save_with_dates, $save_with_time_period))->save(
            89,
            Period::fromPredefinedTimePeriod(PredefinedTimePeriod::YESTERDAY),
            new UserList(
                [$this->alice, $this->bob, $this->charlie],
                [],
                [],
            ),
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($result->value);

        self::assertFalse($save_with_dates->hasBeenCalled());
        self::assertTrue($save_with_time_period->hasBeenCalled());
    }
}
