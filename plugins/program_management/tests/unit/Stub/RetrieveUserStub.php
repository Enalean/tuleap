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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrieveUserStub implements RetrieveUser
{
    private \PFUser $user;

    private function __construct(\PFUser $user)
    {
        $this->user = $user;
    }

    /**
     * @var \PFUser|\PHPUnit\Framework\MockObject\MockObject
     */
    public static function buildMockedRegularUser($user): self
    {
        $user->method('isAdmin')->willReturn(false);
        $user->method('isMemberOfUGroup')->willReturn(false);

        return new self($user);
    }

    public function getUserWithId(UserIdentifier $user_identifier): \PFUser
    {
        return $this->user;
    }

    /**
     * @var \PFUser|\PHPUnit\Framework\MockObject\MockObject
     */
    public static function buildMockedMemberOfUGroupUser($user): self
    {
        $user->method('isAdmin')->willReturn(false);
        $user->method('isMemberOfUGroup')->willReturn(true);

        return new self($user);
    }

    /**
     * @var \PFUser|\PHPUnit\Framework\MockObject\MockObject
     */
    public static function buildMockedAdminUser($user): self
    {
        $user->method('isAdmin')->willReturn(true);

        return new self($user);
    }

    public static function withUser(\PFUser $user): self
    {
        return new self($user);
    }
}
