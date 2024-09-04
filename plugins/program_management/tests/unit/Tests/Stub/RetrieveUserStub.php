<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Test\Builders\UserTestBuilder;

final class RetrieveUserStub implements RetrieveUser
{
    private function __construct(private \PFUser $user)
    {
    }

    /**
     * @param \PFUser&\PHPUnit\Framework\MockObject\MockObject $user
     */
    public static function buildMockedRegularUser($user): self
    {
        $user->method('isAdmin')->willReturn(false);
        $user->method('isMemberOfUGroup')->willReturn(false);
        $user->method('getRealName')->willReturn('John');
        $user->method('getId')->willReturn(101);

        return new self($user);
    }

    public function getUserWithId(UserIdentifier $user_identifier): \PFUser
    {
        return $this->user;
    }

    /**
     * @param \PFUser&\PHPUnit\Framework\MockObject\MockObject $user
     */
    public static function buildMockedMemberOfUGroupUser($user): self
    {
        $user->method('isAdmin')->willReturn(false);
        $user->method('isMemberOfUGroup')->willReturn(true);
        $user->method('getId')->willReturn(101);

        return new self($user);
    }

    /**
     * @param \PFUser&\PHPUnit\Framework\MockObject\MockObject $user
     */
    public static function buildMockedAdminUser($user): self
    {
        $user->method('isAdmin')->willReturn(true);
        $user->method('getId')->willReturn(101);

        return new self($user);
    }

    /**
     * @param \PFUser&\PHPUnit\Framework\MockObject\MockObject $user
     */
    public static function buildMockedSuperUser($user): self
    {
        $user->method('isSuperUser')->willReturn(true);
        $user->method('getId')->willReturn(101);

        return new self($user);
    }

    /**
     * @param \PFUser&\PHPUnit\Framework\MockObject\MockObject $user
     */
    public static function buildUserWhoCanAccessProjectAndIsProjectAdmin($user): self
    {
        $user->method('isAdmin')->willReturn(true);
        $user->method('getId')->willReturn(101);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('isMember')->willReturn(true);

        return new self($user);
    }

    public static function withUser(\PFUser $user): self
    {
        return new self($user);
    }

    public static function withGenericUser(): self
    {
        return new self(UserTestBuilder::aUser()->withId(101)->build());
    }
}
