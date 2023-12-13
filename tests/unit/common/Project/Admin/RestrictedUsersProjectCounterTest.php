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

namespace Tuleap\Project\Admin;

use ForgeAccess;
use ForgeConfig;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use UserDao;

final class RestrictedUsersProjectCounterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testThereIsNoRestrictedUsersWhenRestrictedUsersAreDisabledAtTheInstanceLevel(): void
    {
        $user_dao = $this->createMock(UserDao::class);
        $counter  = new RestrictedUsersProjectCounter($user_dao);

        $project = ProjectTestBuilder::aProject()->build();

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        self::assertEquals(0, $counter->getNumberOfRestrictedUsersInProject($project));
    }

    public function testTotalNumberOfRestrictedUsersInAProjectCanBeRetrieved(): void
    {
        $user_dao = $this->createMock(UserDao::class);
        $counter  = new RestrictedUsersProjectCounter($user_dao);

        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $user_dao->method('listAllUsers')->willReturn([
            'users'   => false,
            'numrows' => 123,
        ]);

        self::assertEquals(123, $counter->getNumberOfRestrictedUsersInProject($project));
    }
}
