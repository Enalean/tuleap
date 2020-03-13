<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

namespace Tuleap\WebDAV;

use FRSPackage;
use FRSRelease;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;

require_once __DIR__ . '/bootstrap.php';

/**
 * This is the unit test of WebDAVFRSPackage
 */
class WebDAVFRSPackageTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    /**
     * Testing when The package have no releases
     */
    public function testGetChildrenNoReleases(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('getReleaseList')->andReturns(array());

        $this->assertEquals($webDAVFRSPackage->getChildren(), array());
    }

    /**
     * Testing when the user can't read the release
     */
    public function testGetChildrenUserCanNotRead(): void
    {
        $release = \Mockery::spy(\WebDAVFRSRelease::class);
        $release->shouldReceive('userCanRead')->andReturns(false);

        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('getWebDAVRelease')->andReturns($release);
        $webDAVFRSPackage->shouldReceive('getUser')->andReturns(Mockery::mock(\PFUser::class));

        $FRSRelease = \Mockery::spy(FRSRelease::class);
        $webDAVFRSPackage->shouldReceive('getReleaseList')->andReturns(array($FRSRelease));

        $this->assertEquals($webDAVFRSPackage->getChildren(), array());
    }

    /**
     * Testing when the user can read the release
     */
    public function testGetChildrenUserCanRead(): void
    {
        $release = \Mockery::spy(\WebDAVFRSRelease::class);
        $release->shouldReceive('userCanRead')->andReturns(true);

        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('getWebDAVRelease')->andReturns($release);
        $webDAVFRSPackage->shouldReceive('getUser')->andReturns(Mockery::mock(\PFUser::class));

        $FRSRelease = \Mockery::spy(FRSRelease::class);
        $webDAVFRSPackage->shouldReceive('getReleaseList')->andReturns(array($FRSRelease));

        $this->assertEquals($webDAVFRSPackage->getChildren(), array($release));
    }

    /**
     * Testing when the release doesn't exist
     */
    public function testGetChildFailWithNotExist(): void
    {
        $FRSRelease = \Mockery::spy(FRSRelease::class);
        $WebDAVRelease = \Mockery::spy(\WebDAVFRSRelease::class);
        $WebDAVRelease->shouldReceive('exist')->andReturns(false);

        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('getFRSReleaseFromName')->andReturns($FRSRelease);
        $webDAVFRSPackage->shouldReceive('getWebDAVRelease')->andReturns($WebDAVRelease);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $webDAVFRSPackage->shouldReceive('getUtils')->andReturns($utils);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFRSPackage->getChild($WebDAVRelease->getReleaseId());
    }

    /**
     * Testing when the user can't read the release
     */
    public function testGetChildFailWithUserCanNotRead(): void
    {
        $FRSRelease = \Mockery::spy(FRSRelease::class);
        $WebDAVRelease = \Mockery::spy(\WebDAVFRSRelease::class);
        $WebDAVRelease->shouldReceive('exist')->andReturns(true);
        $WebDAVRelease->shouldReceive('userCanRead')->andReturns(false);

        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('getFRSReleaseFromName')->andReturns($FRSRelease);
        $webDAVFRSPackage->shouldReceive('getWebDAVRelease')->andReturns($WebDAVRelease);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $webDAVFRSPackage->shouldReceive('getUtils')->andReturns($utils);
        $webDAVFRSPackage->shouldReceive('getUser')->andReturns(Mockery::mock(\PFUser::class));

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSPackage->getChild($WebDAVRelease->getReleaseId());
    }

    /**
     * Testing when the release exist and user can read
     */
    public function testSucceedGetChild(): void
    {
        $FRSRelease = \Mockery::spy(FRSRelease::class);
        $WebDAVRelease = \Mockery::spy(\WebDAVFRSRelease::class);
        $WebDAVRelease->shouldReceive('exist')->andReturns(true);
        $WebDAVRelease->shouldReceive('userCanRead')->andReturns(true);

        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('getFRSReleaseFromName')->andReturns($FRSRelease);
        $webDAVFRSPackage->shouldReceive('getWebDAVRelease')->andReturns($WebDAVRelease);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $webDAVFRSPackage->shouldReceive('getUtils')->andReturns($utils);
        $webDAVFRSPackage->shouldReceive('getUser')->andReturns(Mockery::mock(\PFUser::class));

        $this->assertEquals($webDAVFRSPackage->getChild($WebDAVRelease->getReleaseId()), $WebDAVRelease);
    }

    /**
     * Testing when the package is deleted and the user have no permissions
     */
    public function testUserCanReadFailurePackageDeletedUserHaveNoPermissions(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = Mockery::mock(FRSPackage::class, ['isActive' => false, 'userCanRead' => false, 'isHidden' => false]);
        $webDAVFRSPackage->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when the release is active and user can not read
     */
    public function testUserCanReadFailureActiveUserCanNotRead(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = Mockery::mock(FRSPackage::class, ['isActive' => true, 'userCanRead' => false, 'isHidden' => false]);
        $webDAVFRSPackage->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when the release is not active and the user can read
     */
    public function testUserCanReadFailureDeletedUserCanRead(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = Mockery::mock(FRSPackage::class, ['isActive' => false, 'userCanRead' => true, 'isHidden' => false]);
        $webDAVFRSPackage->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when the release is active and the user can read
     */
    public function testUserCanReadSucceedActiveUserCanRead(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = Mockery::mock(FRSPackage::class, ['isActive' => true, 'userCanRead' => true, 'isHidden' => false]);
        $webDAVFRSPackage->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertEquals($webDAVFRSPackage->userCanRead($user), true);
    }

    /**
     * Testing when the release is hidden and the user is not admin and can not read
     */
    public function testUserCanReadFailureHiddenNotAdmin(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = Mockery::mock(FRSPackage::class, ['isActive' => false, 'userCanRead' => false, 'isHidden' => true]);
        $webDAVFRSPackage->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when the release is hidden and the user can read and is not admin
     */
    public function testUserCanReadFailureHiddenNotAdminUserCanRead(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = Mockery::mock(FRSPackage::class, ['isActive' => false, 'userCanRead' => true, 'isHidden' => true]);
        $webDAVFRSPackage->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when release is deleted and the user is admin
     */
    public function testUserCanReadFailureDeletedUserIsAdmin(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = Mockery::mock(FRSPackage::class, ['isActive' => false, 'userCanRead' => false, 'isHidden' => false]);
        $webDAVFRSPackage->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when the release is active but the admin can not read ????
     * TODO: verify this in a real case
     */
    public function testUserCanReadFailureAdminHaveNoPermission(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = Mockery::mock(FRSPackage::class, ['isActive' => true, 'userCanRead' => false, 'isHidden' => false]);
        $webDAVFRSPackage->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when release is deleted and user is admin and can read
     */
    public function testUserCanReadFailureDeletedCanReadIsAdmin(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = Mockery::mock(FRSPackage::class, ['isActive' => false, 'userCanRead' => true, 'isHidden' => false]);
        $webDAVFRSPackage->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when release is active and user can read and is admin
     */
    public function testUserCanReadSucceedActiveUserCanReadIsAdmin(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = Mockery::mock(FRSPackage::class, ['isActive' => true, 'userCanRead' => true, 'isHidden' => false]);
        $webDAVFRSPackage->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertEquals($webDAVFRSPackage->userCanRead($user), true);
    }

    /**
     * Testing when release is hidden and user is admin
     */
    public function testUserCanReadSucceedHiddenUserIsAdmin(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = Mockery::mock(FRSPackage::class, ['isActive' => false, 'userCanRead' => false, 'isHidden' => true]);
        $webDAVFRSPackage->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertEquals($webDAVFRSPackage->userCanRead($user), true);
    }

    /**
     * Testing when release is hidden and user is admin and can read
     */
    public function testUserCanReadSucceedHiddenUserIsAdminCanRead(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = Mockery::mock(FRSPackage::class, ['isActive' => false, 'userCanRead' => true, 'isHidden' => true]);
        $webDAVFRSPackage->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertEquals($webDAVFRSPackage->userCanRead($user), true);
    }

    /**
     * Testing delete when user is not admin
     */
    public function testDeleteFailWithUserNotAdmin(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('userCanWrite')->andReturns(false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSPackage->delete();
    }

    /**
     * Testing delete when the package is not empty
     */
    public function testDeleteFailWithPackageNotEmpty(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('userCanWrite')->andReturns(true);
        $release = \Mockery::spy(FRSRelease::class);
        $webDAVFRSPackage->shouldReceive('getReleaseList')->andReturns(array($release));
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSPackage->delete();
    }

    /**
     * Testing delete when package doesn't exist
     */
    public function testDeletePackageNotExist(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('userCanWrite')->andReturns(true);
        $webDAVFRSPackage->shouldReceive('getReleaseList')->andReturns(array());
        $webDAVFRSPackage->shouldReceive('getPackageId')->andReturns(1);
        $packageFactory = \Mockery::spy(\FRSPackageFactory::class);
        $packageFactory->shouldReceive('delete_package')->andReturns(0);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getPackageFactory')->andReturns($packageFactory);
        $webDAVFRSPackage->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSPackage->shouldReceive('getProject')->andReturns($project);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSPackage->delete();
    }

    /**
     * Testing succeeded delete
     */
    public function testDeleteSucceede(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('userCanWrite')->andReturns(true);
        $webDAVFRSPackage->shouldReceive('getReleaseList')->andReturns(array());
        $webDAVFRSPackage->shouldReceive('getPackageId')->andReturns(1);
        $packageFactory = \Mockery::spy(\FRSPackageFactory::class);
        $packageFactory->shouldReceive('delete_package')->andReturns(1);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getPackageFactory')->andReturns($packageFactory);
        $webDAVFRSPackage->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSPackage->shouldReceive('getProject')->andReturns($project);

        $webDAVFRSPackage->delete();
    }

    /**
     * Testing setName when user is not admin
     */
    public function testSetNameFailWithUserNotAdmin(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('userCanWrite')->andReturns(false);
        $packageFactory = \Mockery::spy(\FRSPackageFactory::class);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getPackageFactory')->andReturns($packageFactory);
        $webDAVFRSPackage->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSPackage->shouldReceive('getProject')->andReturns($project);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSPackage->setName('newName');
    }

    /**
     * Testing setName when name already exist
     */
    public function testSetNameFailWithNameExist(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('userCanWrite')->andReturns(true);
        $packageFactory = \Mockery::spy(\FRSPackageFactory::class);
        $packageFactory->shouldReceive('isPackageNameExist')->andReturns(true);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getPackageFactory')->andReturns($packageFactory);
        $webDAVFRSPackage->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSPackage->shouldReceive('getProject')->andReturns($project);
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $webDAVFRSPackage->setName('newName');
    }

    /**
     * Testing setName succeede
     */
    public function testSetNameSucceede(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('userCanWrite')->andReturns(true);
        $packageFactory = \Mockery::spy(\FRSPackageFactory::class);
        $packageFactory->shouldReceive('isPackageNameExist')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getPackageFactory')->andReturns($packageFactory);
        $webDAVFRSPackage->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSPackage->shouldReceive('getProject')->andReturns($project);
        $package = new FRSPackage();
        $webDAVFRSPackage->shouldReceive('getPackage')->andReturns($package);

        $webDAVFRSPackage->setName('newName');
    }

    /**
     * Testing creation of release when user is not admin
     */
    public function testCreateDirectoryFailWithUserNotAdmin(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSPackage->shouldReceive('userCanWrite')->andReturns(false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSPackage->createDirectory('release');
    }

    /**
     * Testing creation of release when the name already exist
     */
    public function testCreateDirectoryFailWithNameExist(): void
    {
        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSPackage->shouldReceive('userCanWrite')->andReturns(true);
        $webDAVFRSPackage->shouldReceive('getPackageId')->andReturns(1);
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(true);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $webDAVFRSPackage->shouldReceive('getUtils')->andReturns($utils);
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $webDAVFRSPackage->createDirectory('release');
    }

    /**
     * Testing creation of release succeed
     */
    public function testCreateDirectorysucceed(): void
    {
        // Values we expect for the package to create
        $refPackageToCreate = array('name'       => 'release',
                                    'package_id' => 42,
                                    'notes'      => '',
                                    'changes'    => '',
                                    'status_id'  => 1);
        // Values we expect for the package once created
        $refPackage = $refPackageToCreate;
        $refPackage['release_id'] = 15;

        $webDAVFRSPackage = \Mockery::mock(\WebDAVFRSPackage::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSPackage->shouldReceive('getPackageId')->andReturns(42);

        $webDAVFRSPackage->shouldReceive('userCanWrite')->andReturns(true);

        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(false);
        $frsrf->shouldReceive('create')->with($refPackageToCreate)->once()->andReturns(15);
        $frsrf->shouldReceive('setDefaultPermissions')->with(Mockery::type(FRSRelease::class))->once();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);

        $pm = \Mockery::spy(\PermissionsManager::class);
        $utils->shouldReceive('getPermissionsManager')->andReturns($pm);

        $webDAVFRSPackage->shouldReceive('getUtils')->andReturns($utils);

        $webDAVFRSPackage->createDirectory('release');
    }
}
