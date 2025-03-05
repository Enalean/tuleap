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
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

require_once __DIR__ . '/bootstrap.php';

/**
 * This is the unit test of WebDAVFRSPackage
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class WebDAVFRSPackageTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    /**
     * Testing when The package have no releases
     */
    public function testGetChildrenNoReleases(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getReleaseList'])
            ->getMock();

        $webDAVFRSPackage->method('getReleaseList')->willReturn([]);

        self::assertEquals($webDAVFRSPackage->getChildren(), []);
    }

    /**
     * Testing when the user can't read the release
     */
    public function testGetChildrenUserCanNotRead(): void
    {
        $release = $this->createMock(\WebDAVFRSRelease::class);
        $release->method('userCanRead')->willReturn(false);

        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getUser', 'getWebDAVRelease', 'getReleaseList'])
            ->getMock();

        $webDAVFRSPackage->method('getWebDAVRelease')->willReturn($release);
        $webDAVFRSPackage->method('getUser')->willReturn($this->createMock(\PFUser::class));

        $FRSRelease = $this->createMock(FRSRelease::class);
        $webDAVFRSPackage->method('getReleaseList')->willReturn([$FRSRelease]);

        self::assertEquals($webDAVFRSPackage->getChildren(), []);
    }

    /**
     * Testing when the user can read the release
     */
    public function testGetChildrenUserCanRead(): void
    {
        $release = $this->createMock(\WebDAVFRSRelease::class);
        $release->method('userCanRead')->willReturn(true);

        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getUser', 'getWebDAVRelease', 'getReleaseList'])
            ->getMock();

        $webDAVFRSPackage->method('getWebDAVRelease')->willReturn($release);
        $webDAVFRSPackage->method('getUser')->willReturn($this->createMock(\PFUser::class));

        $FRSRelease = $this->createMock(FRSRelease::class);
        $webDAVFRSPackage->method('getReleaseList')->willReturn([$FRSRelease]);

        self::assertEquals($webDAVFRSPackage->getChildren(), [$release]);
    }

    /**
     * Testing when the release doesn't exist
     */
    public function testGetChildFailWithNotExist(): void
    {
        $FRSRelease    = $this->createMock(FRSRelease::class);
        $WebDAVRelease = $this->createMock(\WebDAVFRSRelease::class);
        $WebDAVRelease->method('exist')->willReturn(false);
        $WebDAVRelease->method('getReleaseId')->willReturn(1);

        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getFRSReleaseFromName', 'getWebDAVRelease', 'getUtils'])
            ->getMock();

        $webDAVFRSPackage->method('getFRSReleaseFromName')->willReturn($FRSRelease);
        $webDAVFRSPackage->method('getWebDAVRelease')->willReturn($WebDAVRelease);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('retrieveName')->willReturn('name');
        $webDAVFRSPackage->method('getUtils')->willReturn($utils);

        $this->expectException(NotFound::class);

        $webDAVFRSPackage->getChild((string) $WebDAVRelease->getReleaseId());
    }

    /**
     * Testing when the user can't read the release
     */
    public function testGetChildFailWithUserCanNotRead(): void
    {
        $FRSRelease    = $this->createMock(FRSRelease::class);
        $WebDAVRelease = $this->createMock(\WebDAVFRSRelease::class);
        $WebDAVRelease->method('exist')->willReturn(true);
        $WebDAVRelease->method('userCanRead')->willReturn(false);
        $WebDAVRelease->method('getReleaseId')->willReturn(1);

        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getFRSReleaseFromName', 'getWebDAVRelease', 'getUtils', 'getUser'])
            ->getMock();

        $webDAVFRSPackage->method('getFRSReleaseFromName')->willReturn($FRSRelease);
        $webDAVFRSPackage->method('getWebDAVRelease')->willReturn($WebDAVRelease);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('retrieveName')->willReturn('name');
        $webDAVFRSPackage->method('getUtils')->willReturn($utils);
        $webDAVFRSPackage->method('getUser')->willReturn(UserTestBuilder::anActiveUser()->build());

        $this->expectException(Forbidden::class);

        $webDAVFRSPackage->getChild((string) $WebDAVRelease->getReleaseId());
    }

    /**
     * Testing when the release exist and user can read
     */
    public function testSucceedGetChild(): void
    {
        $FRSRelease    = $this->createMock(FRSRelease::class);
        $WebDAVRelease = $this->createMock(\WebDAVFRSRelease::class);
        $WebDAVRelease->method('exist')->willReturn(true);
        $WebDAVRelease->method('userCanRead')->willReturn(true);
        $WebDAVRelease->method('getReleaseId')->willReturn(1);

        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getFRSReleaseFromName', 'getWebDAVRelease', 'getUtils', 'getUser'])
            ->getMock();

        $webDAVFRSPackage->method('getFRSReleaseFromName')->willReturn($FRSRelease);
        $webDAVFRSPackage->method('getWebDAVRelease')->willReturn($WebDAVRelease);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('retrieveName')->willReturn('name');
        $webDAVFRSPackage->method('getUtils')->willReturn($utils);
        $webDAVFRSPackage->method('getUser')->willReturn($this->createMock(\PFUser::class));

        self::assertEquals($webDAVFRSPackage->getChild((string) $WebDAVRelease->getReleaseId()), $WebDAVRelease);
    }

    /**
     * Testing when the package is deleted and the user have no permissions
     */
    public function testUserCanReadFailurePackageDeletedUserHaveNoPermissions(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getPackage', 'userIsAdmin'])
            ->getMock();

        $package = $this->createMock(FRSPackage::class);
        $package->method('isActive')->willReturn(false);
        $package->method('userCanRead')->willReturn(false);
        $package->method('isHidden')->willReturn(false);

        $webDAVFRSPackage->method('userIsAdmin')->willReturn(false);
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when the release is active and user can not read
     */
    public function testUserCanReadFailureActiveUserCanNotRead(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getPackage', 'userIsAdmin'])
            ->getMock();

        $package = $this->createMock(FRSPackage::class);
        $package->method('isActive')->willReturn(true);
        $package->method('userCanRead')->willReturn(false);
        $package->method('isHidden')->willReturn(false);

        $webDAVFRSPackage->method('userIsAdmin')->willReturn(false);
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when the release is not active and the user can read
     */
    public function testUserCanReadFailureDeletedUserCanRead(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getPackage', 'userIsAdmin'])
            ->getMock();

        $package = $this->createMock(FRSPackage::class);
        $package->method('isActive')->willReturn(false);
        $package->method('userCanRead')->willReturn(true);
        $package->method('isHidden')->willReturn(false);

        $webDAVFRSPackage->method('userIsAdmin')->willReturn(false);
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when the release is active and the user can read
     */
    public function testUserCanReadSucceedActiveUserCanRead(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getPackage', 'userIsAdmin'])
            ->getMock();

        $package = $this->createMock(FRSPackage::class);
        $package->method('isActive')->willReturn(true);
        $package->method('userCanRead')->willReturn(true);
        $package->method('isHidden')->willReturn(false);

        $webDAVFRSPackage->method('userIsAdmin')->willReturn(false);
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($webDAVFRSPackage->userCanRead($user), true);
    }

    /**
     * Testing when the release is hidden and the user is not admin and can not read
     */
    public function testUserCanReadFailureHiddenNotAdmin(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getPackage', 'userIsAdmin'])
            ->getMock();

        $package = $this->createMock(FRSPackage::class);
        $package->method('isActive')->willReturn(false);
        $package->method('userCanRead')->willReturn(false);
        $package->method('isHidden')->willReturn(true);

        $webDAVFRSPackage->method('userIsAdmin')->willReturn(false);
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when the release is hidden and the user can read and is not admin
     */
    public function testUserCanReadFailureHiddenNotAdminUserCanRead(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getPackage', 'userIsAdmin'])
            ->getMock();

        $package = $this->createMock(FRSPackage::class);
        $package->method('isActive')->willReturn(false);
        $package->method('userCanRead')->willReturn(true);
        $package->method('isHidden')->willReturn(true);

        $webDAVFRSPackage->method('userIsAdmin')->willReturn(false);
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when release is deleted and the user is admin
     */
    public function testUserCanReadFailureDeletedUserIsAdmin(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getPackage', 'userIsAdmin'])
            ->getMock();

        $package = $this->createMock(FRSPackage::class);
        $package->method('isActive')->willReturn(false);
        $package->method('userCanRead')->willReturn(false);
        $package->method('isHidden')->willReturn(false);

        $webDAVFRSPackage->method('userIsAdmin')->willReturn(true);
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when the release is active but the admin can not read ????
     * TODO: verify this in a real case
     */
    public function testUserCanReadFailureAdminHaveNoPermission(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getPackage', 'userIsAdmin'])
            ->getMock();

        $package = $this->createMock(FRSPackage::class);
        $package->method('isActive')->willReturn(true);
        $package->method('userCanRead')->willReturn(false);
        $package->method('isHidden')->willReturn(false);

        $webDAVFRSPackage->method('userIsAdmin')->willReturn(true);
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when release is deleted and user is admin and can read
     */
    public function testUserCanReadFailureDeletedCanReadIsAdmin(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getPackage', 'userIsAdmin'])
            ->getMock();

        $package = $this->createMock(FRSPackage::class);
        $package->method('isActive')->willReturn(false);
        $package->method('userCanRead')->willReturn(true);
        $package->method('isHidden')->willReturn(false);

        $webDAVFRSPackage->method('userIsAdmin')->willReturn(true);
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($webDAVFRSPackage->userCanRead($user), false);
    }

    /**
     * Testing when release is active and user can read and is admin
     */
    public function testUserCanReadSucceedActiveUserCanReadIsAdmin(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getPackage', 'userIsAdmin'])
            ->getMock();

        $package = $this->createMock(FRSPackage::class);
        $package->method('isActive')->willReturn(true);
        $package->method('userCanRead')->willReturn(true);
        $package->method('isHidden')->willReturn(false);

        $webDAVFRSPackage->method('userIsAdmin')->willReturn(true);
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($webDAVFRSPackage->userCanRead($user), true);
    }

    /**
     * Testing when release is hidden and user is admin
     */
    public function testUserCanReadSucceedHiddenUserIsAdmin(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getPackage', 'userIsAdmin'])
            ->getMock();

        $package = $this->createMock(FRSPackage::class);
        $package->method('isActive')->willReturn(false);
        $package->method('userCanRead')->willReturn(false);
        $package->method('isHidden')->willReturn(true);

        $webDAVFRSPackage->method('userIsAdmin')->willReturn(true);
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($webDAVFRSPackage->userCanRead($user), true);
    }

    /**
     * Testing when release is hidden and user is admin and can read
     */
    public function testUserCanReadSucceedHiddenUserIsAdminCanRead(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['getPackage', 'userIsAdmin'])
            ->getMock();

        $package = $this->createMock(FRSPackage::class);
        $package->method('isActive')->willReturn(false);
        $package->method('userCanRead')->willReturn(true);
        $package->method('isHidden')->willReturn(true);

        $webDAVFRSPackage->method('userIsAdmin')->willReturn(true);
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $user = UserTestBuilder::anActiveUser()->build();

        self::assertEquals($webDAVFRSPackage->userCanRead($user), true);
    }

    /**
     * Testing delete when user is not admin
     */
    public function testDeleteFailWithUserNotAdmin(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['userCanWrite'])
            ->getMock();

        $webDAVFRSPackage->method('userCanWrite')->willReturn(false);

        $this->expectException(Forbidden::class);

        $webDAVFRSPackage->delete();
    }

    /**
     * Testing delete when the package is not empty
     */
    public function testDeleteFailWithPackageNotEmpty(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['userCanWrite', 'getReleaseList'])
            ->getMock();

        $webDAVFRSPackage->method('userCanWrite')->willReturn(true);

        $release = $this->createMock(FRSRelease::class);
        $webDAVFRSPackage->method('getReleaseList')->willReturn([$release]);

        $this->expectException(Forbidden::class);

        $webDAVFRSPackage->delete();
    }

    /**
     * Testing delete when package doesn't exist
     */
    public function testDeletePackageNotExist(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['userCanWrite', 'getReleaseList', 'getPackageId', 'getUtils', 'getProject'])
            ->getMock();

        $webDAVFRSPackage->method('userCanWrite')->willReturn(true);
        $webDAVFRSPackage->method('getReleaseList')->willReturn([]);
        $webDAVFRSPackage->method('getPackageId')->willReturn(1);
        $packageFactory = $this->createMock(\FRSPackageFactory::class);
        $packageFactory->method('delete_package')->willReturn(0);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getPackageFactory')->willReturn($packageFactory);
        $webDAVFRSPackage->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $webDAVFRSPackage->method('getProject')->willReturn($project);

        $this->expectException(Forbidden::class);

        $webDAVFRSPackage->delete();
    }

    public function testDeleteSucceeds(): void
    {
        $this->expectNotToPerformAssertions();

        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['userCanWrite', 'getReleaseList', 'getPackageId', 'getUtils', 'getProject'])
            ->getMock();

        $webDAVFRSPackage->method('userCanWrite')->willReturn(true);
        $webDAVFRSPackage->method('getReleaseList')->willReturn([]);
        $webDAVFRSPackage->method('getPackageId')->willReturn(1);
        $packageFactory = $this->createMock(\FRSPackageFactory::class);
        $packageFactory->method('delete_package')->willReturn(1);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getPackageFactory')->willReturn($packageFactory);
        $webDAVFRSPackage->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $webDAVFRSPackage->method('getProject')->willReturn($project);

        $webDAVFRSPackage->delete();
    }

    /**
     * Testing setName when user is not admin
     */
    public function testSetNameFailWithUserNotAdmin(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['userCanWrite', 'getReleaseList', 'getPackageId', 'getUtils', 'getProject'])
            ->getMock();

        $webDAVFRSPackage->method('userCanWrite')->willReturn(false);
        $packageFactory = $this->createMock(\FRSPackageFactory::class);
        $utils          = $this->createMock(\WebDAVUtils::class);
        $utils->method('getPackageFactory')->willReturn($packageFactory);
        $webDAVFRSPackage->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $webDAVFRSPackage->method('getProject')->willReturn($project);

        $this->expectException(Forbidden::class);

        $webDAVFRSPackage->setName('newName');
    }

    /**
     * Testing setName when name already exist
     */
    public function testSetNameFailWithNameExist(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['userCanWrite', 'getReleaseList', 'getPackageId', 'getUtils', 'getProject'])
            ->getMock();

        $webDAVFRSPackage->method('userCanWrite')->willReturn(true);
        $packageFactory = $this->createMock(\FRSPackageFactory::class);
        $packageFactory->method('isPackageNameExist')->willReturn(true);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getPackageFactory')->willReturn($packageFactory);
        $webDAVFRSPackage->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $webDAVFRSPackage->method('getProject')->willReturn($project);

        $this->expectException(MethodNotAllowed::class);

        $webDAVFRSPackage->setName('newName');
    }

    public function testSetNameSucceeds(): void
    {
        $this->expectNotToPerformAssertions();

        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['userCanWrite', 'getReleaseList', 'getPackageId', 'getUtils', 'getProject', 'getPackage'])
            ->getMock();

        $webDAVFRSPackage->method('userCanWrite')->willReturn(true);
        $packageFactory = $this->createMock(\FRSPackageFactory::class);
        $packageFactory->method('isPackageNameExist')->willReturn(false);
        $packageFactory->method('update');
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getPackageFactory')->willReturn($packageFactory);
        $webDAVFRSPackage->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $webDAVFRSPackage->method('getProject')->willReturn($project);
        $package = new FRSPackage();
        $webDAVFRSPackage->method('getPackage')->willReturn($package);

        $webDAVFRSPackage->setName('newName');
    }

    /**
     * Testing creation of release when user is not admin
     */
    public function testCreateDirectoryFailWithUserNotAdmin(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['userCanWrite'])
            ->getMock();

        $webDAVFRSPackage->method('userCanWrite')->willReturn(false);

        $this->expectException(Forbidden::class);

        $webDAVFRSPackage->createDirectory('release');
    }

    /**
     * Testing creation of release when the name already exist
     */
    public function testCreateDirectoryFailWithNameExist(): void
    {
        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['userCanWrite', 'getReleaseList', 'getPackageId', 'getUtils'])
            ->getMock();

        $webDAVFRSPackage->method('userCanWrite')->willReturn(true);
        $webDAVFRSPackage->method('getPackageId')->willReturn(1);
        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('isReleaseNameExist')->willReturn(true);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $webDAVFRSPackage->method('getUtils')->willReturn($utils);

        $this->expectException(MethodNotAllowed::class);

        $webDAVFRSPackage->createDirectory('release');
    }

    /**
     * Testing creation of release succeed
     */
    public function testCreateDirectorysucceed(): void
    {
        // Values we expect for the package to create
        $refPackageToCreate = ['name'       => 'release',
            'package_id' => 42,
            'notes'      => '',
            'changes'    => '',
            'status_id'  => 1,
        ];
        // Values we expect for the package once created
        $refPackage               = $refPackageToCreate;
        $refPackage['release_id'] = 15;

        $webDAVFRSPackage = $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                $this->createMock(FRSPackage::class),
                1,
            ])
            ->onlyMethods(['userCanWrite', 'getReleaseList', 'getPackageId', 'getUtils', 'getProject', 'getPackage'])
            ->getMock();

        $webDAVFRSPackage->method('getPackageId')->willReturn(42);
        $webDAVFRSPackage->method('userCanWrite')->willReturn(true);

        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('isReleaseNameExist')->willReturn(false);
        $frsrf->expects(self::once())->method('create')->with($refPackageToCreate)->willReturn(15);
        $frsrf->expects(self::once())->method('setDefaultPermissions')->with(self::isInstanceOf(FRSRelease::class));

        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);

        $pm = $this->createMock(\PermissionsManager::class);
        $utils->method('getPermissionsManager')->willReturn($pm);

        $webDAVFRSPackage->method('getUtils')->willReturn($utils);

        $webDAVFRSPackage->createDirectory('release');
    }
}
