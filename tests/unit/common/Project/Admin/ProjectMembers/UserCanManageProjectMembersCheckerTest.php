<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectMembers;

use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class UserCanManageProjectMembersCheckerTest extends TestCase
{
    public function testSuccessIfUserIsProjectAdmin(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(111)->build();

        $user = UserTestBuilder::aUser()
            ->withId(101)
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();

        (new UserCanManageProjectMembersChecker(
            $this->createMock(MembershipDelegationDao::class),
        ))->checkUserCanManageProjectMembers($user, $project);

        $this->expectNotToPerformAssertions();
    }

    public function testSuccessIfUserHasPermissionDelegation(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(111)->build();

        $user = UserTestBuilder::aUser()
            ->withId(101)
            ->withoutSiteAdministrator()
            ->withoutMemberOfProjects()
            ->build();

        $delegation_dao = $this->createMock(MembershipDelegationDao::class);
        $delegation_dao->method('doesUserHasMembershipDelegation')->willReturn(true);

        (new UserCanManageProjectMembersChecker(
            $delegation_dao,
        ))->checkUserCanManageProjectMembers($user, $project);

        $this->expectNotToPerformAssertions();
    }

    public function testExceptionIfUserIsNotProjectAdminNorHasPermissionDelegation(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(111)->build();

        $user = UserTestBuilder::aUser()
            ->withId(101)
            ->withoutSiteAdministrator()
            ->withoutMemberOfProjects()
            ->build();

        $delegation_dao = $this->createMock(MembershipDelegationDao::class);
        $delegation_dao->method('doesUserHasMembershipDelegation')->willReturn(false);

        self::expectException(UserIsNotAllowedToManageProjectMembersException::class);

        (new UserCanManageProjectMembersChecker(
            $delegation_dao,
        ))->checkUserCanManageProjectMembers($user, $project);
    }

    public function testExceptionIfUserIsProjectAdminButProjectIsNotImportedYet(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(111)
            ->withStatusCreatingFromArchive()
            ->build();

        $user = UserTestBuilder::aUser()
            ->withId(101)
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();

        self::expectException(UserIsNotAllowedToManageProjectMembersException::class);

        (new UserCanManageProjectMembersChecker(
            $this->createMock(MembershipDelegationDao::class),
        ))->checkUserCanManageProjectMembers($user, $project);
    }
}
