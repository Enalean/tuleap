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
final class ListValuesCollectionTest extends TestCase
{
    private const CURRENT_USER_NAME = 'utian';
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::aUser()->withUserName(self::CURRENT_USER_NAME)->build();
    }

    public function testItReturnsOkForSimpleValue(): void
    {
        $result      = ListValuesCollection::fromValueWrapper(new SimpleValueWrapper('multipolar'));
        $list_values = $result->unwrapOr(null)->list_values ?? [];
        self::assertCount(1, $list_values);
        self::assertSame('multipolar', $list_values[0]);
    }

    public function testItReturnsOkForCurrentUser(): void
    {
        $result      = ListValuesCollection::fromValueWrapper(
            new CurrentUserValueWrapper(
                ProvideCurrentUserStub::buildWithUser($this->user)
            )
        );
        $list_values = $result->unwrapOr(null)->list_values ?? [];
        self::assertCount(1, $list_values);
        self::assertSame(self::CURRENT_USER_NAME, $list_values[0]);
    }

    public function testItReturnsErrWhenCurrentUserIsAnonymous(): void
    {
        $result = ListValuesCollection::fromValueWrapper(
            new CurrentUserValueWrapper(
                ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anAnonymousUser()->build())
            )
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MyselfNotAllowedForAnonymousFault::class, $result->error);
    }

    public function testItReturnsOkForInValues(): void
    {
        $result      = ListValuesCollection::fromValueWrapper(
            new InValueWrapper(
                [
                    new SimpleValueWrapper('multipolar'),
                    new SimpleValueWrapper('suspiratious'),
                ]
            )
        );
        $list_values = $result->unwrapOr(null)->list_values ?? [];
        self::assertCount(2, $list_values);
        self::assertContains('multipolar', $list_values);
        self::assertContains('suspiratious', $list_values);
    }

    public function testItAllowsInValuesIncludingCurrentUser(): void
    {
        $result      = ListValuesCollection::fromValueWrapper(
            new InValueWrapper(
                [
                    new SimpleValueWrapper('vkobayashi'),
                    new CurrentUserValueWrapper(ProvideCurrentUserStub::buildWithUser($this->user)),
                ]
            )
        );
        $list_values = $result->unwrapOr(null)->list_values ?? [];
        self::assertCount(2, $list_values);
        self::assertContains('vkobayashi', $list_values);
        self::assertContains(self::CURRENT_USER_NAME, $list_values);
    }

    public function testItReturnsErrForCurrentDateTime(): void
    {
        $result = ListValuesCollection::fromValueWrapper(new CurrentDateTimeValueWrapper(null, null));
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidComparisonToCurrentDateTimeFault::class, $result->error);
    }

    public function testItReturnsErrForStatusOpen(): void
    {
        $result = ListValuesCollection::fromValueWrapper(new StatusOpenValueWrapper());
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InvalidComparisonToStatusOpenFault::class, $result->error);
    }

    public function testItThrowsForBetweenValueAsListsNeverSupportBetween(): void
    {
        $this->expectException(\LogicException::class);

        ListValuesCollection::fromValueWrapper(
            new BetweenValueWrapper(
                new SimpleValueWrapper(5),
                new SimpleValueWrapper(8)
            )
        );
    }
}
