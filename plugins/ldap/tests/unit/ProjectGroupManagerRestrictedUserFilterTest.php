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
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\ForgeConfigSandbox;
use UserManager;

final class ProjectGroupManagerRestrictedUserFilterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testNoFilteringIsDoneWhenRestrictedUsersAreNotAllowedAtSiteLevel(): void
    {
        $filter = new ProjectGroupManagerRestrictedUserFilter($this->createMock(UserManager::class));

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $set_for_diff = new LDAPSetOfUserIDsForDiff([], [], []);

        self::assertSame($set_for_diff, $filter->filter($this->createMock(Project::class), $set_for_diff));
    }

    public function testNoFilteringIsDoneWhenProjectAllowsRestrictedUsers(): void
    {
        $filter = new ProjectGroupManagerRestrictedUserFilter($this->createMock(UserManager::class));

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = $this->createMock(Project::class);
        $project->method('getAccess')->willReturn(Project::ACCESS_PUBLIC_UNRESTRICTED);

        $set_for_diff = new LDAPSetOfUserIDsForDiff([], [], []);
        self::assertSame($set_for_diff, $filter->filter($project, $set_for_diff));
    }

    public function testRestrictedUsersAreFilteredOutTheProjectWhenRestrictedUsersAreNotAllowed(): void
    {
        $user_manager = $this->createMock(UserManager::class);
        $filter       = new ProjectGroupManagerRestrictedUserFilter($user_manager);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = $this->createMock(Project::class);
        $project->method('getAccess')->willReturn(Project::ACCESS_PRIVATE_WO_RESTRICTED);
        $project->method('isSuperPublic')->willReturn(false);

        $set_for_diff = new LDAPSetOfUserIDsForDiff([102, 107], [103, 105], [104, 106]);

        $member_103 = $this->getProjectMember(103, true);
        $member_104 = $this->getProjectMember(104, true);
        $member_105 = $this->getProjectMember(105, false);
        $member_106 = $this->getProjectMember(106, false);

        $user_manager->method('getUserById')->willReturnMap([
            [102, $this->getProjectMember(102, true)],
            [107, $this->getProjectMember(107, false)],
            [103, $member_103],
            [104, $member_104],
            [105, $member_105],
            [106, $member_106],
        ]);

        $project->method('getMembers')->willReturn(
            [$member_103, $member_104, $member_105, $member_106]
        );

        $filtered_set = $filter->filter($project, $set_for_diff);
        self::assertEqualsCanonicalizing([107], $filtered_set->getUserIDsToAdd());
        self::assertEqualsCanonicalizing([103, 105, 104], $filtered_set->getUserIDsToRemove());
        self::assertEqualsCanonicalizing([106], $filtered_set->getUserIDsNotImpacted());
    }

    private function getProjectMember(int $id, bool $is_restricted): MockObject&PFUser
    {
        $member = $this->createMock(PFUser::class);
        $member->method('getId')->willReturn($id);
        $member->method('isRestricted')->willReturn($is_restricted);

        return $member;
    }
}
