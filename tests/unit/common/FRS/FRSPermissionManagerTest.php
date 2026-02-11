<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\FRS;

use PFUser;
use PHPUnit\Framework\MockObject\Stub;
use Project;
use Project_AccessException;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FRSPermissionManagerTest extends TestCase
{
    private FRSPermissionManager $permission_manager;
    private PFUser&Stub $user;
    private Project&Stub $project;
    private FRSPermissionDao&Stub $permission_dao;
    private FRSPermissionFactory&Stub $permission_factory;
    private ProjectAccessChecker&Stub $access_checker;

    #[\Override]
    public function setUp(): void
    {
        $this->permission_dao     = $this->createStub(FRSPermissionDao::class);
        $this->permission_factory = $this->createStub(FRSPermissionFactory::class);
        $this->project            = $this->createConfiguredStub(Project::class, ['getID' => 101]);
        $this->user               = $this->createStub(PFUser::class);
        $this->access_checker     = $this->createStub(ProjectAccessChecker::class);
        $this->access_checker->method('checkUserCanAccessProject');

        $this->permission_manager = new FRSPermissionManager(
            $this->permission_dao,
            $this->permission_factory,
            $this->access_checker
        );
    }

    public function testItReturnsTrueIfUserIsProjectAdmin()
    {
        $this->user->method('isSuperUser')->willReturn(false);
        $this->user->method('isAdmin')->willReturn(true);

        self::assertTrue($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItReturnsTrueIfUserIsInFrsGroupAdmin()
    {
        $permissions = [
            '5' => new FRSPermission('5'),
            '4' => new FRSPermission('4'),
        ];

        $this->permission_factory->method('getFrsUgroupsByPermission')->willReturn($permissions);
        $this->user->method('isSuperUser')->willReturn(false);
        $this->user->method('isAdmin')->willReturn(false);
        $this->user->method('isMemberOfUGroup')->willReturnMap([
            ['5', 101, false],
            ['4', 101, true],
        ]);

        self::assertTrue($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItReturnsFalseIfUserIsNotProjectAdminAndUserIsNotInFrsGroupAdmin()
    {
        $permissions = [
            '5' => new FRSPermission('5'),
            '4' => new FRSPermission('4'),
        ];

        $this->permission_factory->method('getFrsUgroupsByPermission')->willReturn($permissions);
        $this->user->method('isSuperUser')->willReturn(false);
        $this->user->method('isAdmin')->willReturn(false);
        $this->user->method('isMemberOfUGroup')->willReturn(false);

        self::assertFalse($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItReturnsTrueIfUserIsSiteAdminAndDontHaveExplicitAccessRights()
    {
        $this->user->method('isSuperUser')->willReturn(true);

        $permissions = [
            '4' => new FRSPermission('4'),
        ];

        $this->permission_factory->method('getFrsUgroupsByPermission')->willReturn($permissions);
        $this->user->method('isAdmin')->willReturn(false);
        $this->user->method('isMemberOfUGroup')->willReturn(false);

        self::assertTrue($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItShouldNotBePossibleToAdministrateFRSIfUserCannotAccessTheProject()
    {
        $this->access_checker->method('checkUserCanAccessProject')->willThrowException($this->createStub(Project_AccessException::class));

        self::assertFalse($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItReturnsFalseIToUserCanReadfProjectLevelChecksReturnsAnException()
    {
        $this->access_checker->method('checkUserCanAccessProject')->willThrowException($this->createStub(Project_AccessException::class));

        self::assertFalse($this->permission_manager->userCanRead($this->project, $this->user));
    }

    public function testUserHasReadAccessIfTheyAreAdmin()
    {
        $permissions = [
            '4' => new FRSPermission('4'),
        ];

        $this->permission_factory->method('getFrsUgroupsByPermission')->willReturn($permissions);
        $this->user->method('isSuperUser')->willReturn(false);
        $this->user->method('isAdmin')->willReturn(false);
        $this->user->method('isMemberOfUGroup')->willReturn(true);

        self::assertTrue($this->permission_manager->userCanRead($this->project, $this->user));
    }

    public function testUserHasReadAccessIfTheyArePartOfFRSReaders()
    {
        $this->permission_dao->method('searchPermissionsForProjectByType')->willReturn(
            [
                [ 'project_id' => 101, 'permission_type' => FRSPermission::FRS_READER, 'ugroup_id' => 3],
            ]
        );
        $this->user->method('isMemberOfUGroup')->willReturn(true);
        $this->permission_factory->method('getFrsUgroupsByPermission')->willReturn([]);

        self::assertTrue($this->permission_manager->userCanRead($this->project, $this->user));
    }

    public function testUserCannotReadFRS()
    {
        $this->permission_dao->method('searchPermissionsForProjectByType')->willReturn(
            [
                [ 'project_id' => 101, 'permission_type' => FRSPermission::FRS_READER, 'ugroup_id' => 3],
            ]
        );
        $this->user->method('isMemberOfUGroup')->willReturn(false);
        $this->permission_factory->method('getFrsUgroupsByPermission')->willReturn([]);

        self::assertFalse($this->permission_manager->userCanRead($this->project, $this->user));
    }
}
