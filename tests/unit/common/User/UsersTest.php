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

use Tuleap\Test\Builders\UserTestBuilder;
use Users;

final class UsersTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExtractsUserNames(): void
    {
        $users = new Users(UserTestBuilder::aUser()->withUserName('user1')->build(), UserTestBuilder::aUser()->withUserName('user2')->build());

        self::assertEqualsCanonicalizing(
            ['user1', 'user2'],
            $users->getNames(),
        );
    }
}
