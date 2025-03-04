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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Tuleap\MediawikiStandalone\Permissions\ISearchByProjectStub;
use Tuleap\MediawikiStandalone\Permissions\ProjectPermissionsRetriever;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class AdminPermissionsPresenterBuilderTest extends TestCase
{
    public function testGetPresenter(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $ugroups = [
            new \User_ForgeUGroup(\ProjectUGroup::PROJECT_MEMBERS, 'Project members', ''),
            new \User_ForgeUGroup(104, 'Developers', ''),
            new \User_ForgeUGroup(105, 'Integrators', ''),
            new \User_ForgeUGroup(106, 'QA', ''),
        ];

        $ugroup_factory = $this->createMock(\User_ForgeUserGroupFactory::class);
        $ugroup_factory
            ->method('getAllForProjectWithoutNobody')
            ->willReturn([
                new \User_ForgeUGroup(\ProjectUGroup::ANONYMOUS, 'All users', ''),
                new \User_ForgeUGroup(\ProjectUGroup::REGISTERED, 'Registered users', ''),
                ...$ugroups,
            ]);
        $ugroup_factory
            ->method('getAllForProjectWithoutNobodyNorAnonymous')
            ->willReturn([
                new \User_ForgeUGroup(\ProjectUGroup::REGISTERED, 'Registered users', ''),
                ...$ugroups,
            ]);
        $ugroup_factory
            ->method('getProjectUGroupsWithMembersWithoutNobody')
            ->willReturn($ugroups);

        $dao = ISearchByProjectStub::buildWithPermissions(
            [104, 105],
            [\ProjectUGroup::PROJECT_MEMBERS, 106],
            [105],
        );

        $builder = new AdminPermissionsPresenterBuilder(
            new ProjectPermissionsRetriever($dao),
            $ugroup_factory,
        );

        $csrf_token = CSRFSynchronizerTokenStub::buildSelf();
        $presenter  = $builder->getPresenter($project, '/admin/url', $csrf_token);

        self::assertEquals('/admin/url', $presenter->post_url);
        self::assertSame($csrf_token, $presenter->csrf_token);

        self::assertSame(
            [\ProjectUGroup::ANONYMOUS, \ProjectUGroup::REGISTERED, \ProjectUGroup::PROJECT_MEMBERS, 104, 105, 106],
            array_map(
                static fn(UserGroupPresenter $presenter) => $presenter->id,
                $presenter->readers
            )
        );
        self::assertSame(
            [false, false, false, true, true, false],
            array_map(
                static fn(UserGroupPresenter $presenter) => $presenter->is_selected,
                $presenter->readers
            )
        );

        self::assertSame(
            [\ProjectUGroup::REGISTERED, \ProjectUGroup::PROJECT_MEMBERS, 104, 105, 106],
            array_map(
                static fn(UserGroupPresenter $presenter) => $presenter->id,
                $presenter->writers
            )
        );
        self::assertSame(
            [false, true, false, false, true],
            array_map(
                static fn(UserGroupPresenter $presenter) => $presenter->is_selected,
                $presenter->writers
            )
        );

        self::assertSame(
            [\ProjectUGroup::PROJECT_MEMBERS, 104, 105, 106],
            array_map(
                static fn(UserGroupPresenter $presenter) => $presenter->id,
                $presenter->admins
            )
        );
        self::assertSame(
            [false, false, true, false],
            array_map(
                static fn(UserGroupPresenter $presenter) => $presenter->is_selected,
                $presenter->admins
            )
        );
    }
}
