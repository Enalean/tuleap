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
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Timetracking\Tests\Stub\GetActiveUserStub;
use Tuleap\Timetracking\Tests\Stub\GetQueryUsersStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FromPayloadUserDiffBuilderTest extends TestCase
{
    private const WIDGET_ID  = 92;
    private const ALICE_ID   = 101;
    private const BOB_ID     = 102;
    private const CHARLIE_ID = 103;
    private const DYLAN_ID   = 104;

    private \PFUser $alice;
    private \PFUser $bob;

    protected function setUp(): void
    {
        $this->alice = UserTestBuilder::aUser()->withId(self::ALICE_ID)->build();
        $this->bob   = UserTestBuilder::aUser()->withId(self::BOB_ID)->build();
    }

    public function testItReturnsAFaultWhenUserIdsAreInvalid(): void
    {
        $result = (
            new FromPayloadUserDiffBuilder(
                GetActiveUserStub::withActiveUsers($this->alice, $this->bob),
                GetQueryUsersStub::withUserIds(),
            )
        )->getUserDiff(
            self::WIDGET_ID,
            [
                ['id' => self::ALICE_ID],
                ['id' => self::DYLAN_ID],
            ]
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryInvalidUserIdFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenUserIdIsValidButNotActive(): void
    {
        $result = (
            new FromPayloadUserDiffBuilder(
                GetActiveUserStub::withActiveUsers($this->alice, $this->bob),
                GetQueryUsersStub::withUserIds(),
            )
        )->getUserDiff(
            self::WIDGET_ID,
            [
                ['id' => self::ALICE_ID],
                ['id' => self::CHARLIE_ID],
            ]
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(QueryInvalidUserIdFault::class, $result->error);
    }

    public function testItReturnsAUserDiffWhenValidUsersAreProvided(): void
    {
        $result = (
        new FromPayloadUserDiffBuilder(
            GetActiveUserStub::withActiveUsers($this->alice, $this->bob),
            GetQueryUsersStub::withUserIds(),
        )
        )->getUserDiff(self::WIDGET_ID, [['id' => self::ALICE_ID], ['id' => self::BOB_ID]]);

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            new UserDiff(
                [self::ALICE_ID, self::BOB_ID],
                []
            ),
            $result->value
        );
    }

    public function testItReturnsAnEmptyUserDiffWhenValidUsersAreProvidedAndAreAlreadyInDB(): void
    {
        $result = (
        new FromPayloadUserDiffBuilder(
            GetActiveUserStub::withActiveUsers($this->alice, $this->bob),
            GetQueryUsersStub::withUserIds(self::ALICE_ID, self::BOB_ID),
        )
        )->getUserDiff(self::WIDGET_ID, []);

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            new UserDiff(
                [],
                [self::ALICE_ID, self::BOB_ID]
            ),
            $result->value
        );
    }

    public function testItReturnsAUserDiffWhenValidUsersAreProvidedAndOneIsAlreadyInDB(): void
    {
        $result = (
        new FromPayloadUserDiffBuilder(
            GetActiveUserStub::withActiveUsers($this->alice, $this->bob),
            GetQueryUsersStub::withUserIds(self::BOB_ID),
        )
        )->getUserDiff(self::WIDGET_ID, [['id' => self::ALICE_ID]]);

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            new UserDiff(
                [self::ALICE_ID],
                [self::BOB_ID]
            ),
            $result->value
        );
    }
}
