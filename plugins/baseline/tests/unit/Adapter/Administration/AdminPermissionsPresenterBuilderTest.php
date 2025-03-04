<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter\Administration;

use Tuleap\Baseline\Stub\RoleAssignmentRepositoryStub;
use Tuleap\Baseline\Support\RoleAssignmentTestBuilder;
use Tuleap\Baseline\Domain\RoleBaselineAdmin;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class AdminPermissionsPresenterBuilderTest extends TestCase
{
    public function testGetPresenter(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $ugroup_factory = $this->createMock(\User_ForgeUserGroupFactory::class);
        $ugroup_factory
            ->method('getProjectUGroupsWithMembersWithoutNobody')
            ->willReturn([
                new \User_ForgeUGroup(\ProjectUGroup::PROJECT_MEMBERS, 'Project members', ''),
                new \User_ForgeUGroup(104, 'Developers', ''),
                new \User_ForgeUGroup(105, 'Integrators', ''),
                new \User_ForgeUGroup(106, 'QA', ''),
            ]);

        $builder = new AdminPermissionsPresenterBuilder(
            $ugroup_factory,
            RoleAssignmentRepositoryStub::withRoles(
                RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())->withUserGroups(
                    ProjectUGroupTestBuilder::aCustomUserGroup(104)->build(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(105)->build()
                )->build(),
                RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())->withUserGroups(
                    ProjectUGroupTestBuilder::aCustomUserGroup(105)->build(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(106)->build()
                )->build()
            ),
        );

        $csrf_token = CSRFSynchronizerTokenStub::buildSelf();
        $presenter  = $builder->getPresenter($project, '/admin/url', $csrf_token);

        self::assertEquals('/admin/url', $presenter->post_url);
        self::assertSame($csrf_token, $presenter->csrf_token);
        self::assertSame(
            [\ProjectUGroup::PROJECT_MEMBERS, 104, 105, 106],
            array_map(
                static fn(UgroupPresenter $presenter) => $presenter->id,
                $presenter->administrators
            )
        );
        self::assertSame(
            [false, true, true, false],
            array_map(
                static fn(UgroupPresenter $presenter) => $presenter->is_selected,
                $presenter->administrators
            )
        );
        self::assertSame(
            [\ProjectUGroup::PROJECT_MEMBERS, 104, 105, 106],
            array_map(
                static fn(UgroupPresenter $presenter) => $presenter->id,
                $presenter->readers
            )
        );
        self::assertSame(
            [false, false, true, true],
            array_map(
                static fn(UgroupPresenter $presenter) => $presenter->is_selected,
                $presenter->readers
            )
        );
    }
}
