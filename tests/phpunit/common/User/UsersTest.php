<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\User;

use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Users;

final class UsersTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItExtractsUserNames(): void
    {
        $users = new Users($this->getUserWithUsername('user1'), $this->getUserWithUsername('user2'));

        $this->assertEquals(['user1', 'user2'], $users->getNames());
    }

    private function getUserWithUsername(string $username): PFUser
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getUserName')->andReturn($username);
        return $user;
    }
}
