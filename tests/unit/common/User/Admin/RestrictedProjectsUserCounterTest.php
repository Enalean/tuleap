<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\User\Admin;

use ForgeAccess;
use ForgeConfig;
use TestHelper;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use UserGroupDao;

final class RestrictedProjectsUserCounterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testThereIsNoProjectNotAllowingRestrictedWhenRestrictedUsersAreDisabledAtTheInstanceLevel(): void
    {
        $user_group_dao = $this->createMock(UserGroupDao::class);
        $counter        = new RestrictedProjectsUserCounter($user_group_dao);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $user = UserTestBuilder::aUser()->build();

        self::assertEquals(0, $counter->getNumberOfProjectsNotAllowingRestrictedTheUserIsMemberOf($user));
    }

    public function testTotalNumberOfProjectsNotAllowingRestrictedTheUserIsMemberOf(): void
    {
        $user_group_dao = $this->createMock(UserGroupDao::class);
        $counter        = new RestrictedProjectsUserCounter($user_group_dao);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $user = UserTestBuilder::aUser()->withId(400)->build();

        $dar = TestHelper::arrayToDar([1 => 'row1']);
        $user_group_dao->method('searchActiveProjectsByUserIdAndAccessType')->willReturn($dar);

        self::assertEquals(1, $counter->getNumberOfProjectsNotAllowingRestrictedTheUserIsMemberOf($user));
    }
}
