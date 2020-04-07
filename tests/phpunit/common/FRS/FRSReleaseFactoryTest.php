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
 *
 */

class FRSReleaseFactoryTest extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected $group_id   = 12;
    protected $package_id = 34;
    protected $release_id = 56;
    protected $user_id    = 78;

    private $user;
    private $frs_release_factory;
    private $user_manager;
    private $permission_manager;

    protected function setUp(): void
    {
        $this->user  = \Mockery::spy(\PFUser::class);
        $this->frs_release_factory = \Mockery::mock(\FRSReleaseFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->user_manager        = \Mockery::spy(\UserManager::class);
        $this->permission_manager  = \Mockery::spy(\PermissionsManager::class);
        $this->user_manager->shouldReceive('getUserById')->andReturns($this->user);
        $this->frs_release_factory->shouldReceive('getUserManager')->andReturns($this->user_manager);
        $project = Mockery::spy(Project::class);
        $project->shouldReceive('getID')->andReturn($this->group_id);
        $project->shouldReceive('isActive')->andReturn(true);
        $project->shouldReceive('isPublic')->andReturn(true);
        $project_manager = Mockery::mock(ProjectManager::class);
        $project_manager->shouldReceive('getProject')->andReturn($project);
        ProjectManager::setInstance($project_manager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        ProjectManager::clearInstance();
    }

    public function testAdminHasAlwaysAccessToReleases()
    {
        $this->frs_release_factory->shouldReceive('userCanAdmin')->andReturns(true);
        $this->assertTrue($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    protected function _userCanReadWhenNoPermsOnRelease($canReadPackage)
    {
        $this->frs_release_factory->shouldReceive('userCanAdmin')->andReturns(false);
        $this->permission_manager->shouldReceive('isPermissionExist')->with($this->release_id, 'RELEASE_READ')->once()->andReturns(false);
        $this->frs_release_factory->shouldReceive('getPermissionsManager')->andReturns($this->permission_manager);

        $frs_package_factory = \Mockery::spy(\FRSPackageFactory::class);
        $frs_package_factory->shouldReceive('userCanRead')->with($this->group_id, $this->package_id, null)->once()->andReturns($canReadPackage);
        $this->frs_release_factory->shouldReceive('_getFRSPackageFactory')->andReturns($frs_package_factory);

        return $this->frs_release_factory;
    }

    public function testUserCanReadWhenNoPermsOnReleaseButCanReadPackage()
    {
        $this->_userCanReadWhenNoPermsOnRelease(true);
        $this->assertTrue($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    public function testUserCanReadWhenNoPermsOnReleaseButCannotReadPackage()
    {
        $this->_userCanReadWhenNoPermsOnRelease(false);
        $this->assertFalse($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    protected function _userCanReadWithSpecificPerms($can_read_release)
    {
        $this->user->shouldReceive('getUgroups')->with($this->group_id, array())->once()->andReturns(array(1,2,76));
        $this->permission_manager->shouldReceive('isPermissionExist')->with($this->release_id, 'RELEASE_READ')->once()->andReturns(true);
        $this->permission_manager->shouldReceive('userHasPermission')->with($this->release_id, 'RELEASE_READ', array(1,2,76))->once()->andReturns($can_read_release);
        $this->frs_release_factory->shouldReceive('getPermissionsManager')->andReturns($this->permission_manager);
    }

    public function testUserCanReadWithSpecificPermsHasAccess()
    {
        $this->_userCanReadWithSpecificPerms(true);
        $this->assertTrue($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    public function testUserCanReadWithSpecificPermsHasNoAccess()
    {
        $this->_userCanReadWithSpecificPerms(false);
        $this->assertFalse($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }


    public function testAdminCanAlwaysUpdateReleases()
    {
        $this->frs_release_factory->shouldReceive('userCanAdmin')->andReturns(true);
        $this->assertTrue($this->frs_release_factory->userCanUpdate($this->group_id, $this->release_id, $this->user_id));
    }

    public function testMereMortalCannotUpdateReleases()
    {
        $this->frs_release_factory->shouldReceive('userCanAdmin')->andReturns(false);
        $this->assertFalse($this->frs_release_factory->userCanUpdate($this->group_id, $this->release_id, $this->user_id));
    }

    public function testAdminCanAlwaysCreateReleases()
    {
        $this->frs_release_factory->shouldReceive('userCanAdmin')->andReturns(true);
        $this->assertTrue($this->frs_release_factory->userCanCreate($this->group_id, $this->user_id));
    }

    public function testMereMortalCannotCreateReleases()
    {
        $this->frs_release_factory->shouldReceive('userCanAdmin')->andReturns(false);
        $this->assertFalse($this->frs_release_factory->userCanCreate($this->group_id, $this->user_id));
    }

    public function testDeleteProjectReleasesFail()
    {
        $release1 = array('release_id' => 1);
        $release2 = array('release_id' => 2);
        $release3 = array('release_id' => 3);
        $this->frs_release_factory->shouldReceive('getFRSReleasesInfoListFromDb')->andReturns(array($release1, $release2, $release3));
        $this->frs_release_factory->shouldReceive('delete_release')->with(1, 1)->once()->andReturns(true);
        $this->frs_release_factory->shouldReceive('delete_release')->with(1, 2)->once()->andReturns(false);
        $this->frs_release_factory->shouldReceive('delete_release')->with(1, 3)->once()->andReturns(true);
        $this->assertFalse($this->frs_release_factory->deleteProjectReleases(1));
    }

    public function testDeleteProjectReleasesSuccess()
    {
        $release1 = array('release_id' => 1);
        $release2 = array('release_id' => 2);
        $release3 = array('release_id' => 3);
        $this->frs_release_factory->shouldReceive('getFRSReleasesInfoListFromDb')->andReturns(array($release1, $release2, $release3));
        $this->frs_release_factory->shouldReceive('delete_release')->with(1, 1)->once()->andReturns(true);
        $this->frs_release_factory->shouldReceive('delete_release')->with(1, 2)->once()->andReturns(true);
        $this->frs_release_factory->shouldReceive('delete_release')->with(1, 3)->once()->andReturns(true);
        $this->assertTrue($this->frs_release_factory->deleteProjectReleases(1));
    }
}
