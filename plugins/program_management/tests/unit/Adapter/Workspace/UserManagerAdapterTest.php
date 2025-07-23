<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Domain\Workspace\UserNotFoundException;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserManagerAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID = 170;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\UserManager
     */
    private $user_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->user_manager = $this->createStub(\UserManager::class);
    }

    private function getAdapter(): UserManagerAdapter
    {
        return new UserManagerAdapter($this->user_manager);
    }

    public function testItReturnsPFUserFromUserIdentifier(): void
    {
        $pfuser = UserTestBuilder::aUser()->withId(self::USER_ID)->build();
        $this->user_manager->method('getUserById')->willReturn($pfuser);
        self::assertSame($pfuser, $this->getAdapter()->getUserWithId(UserIdentifierStub::withId(self::USER_ID)));
    }

    public function testItThrowsExceptionWhenUserIdentifierDoesNotMatchUser(): void
    {
        // It should not happen as we should always verify a given id matches a user
        $this->user_manager->method('getUserById')->willReturn(null);
        $this->expectException(UserNotFoundException::class);
        $this->getAdapter()->getUserWithId(UserIdentifierStub::withId(self::USER_ID));
    }

    public function testItReturnsTrueWhenIdMatchesAUser(): void
    {
        $pfuser = UserTestBuilder::aUser()->withId(self::USER_ID)->build();
        $this->user_manager->method('getUserById')->willReturn($pfuser);
        self::assertTrue($this->getAdapter()->isUser(self::USER_ID));
    }

    public function testItReturnsFalseWhenIdDoesNotMatchAnyUser(): void
    {
        $this->user_manager->method('getUserById')->willReturn(null);
        self::assertFalse($this->getAdapter()->isUser(self::USER_ID));
    }
}
