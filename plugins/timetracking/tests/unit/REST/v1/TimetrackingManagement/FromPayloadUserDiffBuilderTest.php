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

use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Timetracking\Tests\Stub\CheckThatUserIsActiveStub;
use Tuleap\Timetracking\Tests\Stub\GetQueryUsersStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FromPayloadUserDiffBuilderTest extends TestCase
{
    private const WIDGET_ID = 92;

    public function testItReturnsAFaultWhenUserIdsAreInvalid(): void
    {
        $result = (
            new FromPayloadUserDiffBuilder(
                CheckThatUserIsActiveStub::withActiveUsers(101, 102),
                GetQueryUsersStub::withUserIds(),
            )
        )->getUserDiff(
            self::WIDGET_ID,
            [
                ['id' => 101],
                ['id' => 104],
            ]
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryInvalidUserIdFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenUserIdIsValidButNotActive(): void
    {
        $result = (
            new FromPayloadUserDiffBuilder(
                CheckThatUserIsActiveStub::withActiveUsers(101, 102),
                GetQueryUsersStub::withUserIds(),
            )
        )->getUserDiff(
            self::WIDGET_ID,
            [
                ['id' => 101],
                ['id' => 103],
            ]
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryInvalidUserIdFault::class, $result->error);
    }

    public function testItReturnsAUserDiffWhenValidUsersAreProvided(): void
    {
        $result = (
        new FromPayloadUserDiffBuilder(
            CheckThatUserIsActiveStub::withActiveUsers(101, 102),
            GetQueryUsersStub::withUserIds(),
        )
        )->getUserDiff(self::WIDGET_ID, [['id' => 101], ['id' => 102]]);

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            new UserDiff(
                [101, 102],
                []
            ),
            $result->value
        );
    }

    public function testItReturnsAnEmptyUserDiffWhenValidUsersAreProvidedAndAreAlreadyInDB(): void
    {
        $result = (
        new FromPayloadUserDiffBuilder(
            CheckThatUserIsActiveStub::withActiveUsers(101, 102),
            GetQueryUsersStub::withUserIds(101, 102),
        )
        )->getUserDiff(self::WIDGET_ID, []);

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            new UserDiff(
                [],
                [101, 102]
            ),
            $result->value
        );
    }

    public function testItReturnsAUserDiffWhenValidUsersAreProvidedAndOneIsAlreadyInDB(): void
    {
        $result = (
        new FromPayloadUserDiffBuilder(
            CheckThatUserIsActiveStub::withActiveUsers(101, 102),
            GetQueryUsersStub::withUserIds(102),
        )
        )->getUserDiff(self::WIDGET_ID, [['id' => 101]]);

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            new UserDiff(
                [101],
                [102]
            ),
            $result->value
        );
    }
}
