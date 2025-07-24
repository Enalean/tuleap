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
use Tuleap\Timetracking\Tests\Stub\GetViewableUserStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FromPayloadUserListBuilderTest extends TestCase
{
    private const ALICE_ID   = 101;
    private const BOB_ID     = 102;
    private const CHARLIE_ID = 103;

    private \PFUser $alice;
    private \PFUser $bob;

    #[\Override]
    protected function setUp(): void
    {
        $this->alice = UserTestBuilder::aUser()->withId(self::ALICE_ID)->build();
        $this->bob   = UserTestBuilder::aUser()->withId(self::BOB_ID)->build();
    }

    public function testItIgnoresWhenUserIdDoesNotMatchActiveUser(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();

        $result = (
            new FromPayloadUserListBuilder(
                GetViewableUserStub::withViewableUsers($this->alice, $this->bob),
            )
        )->getUserList(
            $current_user,
            [
                QueryUserRepresentation::fromId(self::ALICE_ID),
                QueryUserRepresentation::fromId(self::CHARLIE_ID),
            ],
        );

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            new UserList(
                [$this->alice],
                [],
                [self::CHARLIE_ID],
            ),
            $result->value
        );
    }

    public function testItReturnsAUserListWhenValidUsersAreProvided(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();

        $check_that_user_is_active = GetViewableUserStub::withViewableUsers($this->alice, $this->bob);

        $builder = new FromPayloadUserListBuilder($check_that_user_is_active);
        $result  = $builder->getUserList(
            $current_user,
            [
                QueryUserRepresentation::fromId(self::ALICE_ID),
                QueryUserRepresentation::fromId(self::BOB_ID),
            ],
        );

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            new UserList(
                [$this->alice, $this->bob],
                [],
                [],
            ),
            $result->value
        );
    }

    public function testItReturnsAUserListWhenValidUsersAreProvidedButSomeAreNotViewable(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();

        $check_that_user_is_active = GetViewableUserStub::withViewableUsers($this->alice)
            ->andNotViewableUsers($this->bob);

        $builder = new FromPayloadUserListBuilder($check_that_user_is_active);
        $result  = $builder->getUserList(
            $current_user,
            [
                QueryUserRepresentation::fromId(self::ALICE_ID),
                QueryUserRepresentation::fromId(self::BOB_ID),
            ],
        );

        self::assertTrue(Result::isOk($result));
        self::assertEquals(
            new UserList(
                [$this->alice],
                [$this->bob],
                [],
            ),
            $result->value
        );
    }
}
