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
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Project_AccessException;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\PHPUnit\TestCase;

class FRSPermissionManagerTest extends TestCase
{
    private FRSPermissionManager $permission_manager;
    /**
     * @var MockObject&PFUser
     */
    private $user;
    /**
     * @var MockObject&Project
     */
    private $project;
    /**
     * @var MockObject&FRSPermissionDao
     */
    private $permission_dao;
    /**
     * @var MockObject&FRSPermissionFactory
     */
    private $permission_factory;
    /**
     * @var MockObject&ProjectAccessChecker
     */
    private $access_checker;

    public function setUp(): void
    {
        $this->permission_dao     = $this->createMock(FRSPermissionDao::class);
        $this->permission_factory = $this->createMock(FRSPermissionFactory::class);
        $this->project            = $this->createConfiguredMock(Project::class, ['getID' => 101]);
        $this->user               = $this->createMock(PFUser::class);
        $this->access_checker     = $this->createMock(ProjectAccessChecker::class);
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

        $this->permission_factory->method('getFrsUgroupsByPermission')->with($this->project, FRSPermission::FRS_ADMIN)->willReturn($permissions);
        $this->user->method('isSuperUser')->willReturn(false);
        $this->user->method('isAdmin')->willReturn(false);
        $this->user->method('isMemberOfUGroup')->withConsecutive(
            [5, 101],
            [4, 101]
        )->willReturnOnConsecutiveCalls(false, true);

        self::assertTrue($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItReturnsFalseIfUserIsNotProjectAdminAndUserIsNotInFrsGroupAdmin()
    {
        $permissions = [
            '5' => new FRSPermission('5'),
            '4' => new FRSPermission('4'),
        ];

        $this->permission_factory->method('getFrsUgroupsByPermission')->with($this->project, FRSPermission::FRS_ADMIN)->willReturn($permissions);
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

        $this->permission_factory->method('getFrsUgroupsByPermission')->with($this->project, FRSPermission::FRS_ADMIN)->willReturn($permissions);
        $this->user->method('isAdmin')->willReturn(false);
        $this->user->method('isMemberOfUGroup')->willReturn(false);

        self::assertTrue($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItShouldNotBePossibleToAdministrateFRSIfUserCannotAccessTheProject()
    {
        $this->access_checker->method('checkUserCanAccessProject')->with($this->user, $this->project)->willThrowException($this->createMock(Project_AccessException::class));

        self::assertFalse($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItReturnsFalseIToUserCanReadfProjectLevelChecksReturnsAnException()
    {
        $this->access_checker->method('checkUserCanAccessProject')->with($this->user, $this->project)->willThrowException($this->createMock(Project_AccessException::class));

        self::assertFalse($this->permission_manager->userCanRead($this->project, $this->user));
    }

    public function testUserHasReadAccessIfTheyAreAdmin()
    {
        $permissions = [
            '4' => new FRSPermission('4'),
        ];

        $this->permission_factory->method('getFrsUgroupsByPermission')->with($this->project, FRSPermission::FRS_ADMIN)->willReturn($permissions);
        $this->user->method('isSuperUser')->willReturn(false);
        $this->user->method('isAdmin')->willReturn(false);
        $this->user->method('isMemberOfUGroup')->with(4, 101)->willReturn(true);

        self::assertTrue($this->permission_manager->userCanRead($this->project, $this->user));
    }

    public function testUserHasReadAccessIfTheyArePartOfFRSReaders()
    {
        $this->permission_dao->method('searchPermissionsForProjectByType')->with(101, FRSPermission::FRS_READER)->willReturn(
            [
                [ 'project_id' => 101, 'permission_type' => FRSPermission::FRS_READER, 'ugroup_id' => 3],
            ]
        );
        $this->user->method('isMemberOfUGroup')->with(3, 101)->willReturn(true);

        self::assertTrue($this->permission_manager->userCanRead($this->project, $this->user));
    }

    public function testUserCannotReadFRS()
    {
        $this->permission_dao->method('searchPermissionsForProjectByType')->with(101, FRSPermission::FRS_READER)->willReturn(
            [
                [ 'project_id' => 101, 'permission_type' => FRSPermission::FRS_READER, 'ugroup_id' => 3],
            ]
        );
        $this->user->method('isMemberOfUGroup')->willReturn(false);

        self::assertFalse($this->permission_manager->userCanRead($this->project, $this->user));
    }
}
