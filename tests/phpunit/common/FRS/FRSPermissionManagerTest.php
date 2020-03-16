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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project_AccessException;
use Tuleap\Project\ProjectAccessChecker;

class FRSPermissionManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FRSPermissionManager
     */
    private $permission_manager;
    /**
     * @var \Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var \Mockery\MockInterface|\Project
     */
    private $project;
    /**
     * @var \Mockery\MockInterface|FRSPermissionDao
     */
    private $permission_dao;
    /**
     * @var \Mockery\MockInterface|FRSPermissionFactory
     */
    private $permission_factory;
    /**
     * @var \Mockery\MockInterface|ProjectAccessChecker
     */
    private $access_checker;

    public function setUp() : void
    {
        $this->permission_dao     = \Mockery::mock(\Tuleap\FRS\FRSPermissionDao::class);
        $this->permission_factory = \Mockery::mock(\Tuleap\FRS\FRSPermissionFactory::class);
        $this->project            = \Mockery::mock(\Project::class, ['getID' => 101]);
        $this->user               = \Mockery::mock(\PFUser::class, ['isSuperUser' => false, 'isAdmin' => false]);
        $this->access_checker     = \Mockery::mock(ProjectAccessChecker::class, ['checkUserCanAccessProject' => null]);

        $this->permission_manager = new FRSPermissionManager(
            $this->permission_dao,
            $this->permission_factory,
            $this->access_checker
        );
    }

    public function testItReturnsTrueIfUserIsProjectAdmin()
    {
        $this->user->shouldReceive('isAdmin')->andReturns(true);

        $this->assertTrue($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItRetrunsTrueIfUserIsInFrsGroupAdmin()
    {
        $permissions = array(
            '5' => new FRSPermission('5'),
            '4' => new FRSPermission('4')
        );

        $this->permission_factory->shouldReceive('getFrsUgroupsByPermission')->with($this->project, FRSPermission::FRS_ADMIN)->andReturns($permissions);
        $this->user->shouldReceive('isMemberOfUGroup')->with(5, 101)->andReturns(false);
        $this->user->shouldReceive('isMemberOfUGroup')->with(4, 101)->andReturns(true);

        $this->assertTrue($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItReturnsFalseIfUserIsNotProjectAdminAndUserIsNotInFrsGroupAdmin()
    {
        $permissions = array(
            '5' => new FRSPermission('5'),
            '4' => new FRSPermission('4')
        );

        $this->permission_factory->shouldReceive('getFrsUgroupsByPermission')->with($this->project, FRSPermission::FRS_ADMIN)->andReturns($permissions);
        $this->user->shouldReceive('isMemberOfUGroup')->andReturns(false);

        $this->assertFalse($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItReturnsTrueIfUserIsSiteAdminAndDontHaveExplicitAccessRights()
    {
        $this->user->shouldReceive('isSuperUser')->andReturns(true);

        $permissions = array(
            '4' => new FRSPermission('4')
        );

        $this->permission_factory->shouldReceive('getFrsUgroupsByPermission')->with($this->project, FRSPermission::FRS_ADMIN)->andReturns($permissions);
        $this->user->shouldReceive('isMemberOfUGroup')->andReturns(false);

        $this->assertTrue($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItShouldNotBePossibleToAdministrateFRSIfUserCannotAccessTheProject()
    {
        $this->access_checker->shouldReceive('checkUserCanAccessProject')->with($this->user, $this->project)->andThrow(\Mockery::mock(Project_AccessException::class));

        $this->assertFalse($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function testItReturnsFalseIToUserCanReadfProjectLevelChecksReturnsAnException()
    {
        $this->access_checker->shouldReceive('checkUserCanAccessProject')->with($this->user, $this->project)->andThrow(\Mockery::mock(Project_AccessException::class));

        $this->assertFalse($this->permission_manager->userCanRead($this->project, $this->user));
    }

    public function testUserHasReadAccessIfTheyAreAdmin()
    {
        $permissions = array(
            '4' => new FRSPermission('4')
        );

        $this->permission_factory->shouldReceive('getFrsUgroupsByPermission')->with($this->project, FRSPermission::FRS_ADMIN)->andReturns($permissions);
        $this->user->shouldReceive('isMemberOfUGroup')->with(4, 101)->andReturns(true);

        $this->assertTrue($this->permission_manager->userCanRead($this->project, $this->user));
    }

    public function testUserHasReadAccessIfTheyArePartOfFRSReaders()
    {
        $this->permission_dao->shouldReceive('searchPermissionsForProjectByType')->with(101, FRSPermission::FRS_READER)->andReturns(
            [
                [ 'project_id' => 101, 'permission_type' => FRSPermission::FRS_READER, 'ugroup_id' => 3]
            ]
        );
        $this->user->shouldReceive('isMemberOfUGroup')->with(3, 101)->andReturns(true);

        $this->assertTrue($this->permission_manager->userCanRead($this->project, $this->user));
    }

    public function testUserCannotReadFRS()
    {
        $this->permission_dao->shouldReceive('searchPermissionsForProjectByType')->with(101, FRSPermission::FRS_READER)->andReturns(
            [
                [ 'project_id' => 101, 'permission_type' => FRSPermission::FRS_READER, 'ugroup_id' => 3]
            ]
        );
        $this->user->shouldReceive('isMemberOfUGroup')->andReturns(false);

        $this->assertFalse($this->permission_manager->userCanRead($this->project, $this->user));
    }
}
