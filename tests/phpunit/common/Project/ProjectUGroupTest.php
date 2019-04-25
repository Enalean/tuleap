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

namespace Tuleap\Project;

use ForgeAccess;
use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use ProjectUGroup;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;

final class ProjectUGroupTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    protected function tearDown() : void
    {
        ProjectManager::clearInstance();
    }

    public function testCannotAddRestrictedUserInAUserGroupOfAProjectNotAllowingRestricted() : void
    {
        $project_ugroup = new ProjectUGroup(['ugroup_id' => 200, 'group_id' => 300]);

        $restricted_user = Mockery::mock(PFUser::class);
        $restricted_user->shouldReceive('getId')->andReturn(101);
        $restricted_user->shouldReceive('isAnonymous')->andReturn(false);
        $restricted_user->shouldReceive('isRestricted')->andReturn(true);

        $project_not_allowing_restricted = Mockery::mock(Project::class);
        $project_not_allowing_restricted->shouldReceive('getID')->andReturn(300);
        $project_not_allowing_restricted->shouldReceive('getAccess')->andReturn(Project::ACCESS_PRIVATE_WO_RESTRICTED);

        $project_manager = Mockery::mock(ProjectManager::class);
        $project_manager->shouldReceive('getProject')->with(300)->andReturn($project_not_allowing_restricted);
        ProjectManager::setInstance($project_manager);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->expectException(CannotAddRestrictedUserToProjectNotAllowingRestricted::class);
        $project_ugroup->addUser($restricted_user);
    }
}
