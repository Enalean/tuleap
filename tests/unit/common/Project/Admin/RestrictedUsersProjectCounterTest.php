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
use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\ForgeConfigSandbox;
use UserDao;

final class RestrictedUsersProjectCounterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testThereIsNoRestrictedUsersWhenRestrictedUsersAreDisabledAtTheInstanceLevel(): void
    {
        $user_dao = Mockery::mock(UserDao::class);
        $counter  = new RestrictedUsersProjectCounter($user_dao);

        $project = Mockery::mock(Project::class);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $this->assertEquals(0, $counter->getNumberOfRestrictedUsersInProject($project));
    }

    public function testTotalNumberOfRestrictedUsersInAProjectCanBeRetrieved(): void
    {
        $user_dao = Mockery::mock(UserDao::class);
        $counter  = new RestrictedUsersProjectCounter($user_dao);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $user_dao->shouldReceive('listAllUsers')->andReturn([
            'users'   => false,
            'numrows' => 123
        ]);

        $this->assertEquals(123, $counter->getNumberOfRestrictedUsersInProject($project));
    }
}
