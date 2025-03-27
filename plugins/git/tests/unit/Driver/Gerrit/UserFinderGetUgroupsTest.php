<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Driver\Gerrit;

use Git;
use Git_Driver_Gerrit_UserFinder;
use PermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use TestHelper;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserFinderGetUgroupsTest extends TestCase
{
    private PermissionsManager&MockObject $permissions_manager;
    private Git_Driver_Gerrit_UserFinder $user_finder;

    protected function setUp(): void
    {
        $this->permissions_manager = $this->createMock(PermissionsManager::class);
        $this->user_finder         = new Git_Driver_Gerrit_UserFinder($this->permissions_manager);
    }

    public function testItAsksPermissionsToPermissionsManager(): void
    {
        $repository_id   = 12;
        $permission_type = Git::PERM_READ;

        $this->permissions_manager->expects($this->once())->method('getAuthorizedUgroups')
            ->with($repository_id, $permission_type, false)
            ->willReturn(TestHelper::emptyDar());

        $this->user_finder->getUgroups($repository_id, $permission_type);
    }

    public function testItReturnsUGroupIdsFromPermissionsManager(): void
    {
        $ugroup_id_120 = 120;
        $ugroup_id_115 = 115;
        $this->permissions_manager->method('getAuthorizedUgroups')
            ->willReturn(TestHelper::arrayToDar(['ugroup_id' => $ugroup_id_115], ['ugroup_id' => $ugroup_id_120]));

        $ugroups = $this->user_finder->getUgroups('whatever', 'whatever');
        self::assertEquals([
            $ugroup_id_115,
            $ugroup_id_120,
        ], $ugroups);
    }

    public function testItAlwaysReturnsTheProjectAdminGroupWhenGitAdministratorsAreRequested(): void
    {
        $project_admin_group_id = ProjectUGroup::PROJECT_ADMIN;

        $expected_ugroups = [$project_admin_group_id];
        $ugroups          = $this->user_finder->getUgroups('whatever', Git::SPECIAL_PERM_ADMIN);

        self::assertEquals($expected_ugroups, $ugroups);
    }

    public function testItDoesntJoinWithUGroupTableWhenItFetchesGroupPermissionsInOrderToReturnSomethingWhenWeAreDeletingTheGroup(): void
    {
        $this->permissions_manager->expects($this->once())->method('getAuthorizedUgroups')
            ->with(self::anything(), self::anything(), false)
            ->willReturn(TestHelper::emptyDar());

        $this->user_finder->getUgroups('whatever', 'whatever');
    }
}
