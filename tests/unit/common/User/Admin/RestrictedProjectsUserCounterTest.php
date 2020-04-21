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
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use TestHelper;
use Tuleap\ForgeConfigSandbox;
use UserGroupDao;

final class RestrictedProjectsUserCounterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testThereIsNoProjectNotAllowingRestrictedWhenRestrictedUsersAreDisabledAtTheInstanceLevel(): void
    {
        $user_group_dao = Mockery::mock(UserGroupDao::class);
        $counter         = new RestrictedProjectsUserCounter($user_group_dao);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $user = Mockery::mock(PFUser::class);

        $this->assertEquals(0, $counter->getNumberOfProjectsNotAllowingRestrictedTheUserIsMemberOf($user));
    }

    public function testTotalNumberOfProjectsNotAllowingRestrictedTheUserIsMemberOf(): void
    {
        $user_group_dao = Mockery::mock(UserGroupDao::class);
        $counter        = new RestrictedProjectsUserCounter($user_group_dao);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(400);

        $dar = TestHelper::arrayToDar([1 => 'row1']);
        $user_group_dao->shouldReceive('searchActiveProjectsByUserIdAndAccessType')->andReturn($dar);

        $this->assertEquals(1, $counter->getNumberOfProjectsNotAllowingRestrictedTheUserIsMemberOf($user));
    }
}
