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

namespace Tuleap\LDAP;

use ForgeAccess;
use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\ForgeConfigSandbox;
use UserManager;

final class ProjectGroupManagerRestrictedUserFilterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testNoFilteringIsDoneWhenRestrictedUsersAreNotAllowedAtSiteLevel(): void
    {
        $filter = new ProjectGroupManagerRestrictedUserFilter(Mockery::mock(UserManager::class));

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $set_for_diff = new LDAPSetOfUserIDsForDiff([], [], []);

        $this->assertSame($set_for_diff, $filter->filter(Mockery::mock(Project::class), $set_for_diff));
    }

    public function testNoFilteringIsDoneWhenProjectAllowsRestrictedUsers(): void
    {
        $filter = new ProjectGroupManagerRestrictedUserFilter(Mockery::mock(UserManager::class));

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getAccess')->andReturn(Project::ACCESS_PUBLIC_UNRESTRICTED);

        $set_for_diff = new LDAPSetOfUserIDsForDiff([], [], []);
        $this->assertSame($set_for_diff, $filter->filter($project, $set_for_diff));
    }

    public function testRestrictedUsersAreFilteredOutTheProjectWhenRestrictedUsersAreNotAllowed(): void
    {
        $user_manager = Mockery::mock(UserManager::class);
        $filter       = new ProjectGroupManagerRestrictedUserFilter($user_manager);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getAccess')->andReturn(Project::ACCESS_PRIVATE_WO_RESTRICTED);
        $project->shouldReceive('isSuperPublic')->andReturn(false);

        $set_for_diff = new LDAPSetOfUserIDsForDiff([102, 107], [103, 105], [104, 106]);

        $user_manager->shouldReceive('getUserById')->with(102)->andReturn(
            $this->getProjectMember(102, true)
        );
        $user_manager->shouldReceive('getUserById')->with(107)->andReturn(
            $this->getProjectMember(107, false)
        );
        $member_103 = $this->getProjectMember(103, true);
        $user_manager->shouldReceive('getUserById')->with(103)->andReturn($member_103);
        $member_104 = $this->getProjectMember(104, true);
        $user_manager->shouldReceive('getUserById')->with(104)->andReturn($member_104);
        $member_105 = $this->getProjectMember(105, false);
        $user_manager->shouldReceive('getUserById')->with(105)->andReturn($member_105);
        $member_106 = $this->getProjectMember(106, false);
        $user_manager->shouldReceive('getUserById')->with(106)->andReturn($member_106);

        $project->shouldReceive('getMembers')->andReturn(
            [$member_103, $member_104, $member_105, $member_106]
        );

        $filtered_set = $filter->filter($project, $set_for_diff);
        $this->assertEqualsCanonicalizing([107], $filtered_set->getUserIDsToAdd());
        $this->assertEqualsCanonicalizing([103, 105, 104], $filtered_set->getUserIDsToRemove());
        $this->assertEqualsCanonicalizing([106], $filtered_set->getUserIDsNotImpacted());
    }

    private function getProjectMember(int $id, bool $is_restricted): PFUser
    {
        $member = Mockery::mock(PFUser::class);
        $member->shouldReceive('getId')->andReturn($id);
        $member->shouldReceive('isRestricted')->andReturn($is_restricted);

        return $member;
    }
}
