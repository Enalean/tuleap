<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use FRSFileFactory;
use FRSPackage;
use FRSRelease;
use PFUser;
use Project;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\RequestedRangeNotSatisfiable;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

/**
 * This is the unit test of WebDAVFRSRelease
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class WebDAVFRSReleaseTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var \WebDAVUtils&\PHPUnit\Framework\MockObject\MockObject
     */
    private $utils;

    protected function setUp(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', __DIR__ . '/_fixtures/incoming');

        $this->user    = UserTestBuilder::aUser()->build();
        $this->project = ProjectTestBuilder::aProject()->build();
        $this->utils   = $this->createMock(\WebDAVUtils::class);
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    /**
     * Testing when The release have no files
     */
    public function testGetChildrenNoFiles(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getFileList'],
        );
        $webDAVFRSRelease->method('getFileList')->willReturn([]);

        self::assertEquals([], $webDAVFRSRelease->getChildren());
    }

    /**
     * Testing when the release contains files
     */
    public function testGetChildrenContainFiles(): void
    {
        $file = $this->createMock(\WebDAVFRSFile::class);

        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getChild', 'getFileList', 'getWebDAVFRSFile'],
        );
        $webDAVFRSRelease->method('getChild')->willReturn($file);

        $FRSFile = $this->createMock(\FRSFile::class);
        $webDAVFRSRelease->method('getFileList')->willReturn([$FRSFile]);
        $webDAVFRSRelease->method('getWebDAVFRSFile')->willReturn($file);

        self::assertEquals([$file], $webDAVFRSRelease->getChildren());
    }

    /**
     * Testing when the file is null
     */
    public function testGetChildFailureWithFileNull(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getFileIdFromName', 'getFRSFileFromId'],
        );

        $webDAVFRSRelease->method('getFileIdFromName')->with('fileName')->willReturn(0);
        $webDAVFRSRelease->method('getFRSFileFromId')->willReturn(null);

        $this->expectException(NotFound::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file returns isActive == false
     */
    public function testGetChildFailureWithNotActive(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getFileIdFromName', 'getFRSFileFromId'],
        );

        $file = $this->createMock(\FRSFile::class);

        $file->method('isActive')->willReturn(false);
        $file->method('isDeleted')->willReturn(false);

        $webDAVFRSRelease->method('getFileIdFromName')->with('fileName')->willReturn(1);
        $webDAVFRSRelease->method('getFRSFileFromId')->willReturn($file);

        $this->expectException(Forbidden::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the user don't have the right to download
     */
    public function testGetChildFailureWithUserCanNotDownload(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getFileIdFromName', 'getFRSFileFromId', 'getUser'],
        );

        $file = $this->createMock(\FRSFile::class);
        $file->method('isActive')->willReturn(true);
        $file->method('isDeleted')->willReturn(false);
        $file->method('userCanDownload')->willReturn(false);

        $webDAVFRSRelease->method('getFileIdFromName')->with('fileName')->willReturn(1);
        $webDAVFRSRelease->method('getFRSFileFromId')->willReturn($file);

        $user = $this->createMock(PFUser::class);
        $webDAVFRSRelease->method('getUser')->willReturn($user);

        $this->expectException(Forbidden::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file doesn't exist
     */
    public function testGetChildFailureWithNotExist(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getFileIdFromName', 'getFRSFileFromId', 'getUser'],
        );

        $file = $this->createMock(\FRSFile::class);
        $file->method('isActive')->willReturn(true);
        $file->method('isDeleted')->willReturn(false);
        $file->method('userCanDownload')->willReturn(true);
        $file->method('fileExists')->willReturn(false);

        $webDAVFRSRelease->method('getFileIdFromName')->with('fileName')->willReturn(1);
        $webDAVFRSRelease->method('getFRSFileFromId')->willReturn($file);

        $user = $this->createMock(PFUser::class);
        $webDAVFRSRelease->method('getUser')->willReturn($user);

        $this->expectException(NotFound::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file don't belong to the given package
     */
    public function testGetChildFailureWithNotBelongToPackage(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getFileIdFromName', 'getFRSFileFromId', 'getUser', 'getPackage', 'getReleaseId'],
        );

        $file = $this->createMock(\FRSFile::class);
        $file->method('isActive')->willReturn(true);
        $file->method('isDeleted')->willReturn(false);
        $file->method('userCanDownload')->willReturn(true);
        $file->method('fileExists')->willReturn(true);
        $file->method('getPackageId')->willReturn(1);
        $file->method('getReleaseId')->willReturn(3);

        $package = $this->createMock(\WebDAVFRSPackage::class);
        $package->method('getPackageID')->willReturn(2);
        $webDAVFRSRelease->method('getPackage')->willReturn($package);
        $webDAVFRSRelease->method('getReleaseId')->willReturn(3);
        $webDAVFRSRelease->method('getFileIdFromName')->with('fileName')->willReturn(1);
        $webDAVFRSRelease->method('getFRSFileFromId')->willReturn($file);

        $user = $this->createMock(PFUser::class);
        $webDAVFRSRelease->method('getUser')->willReturn($user);

        $this->expectException(NotFound::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file don't belong to the given relaese
     */
    public function testGetChildFailureWithNotBelongToRelease(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getFileIdFromName', 'getFRSFileFromId', 'getUser', 'getPackage', 'getReleaseId'],
        );

        $file = $this->createMock(\FRSFile::class);
        $file->method('isDeleted')->willReturn(false);
        $file->method('isActive')->willReturn(true);
        $file->method('userCanDownload')->willReturn(true);
        $file->method('fileExists')->willReturn(true);
        $file->method('getPackageId')->willReturn(1);
        $file->method('getReleaseId')->willReturn(2);

        $package = $this->createMock(\WebDAVFRSPackage::class);
        $package->method('getPackageID')->willReturn(1);
        $webDAVFRSRelease->method('getPackage')->willReturn($package);
        $webDAVFRSRelease->method('getReleaseId')->willReturn(3);
        $webDAVFRSRelease->method('getFileIdFromName')->with('fileName')->willReturn(1);
        $webDAVFRSRelease->method('getFRSFileFromId')->willReturn($file);

        $user = $this->createMock(PFUser::class);
        $webDAVFRSRelease->method('getUser')->willReturn($user);

        $this->expectException(NotFound::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file size exceed max file size
     */
    public function testGetChildFailureWithBigFile(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getFileIdFromName', 'getFRSFileFromId', 'getUser', 'getPackage', 'getReleaseId', 'getMaxFileSize'],
        );

        $file = $this->createMock(\FRSFile::class);
        $file->method('isActive')->willReturn(true);
        $file->method('isDeleted')->willReturn(false);
        $file->method('userCanDownload')->willReturn(true);
        $file->method('fileExists')->willReturn(true);
        $file->method('getPackageId')->willReturn(1);
        $file->method('getReleaseId')->willReturn(2);
        $file->method('getFileSize')->willReturn(65);

        $package = $this->createMock(\WebDAVFRSPackage::class);
        $package->method('getPackageID')->willReturn(1);
        $webDAVFRSRelease->method('getPackage')->willReturn($package);
        $webDAVFRSRelease->method('getReleaseId')->willReturn(2);

        $webDAVFRSRelease->method('getMaxFileSize')->willReturn(64);
        $webDAVFRSRelease->method('getFileIdFromName')->with('fileName')->willReturn(1);
        $webDAVFRSRelease->method('getFRSFileFromId')->willReturn($file);

        $user = $this->createMock(PFUser::class);
        $webDAVFRSRelease->method('getUser')->willReturn($user);

        $this->expectException(RequestedRangeNotSatisfiable::class);

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when GetChild succeed
     */
    public function testGetChildSucceed(): void
    {
        $webDAVFRSRelease = $this->getMockBuilder(\WebDAVFRSRelease::class)
            ->setConstructorArgs([
                $this->user,
                $this->project,
                null,
                null,
                1000,
            ])
            ->onlyMethods(['getFileIdFromName', 'getFRSFileFromId', 'getPackage', 'getReleaseId', 'getUtils'])
            ->getMock();

        $file = $this->createMock(\FRSFile::class);
        $file->method('isDeleted')->willReturn(false);
        $file->method('isActive')->willReturn(true);
        $file->method('userCanDownload')->willReturn(true);
        $file->method('fileExists')->willReturn(true);
        $file->method('getPackageId')->willReturn(1);
        $file->method('getReleaseId')->willReturn(2);
        $file->method('getFileSize')->willReturn(64);

        $package = $this->createMock(\WebDAVFRSPackage::class);
        $package->method('getPackageID')->willReturn(1);
        $webDAVFRSRelease->method('getPackage')->willReturn($package);
        $webDAVFRSRelease->method('getReleaseId')->willReturn(2);

        $webDAVFRSRelease->method('getFileIdFromName')->with('fileName')->willReturn(1);
        $webDAVFRSRelease->method('getFRSFileFromId')->willReturn($file);

        $webDAVFRSRelease->method('getUtils')->willReturn($this->utils);

        $webDAVFile = new \WebDAVFRSFile($this->user, $this->project, $file, $this->utils);
        self::assertEquals($webDAVFile, $webDAVFRSRelease->getChild('fileName'));
    }

    /**
     * Testing when the release is deleted and the user have no permissions
     */
    public function testUserCanReadFailureReleaseDeletedUserHaveNoPermissions(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['userIsAdmin', 'getRelease'],
        );

        $release = $this->createMock(FRSRelease::class);
        $release->method('isDeleted')->willReturn(false);
        $release->method('isActive')->willReturn(false);
        $release->method('userCanRead')->willReturn(false);
        $release->method('isHidden')->willReturn(false);
        $webDAVFRSRelease->method('userIsAdmin')->willReturn(false);

        $webDAVFRSRelease->method('getRelease')->willReturn($release);
        $user = $this->createMock(\PFUser::class);

        self::assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is active and user can not read
     */
    public function testUserCanReadFailureActiveUserCanNotRead(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['userIsAdmin', 'getRelease'],
        );

        $release = $this->createMock(FRSRelease::class);
        $release->method('isActive')->willReturn(true);
        $release->method('userCanRead')->willReturn(false);
        $release->method('isHidden')->willReturn(false);
        $webDAVFRSRelease->method('userIsAdmin')->willReturn(false);

        $webDAVFRSRelease->method('getRelease')->willReturn($release);
        $user = UserTestBuilder::aUser()->build();

        self::assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is not active and the user can read
     */
    public function testUserCanReadFailureDeletedUserCanRead(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['userIsAdmin', 'getRelease'],
        );

        $release = $this->createMock(FRSRelease::class);
        $release->method('isActive')->willReturn(false);
        $release->method('userCanRead')->willReturn(true);
        $release->method('isHidden')->willReturn(false);
        $webDAVFRSRelease->method('userIsAdmin')->willReturn(false);

        $webDAVFRSRelease->method('getRelease')->willReturn($release);
        $user = UserTestBuilder::aUser()->build();

        self::assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is active and the user can read
     */
    public function testUserCanReadSucceedActiveUserCanRead(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['userIsAdmin', 'getRelease'],
        );

        $release = $this->createMock(FRSRelease::class);
        $release->method('isActive')->willReturn(true);
        $release->method('userCanRead')->willReturn(true);
        $release->method('isHidden')->willReturn(false);
        $webDAVFRSRelease->method('userIsAdmin')->willReturn(false);

        $webDAVFRSRelease->method('getRelease')->willReturn($release);
        $user = UserTestBuilder::aUser()->build();

        self::assertTrue($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is hidden and the user is not admin an can not read
     */
    public function testUserCanReadFailureHiddenNotAdmin(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getRelease', 'getProject', 'getUtils'],
        );

        $release = $this->createMock(FRSRelease::class);
        $release->method('isActive')->willReturn(false);
        $release->method('userCanRead')->willReturn(false);
        $release->method('isHidden')->willReturn(true);

        $webDAVFRSRelease->method('getRelease')->willReturn($release);
        $user = UserTestBuilder::aUser()->build();

        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $webDAVFRSRelease->method('getProject')->willReturn($project);

        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('userIsAdmin')->willReturn(false);
        $webDAVFRSRelease->method('getUtils')->willReturn($utils);

        self::assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is hidden and the user can read and is not admin
     */
    public function testUserCanReadFailureHiddenNotAdminUserCanRead(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getRelease', 'userIsAdmin'],
        );

        $release = $this->createMock(FRSRelease::class);
        $release->method('isActive')->willReturn(false);
        $release->method('userCanRead')->willReturn(true);
        $release->method('isHidden')->willReturn(true);
        $webDAVFRSRelease->method('userIsAdmin')->willReturn(false);

        $webDAVFRSRelease->method('getRelease')->willReturn($release);
        $user = UserTestBuilder::aUser()->build();

        self::assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is deleted and the user is admin
     */
    public function testUserCanReadFailureDeletedUserIsAdmin(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getRelease', 'userIsAdmin'],
        );

        $release = $this->createMock(FRSRelease::class);
        $release->method('isActive')->willReturn(false);
        $release->method('userCanRead')->willReturn(false);
        $release->method('isHidden')->willReturn(false);
        $webDAVFRSRelease->method('userIsAdmin')->willReturn(true);

        $webDAVFRSRelease->method('getRelease')->willReturn($release);
        $user = UserTestBuilder::aUser()->build();

        self::assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is active but the admin can not read ????
     * TODO: verify this in a real case
     */
    public function testUserCanReadFailureAdminHaveNoPermission(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getRelease', 'userIsAdmin'],
        );

        $release = $this->createMock(FRSRelease::class);
        $release->method('isActive')->willReturn(true);
        $release->method('userCanRead')->willReturn(false);
        $release->method('isHidden')->willReturn(false);
        $webDAVFRSRelease->method('userIsAdmin')->willReturn(true);

        $webDAVFRSRelease->method('getRelease')->willReturn($release);
        $user = UserTestBuilder::aUser()->build();

        self::assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is deleted and user is admin and can read
     */
    public function testUserCanReadFailureDeletedCanReadIsAdmin(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getRelease', 'userIsAdmin'],
        );

        $release = $this->createMock(FRSRelease::class);
        $release->method('isActive')->willReturn(false);
        $release->method('userCanRead')->willReturn(true);
        $release->method('isHidden')->willReturn(false);
        $webDAVFRSRelease->method('userIsAdmin')->willReturn(true);

        $webDAVFRSRelease->method('getRelease')->willReturn($release);
        $user = UserTestBuilder::aUser()->build();

        self::assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is active and user can read and is admin
     */
    public function testUserCanReadSucceedActiveUserCanReadIsAdmin(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getRelease', 'userIsAdmin'],
        );

        $release = $this->createMock(FRSRelease::class);
        $release->method('isActive')->willReturn(true);
        $release->method('userCanRead')->willReturn(true);
        $release->method('isHidden')->willReturn(false);
        $webDAVFRSRelease->method('userIsAdmin')->willReturn(true);

        $webDAVFRSRelease->method('getRelease')->willReturn($release);
        $user = UserTestBuilder::aUser()->build();

        self::assertTrue($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is hidden and user is admin
     */
    public function testUserCanReadSucceedHiddenUserIsAdmin(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getRelease', 'userIsAdmin'],
        );

        $release = $this->createMock(FRSRelease::class);
        $release->method('isActive')->willReturn(false);
        $release->method('userCanRead')->willReturn(false);
        $release->method('isHidden')->willReturn(true);
        $webDAVFRSRelease->method('userIsAdmin')->willReturn(true);

        $webDAVFRSRelease->method('getRelease')->willReturn($release);
        $user = UserTestBuilder::aUser()->build();

        self::assertTrue($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is hidden and user is admin and can read
     */
    public function testUserCanReadSucceedHiddenUserIsAdminCanRead(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getRelease', 'userIsAdmin'],
        );

        $release = $this->createMock(FRSRelease::class);
        $release->method('isActive')->willReturn(false);
        $release->method('userCanRead')->willReturn(true);
        $release->method('isHidden')->willReturn(true);
        $webDAVFRSRelease->method('userIsAdmin')->willReturn(true);

        $webDAVFRSRelease->method('getRelease')->willReturn($release);
        $user = UserTestBuilder::aUser()->build();

        self::assertTrue($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing delete when user is not admin
     */
    public function testDeleteFailWithUserNotAdmin(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['userCanWrite'],
        );
        $webDAVFRSRelease->method('userCanWrite')->willReturn(false);
        $this->expectException(Forbidden::class);

        $webDAVFRSRelease->delete();
    }

    /**
     * Testing delete when release doesn't exist
     */
    public function testDeleteReleaseNotExist(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getProject', 'userCanWrite', 'getUtils', 'getReleaseId'],
        );

        $webDAVFRSRelease->method('userCanWrite')->willReturn(true);
        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('delete_release')->willReturn(0);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $project = ProjectTestBuilder::aProject()->build();
        $webDAVFRSRelease->method('getProject')->willReturn($project);
        $webDAVFRSRelease->method('getUtils')->willReturn($utils);
        $webDAVFRSRelease->method('getReleaseId')->willReturn(0);

        $this->expectException(Forbidden::class);

        $webDAVFRSRelease->delete();
    }

    public function testDeleteSucceeds(): void
    {
        $this->expectNotToPerformAssertions();

        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getProject', 'userCanWrite', 'getUtils', 'getReleaseId'],
        );

        $webDAVFRSRelease->method('userCanWrite')->willReturn(true);
        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('delete_release')->willReturn(1);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $project = ProjectTestBuilder::aProject()->build();
        $webDAVFRSRelease->method('getProject')->willReturn($project);
        $webDAVFRSRelease->method('getUtils')->willReturn($utils);
        $webDAVFRSRelease->method('getReleaseId')->willReturn(1);

        $webDAVFRSRelease->delete();
    }

    /**
     * Testing setName when user is not admin
     */
    public function testSetNameFailWithUserNotAdmin(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getPackage', 'userCanWrite', 'getUtils', 'getProject'],
        );

        $webDAVFRSRelease->method('userCanWrite')->willReturn(false);
        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $package = new FRSPackage();
        $webDAVFRSRelease->method('getPackage')->willReturn($package);
        $webDAVFRSRelease->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $webDAVFRSRelease->method('getProject')->willReturn($project);
        $this->expectException(Forbidden::class);

        $webDAVFRSRelease->setName('newName');
    }

    /**
     * Testing setName when name already exist
     */
    public function testSetNameFailWithNameExist(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getPackage', 'userCanWrite', 'getUtils', 'getProject'],
        );

        $webDAVFRSRelease->method('userCanWrite')->willReturn(true);
        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('isReleaseNameExist')->willReturn(true);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $package = new FRSPackage();
        $webDAVFRSRelease->method('getPackage')->willReturn($package);
        $webDAVFRSRelease->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $webDAVFRSRelease->method('getProject')->willReturn($project);
        $this->expectException(MethodNotAllowed::class);

        $webDAVFRSRelease->setName('newName');
    }

    public function testSetNameSucceeds(): void
    {
        $this->expectNotToPerformAssertions();

        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getPackage', 'userCanWrite', 'getUtils', 'getProject', 'getRelease'],
        );

        $webDAVFRSRelease->method('userCanWrite')->willReturn(true);
        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('isReleaseNameExist')->willReturn(false);
        $frsrf->method('update');
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $package = new FRSPackage();
        $webDAVFRSRelease->method('getPackage')->willReturn($package);
        $webDAVFRSRelease->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $webDAVFRSRelease->method('getProject')->willReturn($project);
        $release = $this->createMock(FRSRelease::class);
        $release->method('setName');
        $release->method('toArray')->willReturn([]);
        $webDAVFRSRelease->method('getRelease')->willReturn($release);

        $webDAVFRSRelease->setName('newName');
    }

    public function testMoveFailNotAdminOnSource(): void
    {
        $source = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getUtils', 'getProject', 'getRelease'],
        );

        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('userCanUpdate')->willReturn(false);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $source->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $source->method('getProject')->willReturn($project);
        $release = $this->createMock(FRSRelease::class);
        $release->method('getReleaseID')->willReturn(1);
        $source->method('getRelease')->willReturn($release);
        $destination = $this->createMock(\WebDAVFRSPackage::class);
        $destination->method('userCanWrite')->willReturn(true);
        $package = new FRSPackage();
        $destination->method('getPackage')->willReturn($package);

        $this->expectException(Forbidden::class);

        $source->move($destination);
    }

    public function testMoveFailNotAdminOnDestination(): void
    {
        $source = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getUtils', 'getProject', 'getRelease'],
        );

        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('userCanUpdate')->willReturn(true);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $source->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $source->method('getProject')->willReturn($project);
        $release = $this->createMock(FRSRelease::class);
        $release->method('getReleaseID')->willReturn(1);
        $source->method('getRelease')->willReturn($release);
        $destination = $this->createMock(\WebDAVFRSPackage::class);
        $destination->method('userCanWrite')->willReturn(false);
        $package = new FRSPackage();
        $destination->method('getPackage')->willReturn($package);

        $this->expectException(Forbidden::class);

        $source->move($destination);
    }

    public function testMoveFailNotAdminOnBoth(): void
    {
        $source = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getUtils', 'getProject', 'getRelease'],
        );

        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('userCanUpdate')->willReturn(false);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $source->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $source->method('getProject')->willReturn($project);
        $release = $this->createMock(FRSRelease::class);
        $release->method('getReleaseID')->willReturn(1);
        $source->method('getRelease')->willReturn($release);
        $destination = $this->createMock(\WebDAVFRSPackage::class);
        $destination->method('userCanWrite')->willReturn(false);
        $package = new FRSPackage();
        $destination->method('getPackage')->willReturn($package);

        $this->expectException(Forbidden::class);

        $source->move($destination);
    }

    public function testMoveFailNameExist(): void
    {
        $source = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getUtils', 'getProject', 'getRelease'],
        );

        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('userCanUpdate')->willReturn(true);
        $frsrf->method('isReleaseNameExist')->willReturn(true);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $utils->method('unconvertHTMLSpecialChars')->willReturn('');
        $source->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $source->method('getProject')->willReturn($project);
        $release = $this->createMock(FRSRelease::class);
        $release->method('getReleaseID')->willReturn(1);
        $release->method('getName')->willReturn('release01');
        $source->method('getRelease')->willReturn($release);
        $destination = $this->createMock(\WebDAVFRSPackage::class);
        $destination->method('userCanWrite')->willReturn(true);
        $destination->method('getPackageId')->willReturn(1);
        $package = new FRSPackage();
        $destination->method('getPackage')->willReturn($package);

        $this->expectException(MethodNotAllowed::class);

        $source->move($destination);
    }

    public function testMoveFailPackageHiddenReleaseNotHidden(): void
    {
        $source = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getUtils', 'getProject', 'getRelease'],
        );

        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('userCanUpdate')->willReturn(true);
        $frsrf->method('isReleaseNameExist')->willReturn(false);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $utils->method('unconvertHTMLSpecialChars')->willReturn('');
        $source->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $source->method('getProject')->willReturn($project);
        $release = $this->createMock(FRSRelease::class);
        $release->method('isHidden')->willReturn(false);
        $release->method('getReleaseID')->willReturn(1);
        $release->method('getName')->willReturn('release01');
        $source->method('getRelease')->willReturn($release);
        $destination = $this->createMock(\WebDAVFRSPackage::class);
        $destination->method('userCanWrite')->willReturn(true);
        $destination->method('getPackageId')->willReturn(1);
        $package = new FRSPackage(['status_id' => FRSPackage::STATUS_HIDDEN]);
        $destination->method('getPackage')->willReturn($package);

        $this->expectException(MethodNotAllowed::class);

        $source->move($destination);
    }

    public function testMoveSucceedPackageAndReleaseHidden(): void
    {
        $this->expectNotToPerformAssertions();

        $source = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getUtils', 'getProject', 'getRelease'],
        );

        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('userCanUpdate')->willReturn(true);
        $frsrf->method('isReleaseNameExist')->willReturn(false);
        $frsrf->method('update');
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $utils->method('unconvertHTMLSpecialChars')->willReturn('');
        $source->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $source->method('getProject')->willReturn($project);
        $release = $this->createMock(FRSRelease::class);
        $release->method('isHidden')->willReturn(true);
        $release->method('getReleaseID')->willReturn(1);
        $release->method('getName')->willReturn('release01');
        $release->method('setPackageID');
        $release->method('toArray')->willReturn([]);
        $source->method('getRelease')->willReturn($release);
        $destination = $this->createMock(\WebDAVFRSPackage::class);
        $destination->method('userCanWrite')->willReturn(true);
        $destination->method('getPackageId')->willReturn(1);
        $package = new FRSPackage(['status_id' => FRSPackage::STATUS_HIDDEN]);
        $destination->method('getPackage')->willReturn($package);

        $source->move($destination);
    }

    public function testMoveSucceedReleaseHidden(): void
    {
        $this->expectNotToPerformAssertions();

        $source = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getUtils', 'getProject', 'getRelease'],
        );

        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('userCanUpdate')->willReturn(true);
        $frsrf->method('isReleaseNameExist')->willReturn(false);
        $frsrf->method('update');
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $utils->method('unconvertHTMLSpecialChars')->willReturn('');
        $source->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $source->method('getProject')->willReturn($project);
        $release = $this->createMock(FRSRelease::class);
        $release->method('isHidden')->willReturn(true);
        $release->method('getReleaseID')->willReturn(1);
        $release->method('getName')->willReturn('release01');
        $release->method('setPackageID');
        $release->method('toArray')->willReturn([]);
        $source->method('getRelease')->willReturn($release);
        $destination = $this->createMock(\WebDAVFRSPackage::class);
        $destination->method('userCanWrite')->willReturn(true);
        $destination->method('getPackageId')->willReturn(1);
        $package = new FRSPackage(['status_id' => FRSPackage::STATUS_ACTIVE]);
        $destination->method('getPackage')->willReturn($package);

        $source->move($destination);
    }

    public function testMoveSucceed(): void
    {
        $this->expectNotToPerformAssertions();

        $source = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['getUtils', 'getProject', 'getRelease'],
        );

        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->method('userCanUpdate')->willReturn(true);
        $frsrf->method('isReleaseNameExist')->willReturn(false);
        $frsrf->method('update');
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getReleaseFactory')->willReturn($frsrf);
        $utils->method('unconvertHTMLSpecialChars')->willReturn('');
        $source->method('getUtils')->willReturn($utils);
        $project = ProjectTestBuilder::aProject()->build();
        $source->method('getProject')->willReturn($project);
        $release = $this->createMock(FRSRelease::class);
        $release->method('isHidden')->willReturn(false);
        $release->method('getReleaseID')->willReturn(1);
        $release->method('getName')->willReturn('release01');
        $release->method('setPackageID');
        $release->method('toArray')->willReturn([]);
        $source->method('getRelease')->willReturn($release);
        $destination = $this->createMock(\WebDAVFRSPackage::class);
        $destination->method('userCanWrite')->willReturn(true);
        $destination->method('getPackageId')->willReturn(1);
        $package = new FRSPackage(['status_id' => FRSPackage::STATUS_ACTIVE]);
        $destination->method('getPackage')->willReturn($package);

        $source->move($destination);
    }

    /**
     * Testing creation of file when user is not admin
     */
    public function testCreateFileFailWithUserNotAdmin(): void
    {
        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['userCanWrite'],
        );

        $webDAVFRSRelease->method('userCanWrite')->willReturn(false);
        $this->expectException(Forbidden::class);

        $webDAVFRSRelease->createFile('release');
    }

    /**
     * Testing creation of file when the file size is bigger than permitted
     */
    public function testCreateFileFailWithFileSizeLimitExceeded(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', \org\bovigo\vfs\vfsStream::setup()->url());

        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['userCanWrite', 'getRelease', 'getProject', 'getUser', 'getUtils', 'getMaxFileSize'],
        );

        $webDAVFRSRelease->method('userCanWrite')->willReturn(true);
        $frsff = $this->createMock(FRSFileFactory::class);
        $frsff->method('isFileBaseNameExists')->willReturn(false);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getFileFactory')->willReturn($frsff);
        $utils->method('getIncomingFileSize')->willReturn(65);
        $project = $this->createMock(\Project::class);
        $webDAVFRSRelease->method('getProject')->willReturn($project);
        $webDAVFRSRelease->method('getUtils')->willReturn($utils);
        $this->expectException(RequestedRangeNotSatisfiable::class);
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVFRSRelease->method('getMaxFileSize')->willReturn(64);

        $webDAVFRSRelease->createFile('release1', $data);
    }

    /**
     * Testing creation of file succeed
     */
    public function testCreateFilesucceed(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', \org\bovigo\vfs\vfsStream::setup()->url());

        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['userCanWrite', 'getRelease', 'getProject', 'getUser', 'getUtils', 'getMaxFileSize'],
        );

        $webDAVFRSRelease->method('userCanWrite')->willReturn(true);
        $frsff = $this->createMock(FRSFileFactory::class);
        $frsff->method('isFileBaseNameExists')->willReturn(false);
        $frsff->method('createFile')->willReturn(true);

        $release = $this->createMock(FRSRelease::class);
        $release->method('getReleaseID')->willReturn(1234);
        $webDAVFRSRelease->method('getRelease')->willReturn($release);

        $frsrf = $this->createMock(\FRSReleaseFactory::class);
        $frsrf->expects($this->once())->method('emailNotification');

        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('getFileFactory')->willReturn($frsff);
        $utils->method('getIncomingFileSize')->willReturn(64);
        $utils->method('getReleaseFactory')->willReturn($frsrf);

        $project = ProjectTestBuilder::aProject()->build();
        $webDAVFRSRelease->method('getProject')->willReturn($project);
        $user = UserTestBuilder::aUser()->build();
        $webDAVFRSRelease->method('getUser')->willReturn($user);
        $webDAVFRSRelease->method('getUtils')->willReturn($utils);

        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVFRSRelease->method('getMaxFileSize')->willReturn(64);

        $webDAVFRSRelease->createFile('release', $data);
    }

    public function testCreateFileIntoIncomingUnlinkFail(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', __DIR__ . '/_fixtures');

        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['unlinkFile', 'openFile', 'streamCopyToStream', 'closeFile'],
        );

        $webDAVFRSRelease->expects($this->once())->method('unlinkFile')->willReturn(false);
        $webDAVFRSRelease->expects(self::never())->method('openFile');
        $webDAVFRSRelease->expects(self::never())->method('streamCopyToStream');
        $webDAVFRSRelease->expects(self::never())->method('closeFile');
        $this->expectException(Exception::class);

        $webDAVFRSRelease->createFileIntoIncoming('test.txt', 'text');
    }

    public function testCreateFileIntoIncomingCreateFail(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', __DIR__ . '/_fixtures');

        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['unlinkFile', 'openFile', 'streamCopyToStream', 'closeFile'],
        );

        $webDAVFRSRelease->expects(self::never())->method('unlinkFile')->willReturn(true);
        $webDAVFRSRelease->expects($this->once())->method('openFile')->willReturn(false);
        $webDAVFRSRelease->expects(self::never())->method('streamCopyToStream');
        $webDAVFRSRelease->expects(self::never())->method('closeFile');
        $this->expectException(Exception::class);

        $webDAVFRSRelease->createFileIntoIncoming('toto.txt', 'text');
    }

    public function testCreateFileIntoIncomingCloseFail(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', __DIR__ . '/_fixtures');

        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['unlinkFile', 'openFile', 'streamCopyToStream', 'closeFile'],
        );

        $webDAVFRSRelease->expects(self::never())->method('unlinkFile')->willReturn(true);
        $webDAVFRSRelease->expects($this->once())->method('openFile')->willReturn(true);
        $webDAVFRSRelease->expects($this->once())->method('streamCopyToStream');
        $webDAVFRSRelease->expects($this->once())->method('closeFile')->willReturn(false);
        $this->expectException(Exception::class);
        $this->expectException(Exception::class);

        $webDAVFRSRelease->createFileIntoIncoming('toto.txt', 'text');
    }

    public function testCreateFileIntoIncomingSucceed(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', __DIR__ . '/_fixtures');

        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['unlinkFile', 'openFile', 'streamCopyToStream', 'closeFile'],
        );

        $webDAVFRSRelease->expects(self::never())->method('unlinkFile');
        $webDAVFRSRelease->expects($this->once())->method('openFile')->willReturn(true);
        $webDAVFRSRelease->expects($this->once())->method('streamCopyToStream');
        $webDAVFRSRelease->expects($this->once())->method('closeFile')->willReturn(true);

        $webDAVFRSRelease->createFileIntoIncoming('toto.txt', 'text');
    }

    public function testCreateFileIntoIncomingSucceedWithFileExist(): void
    {
        \ForgeConfig::set('ftp_incoming_dir', __DIR__ . '/_fixtures');

        $webDAVFRSRelease = $this->createPartialMock(
            \WebDAVFRSRelease::class,
            ['unlinkFile', 'openFile', 'streamCopyToStream', 'closeFile'],
        );

        $webDAVFRSRelease->expects($this->once())->method('unlinkFile')->willReturn(true);
        $webDAVFRSRelease->expects($this->once())->method('openFile')->willReturn(true);
        $webDAVFRSRelease->expects($this->once())->method('streamCopyToStream');
        $webDAVFRSRelease->expects($this->once())->method('closeFile')->willReturn(true);

        $webDAVFRSRelease->createFileIntoIncoming('test.txt', 'text');
    }
}
