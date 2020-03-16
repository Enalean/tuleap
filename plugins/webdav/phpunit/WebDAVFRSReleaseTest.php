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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\GlobalLanguageMock;

require_once __DIR__ . '/bootstrap.php';

/**
 * This is the unit test of WebDAVFRSRelease
 */
class WebDAVFRSReleaseTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__) . '/_fixtures/incoming';
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['ftp_incoming_dir']);
        parent::tearDown();
    }

    /**
     * Testing when The release have no files
     */
    public function testGetChildrenNoFiles(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('getFileList')->andReturns(array());

        $this->assertEquals([], $webDAVFRSRelease->getChildren());
    }

    /**
     * Testing when the release contains files
     */
    public function testGetChildrenContainFiles(): void
    {
        $file = \Mockery::spy(\WebDAVFRSFile::class);

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('getChild')->andReturns($file);

        $FRSFile = \Mockery::spy(\FRSFile::class);
        $webDAVFRSRelease->shouldReceive('getFileList')->andReturns(array($FRSFile));
        $webDAVFRSRelease->shouldReceive('getWebDAVFRSFile')->andReturns($file);

        $this->assertEquals([$file], $webDAVFRSRelease->getChildren());
    }

    /**
     * Testing when the file is null
     */
    public function testGetChildFailureWithFileNull(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFile = \Mockery::spy(\WebDAVFRSFile::class);

        $webDAVFile->shouldReceive('getFile')->andReturns(null);
        $webDAVFRSRelease->shouldReceive('getWebDAVFRSFile')->andReturns($webDAVFile);
        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(0);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturnNull();

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file returns isActive == false
     */
    public function testGetChildFailureWithNotActive(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFile = \Mockery::spy(\WebDAVFRSFile::class);
        $file = \Mockery::spy(\FRSFile::class);

        $webDAVFile->shouldReceive('getFile')->andReturns($file);

        $webDAVFile->shouldReceive('isActive')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('getWebDAVFRSFile')->andReturns($webDAVFile);
        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the user don't have the right to download
     */
    public function testGetChildFailureWithUserCanNotDownload(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFile = \Mockery::spy(\WebDAVFRSFile::class);
        $file = \Mockery::spy(\FRSFile::class);

        $webDAVFile->shouldReceive('getFile')->andReturns($file);

        $webDAVFile->shouldReceive('isActive')->andReturns(true);

        $webDAVFile->shouldReceive('userCanDownload')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('getWebDAVFRSFile')->andReturns($webDAVFile);
        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $user = Mockery::mock(PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file doesn't exist
     */
    public function testGetChildFailureWithNotExist(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFile = \Mockery::spy(\WebDAVFRSFile::class);
        $file = \Mockery::spy(\FRSFile::class);

        $webDAVFile->shouldReceive('getFile')->andReturns($file);

        $webDAVFile->shouldReceive('isActive')->andReturns(true);

        $webDAVFile->shouldReceive('userCanDownload')->andReturns(true);

        $webDAVFile->shouldReceive('fileExists')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('getWebDAVFRSFile')->andReturns($webDAVFile);
        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $user = Mockery::mock(PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file don't belong to the given package
     */
    public function testGetChildFailureWithNotBelongToPackage(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFile = \Mockery::spy(\WebDAVFRSFile::class);
        $file = \Mockery::spy(\FRSFile::class);

        $webDAVFile->shouldReceive('getFile')->andReturns($file);

        $webDAVFile->shouldReceive('isActive')->andReturns(true);

        $webDAVFile->shouldReceive('userCanDownload')->andReturns(true);

        $webDAVFile->shouldReceive('fileExists')->andReturns(true);

        $webDAVFile->shouldReceive('getPackageId')->andReturns(1);
        $webDAVFile->shouldReceive('getReleaseId')->andReturns(3);
        $package = \Mockery::spy(\WebDAVFRSPackage::class);
        $package->shouldReceive('getPackageID')->andReturns(2);
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getReleaseId')->andReturns(3);
        $webDAVFRSRelease->shouldReceive('getWebDAVFRSFile')->andReturns($webDAVFile);
        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $user = Mockery::mock(PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file don't belong to the given relaese
     */
    public function testGetChildFailureWithNotBelongToRelease(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFile = \Mockery::spy(\WebDAVFRSFile::class);
        $file = \Mockery::spy(\FRSFile::class);
        $webDAVFile->shouldReceive('getFile')->andReturns($file);

        $webDAVFile->shouldReceive('isActive')->andReturns(true);

        $webDAVFile->shouldReceive('userCanDownload')->andReturns(true);

        $webDAVFile->shouldReceive('fileExists')->andReturns(true);

        $webDAVFile->shouldReceive('getPackageId')->andReturns(1);
        $webDAVFile->shouldReceive('getReleaseId')->andReturns(2);
        $package = \Mockery::spy(\WebDAVFRSPackage::class);
        $package->shouldReceive('getPackageID')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getReleaseId')->andReturns(3);
        $webDAVFRSRelease->shouldReceive('getWebDAVFRSFile')->andReturns($webDAVFile);
        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $user = Mockery::mock(PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when the file size exceed max file size
     */
    public function testGetChildFailureWithBigFile(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFile = \Mockery::spy(\WebDAVFRSFile::class);
        $file = \Mockery::spy(\FRSFile::class);
        $webDAVFile->shouldReceive('getFile')->andReturns($file);

        $webDAVFile->shouldReceive('isActive')->andReturns(true);

        $webDAVFile->shouldReceive('userCanDownload')->andReturns(true);

        $webDAVFile->shouldReceive('fileExists')->andReturns(true);

        $webDAVFile->shouldReceive('getPackageId')->andReturns(1);
        $webDAVFile->shouldReceive('getReleaseId')->andReturns(2);
        $package = \Mockery::spy(\WebDAVFRSPackage::class);
        $package->shouldReceive('getPackageID')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getReleaseId')->andReturns(2);

        $webDAVFile->shouldReceive('getSize')->andReturns(65);
        $webDAVFRSRelease->shouldReceive('getMaxFileSize')->andReturns(64);
        $webDAVFRSRelease->shouldReceive('getWebDAVFRSFile')->andReturns($webDAVFile);
        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $user = Mockery::mock(PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);

        $this->expectException('Sabre_DAV_Exception_RequestedRangeNotSatisfiable');

        $webDAVFRSRelease->getChild('fileName');
    }

    /**
     * Testing when GetChild succeede
     */
    public function testGetChildSucceede(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFile = \Mockery::spy(\WebDAVFRSFile::class);
        $file = \Mockery::spy(\FRSFile::class);
        $webDAVFile->shouldReceive('getFile')->andReturns($file);

        $webDAVFile->shouldReceive('isActive')->andReturns(true);

        $webDAVFile->shouldReceive('userCanDownload')->andReturns(true);

        $webDAVFile->shouldReceive('fileExists')->andReturns(true);

        $webDAVFile->shouldReceive('getPackageId')->andReturns(1);
        $webDAVFile->shouldReceive('getReleaseId')->andReturns(2);
        $package = \Mockery::spy(\WebDAVFRSPackage::class);
        $package->shouldReceive('getPackageID')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getReleaseId')->andReturns(2);

        $webDAVFile->shouldReceive('getSize')->andReturns(64);
        $webDAVFRSRelease->shouldReceive('getMaxFileSize')->andReturns(64);
        $webDAVFRSRelease->shouldReceive('getWebDAVFRSFile')->andReturns($webDAVFile);
        $webDAVFRSRelease->shouldReceive('getFileIdFromName')->with('fileName')->andReturns(1);
        $webDAVFRSRelease->shouldReceive('getFRSFileFromId')->andReturn($file);

        $user = Mockery::mock(PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);

        $this->assertEquals($webDAVFile, $webDAVFRSRelease->getChild('fileName'));
    }

    /**
     * Testing when the release is deleted and the user have no permissions
     */
    public function testUserCanReadFailureReleaseDeletedUserHaveNoPermissions(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(false);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is active and user can not read
     */
    public function testUserCanReadFailureActiveUserCanNotRead(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(true);
        $release->shouldReceive('userCanRead')->andReturns(false);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is not active and the user can read
     */
    public function testUserCanReadFailureDeletedUserCanRead(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(true);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is active and the user can read
     */
    public function testUserCanReadSucceedActiveUserCanRead(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(true);
        $release->shouldReceive('userCanRead')->andReturns(true);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertTrue($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is hidden and the user is not admin an can not read
     */
    public function testUserCanReadFailureHiddenNotAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(false);
        $release->shouldReceive('isHidden')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('userIsAdmin')->andReturnFalse();
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is hidden and the user can read and is not admin
     */
    public function testUserCanReadFailureHiddenNotAdminUserCanRead(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(true);
        $release->shouldReceive('isHidden')->andReturns(true);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(false);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is deleted and the user is admin
     */
    public function testUserCanReadFailureDeletedUserIsAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(false);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when the release is active but the admin can not read ????
     * TODO: verify this in a real case
     */
    public function testUserCanReadFailureAdminHaveNoPermission(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(true);
        $release->shouldReceive('userCanRead')->andReturns(false);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is deleted and user is admin and can read
     */
    public function testUserCanReadFailureDeletedCanReadIsAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(true);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertFalse($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is active and user can read and is admin
     */
    public function testUserCanReadSucceedActiveUserCanReadIsAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(true);
        $release->shouldReceive('userCanRead')->andReturns(true);
        $release->shouldReceive('isHidden')->andReturns(false);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertTrue($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is hidden and user is admin
     */
    public function testUserCanReadSucceedHiddenUserIsAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(false);
        $release->shouldReceive('isHidden')->andReturns(true);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertTrue($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing when release is hidden and user is admin and can read
     */
    public function testUserCanReadSucceedHiddenUserIsAdminCanRead(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isActive')->andReturns(false);
        $release->shouldReceive('userCanRead')->andReturns(true);
        $release->shouldReceive('isHidden')->andReturns(true);
        $webDAVFRSRelease->shouldReceive('userIsAdmin')->andReturns(true);

        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);
        $user = \Mockery::spy(\PFUser::class);

        $this->assertTrue($webDAVFRSRelease->userCanRead($user));
    }

    /**
     * Testing delete when user is not admin
     */
    public function testDeleteFailWithUserNotAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSRelease->delete();
    }

    /**
     * Testing delete when release doesn't exist
     */
    public function testDeleteReleaseNotExist(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(true);
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('delete_release')->andReturns(0);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);
        $webDAVFRSRelease->shouldReceive('getReleaseId')->andReturns(0);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSRelease->delete();
    }

    /**
     * Testing succeeded delete
     */
    public function testDeleteSucceede(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(true);
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('delete_release')->andReturns(1);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);
        $webDAVFRSRelease->shouldReceive('getReleaseId')->andReturns(1);

        $webDAVFRSRelease->delete();
    }

    /**
     * Testing setName when user is not admin
     */
    public function testSetNameFailWithUserNotAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(false);
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $package = new FRSPackage();
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSRelease->setName('newName');
    }

    /**
     * Testing setName when name already exist
     */
    public function testSetNameFailWithNameExist(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(true);
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(true);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $package = new FRSPackage();
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $webDAVFRSRelease->setName('newName');
    }

    /**
     * Testing setName succeede
     */
    public function testSetNameSucceede(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(true);
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $package = new FRSPackage();
        $webDAVFRSRelease->shouldReceive('getPackage')->andReturns($package);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);

        $webDAVFRSRelease->setName('newName');
    }

    public function testMoveFailNotAdminOnSource(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(true);
        $package = new FRSPackage();
        $destination->shouldReceive('getPackage')->andReturns($package);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $source->move($destination);
    }

    public function testMoveFailNotAdminOnDestination(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(true);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(false);
        $package = new FRSPackage();
        $destination->shouldReceive('getPackage')->andReturns($package);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $source->move($destination);
    }

    public function testMoveFailNotAdminOnBoth(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(false);
        $package = new FRSPackage();
        $destination->shouldReceive('getPackage')->andReturns($package);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $source->move($destination);
    }

    public function testMoveFailNameExist(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(true);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(true);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(true);
        $package = new FRSPackage();
        $destination->shouldReceive('getPackage')->andReturns($package);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $source->move($destination);
    }

    public function testMoveFailPackageHiddenReleaseNotHidden(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(true);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isHidden')->andReturns(false);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(true);
        $package = new FRSPackage(['status_id' => FRSPackage::STATUS_HIDDEN]);
        $destination->shouldReceive('getPackage')->andReturns($package);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $source->move($destination);
    }

    public function testMoveSucceedPackageAndReleaseHidden(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(true);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isHidden')->andReturns(true);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(true);
        $package = new FRSPackage(['status_id' => FRSPackage::STATUS_HIDDEN]);
        $destination->shouldReceive('getPackage')->andReturns($package);

        $source->move($destination);
    }

    public function testMoveSucceedReleaseHidden(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(true);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isHidden')->andReturns(true);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(true);
        $package = new FRSPackage(['status_id' => FRSPackage::STATUS_ACTIVE]);
        $destination->shouldReceive('getPackage')->andReturns($package);

        $source->move($destination);
    }

    public function testMoveSucceed(): void
    {
        $source = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('userCanUpdate')->andReturns(true);
        $frsrf->shouldReceive('isReleaseNameExist')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);
        $source->shouldReceive('getUtils')->andReturns($utils);
        $project = \Mockery::spy(\Project::class);
        $source->shouldReceive('getProject')->andReturns($project);
        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('isHidden')->andReturns(false);
        $source->shouldReceive('getRelease')->andReturns($release);
        $destination = \Mockery::spy(\WebDAVFRSPackage::class);
        $destination->shouldReceive('userCanWrite')->andReturns(true);
        $package = new FRSPackage(['status_id' => FRSPackage::STATUS_ACTIVE]);
        $destination->shouldReceive('getPackage')->andReturns($package);

        $source->move($destination);
    }

    /**
     * Testing creation of file when user is not admin
     */
    public function testCreateFileFailWithUserNotAdmin(): void
    {
        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSRelease->createFile('release');
    }

    /**
     * Testing creation of file when the file size is bigger than permitted
     */
    public function testCreateFileFailWithFileSizeLimitExceeded(): void
    {
        $GLOBALS['ftp_incoming_dir'] = \org\bovigo\vfs\vfsStream::setup()->url();

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(true);
        $frsff = \Mockery::mock(FRSFileFactory::class);
        $frsff->shouldReceive('isFileBaseNameExists')->andReturn(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getFileFactory')->andReturns($frsff);
        $utils->shouldReceive('getIncomingFileSize')->andReturns(65);
        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);
        $this->expectException('Sabre_DAV_Exception_RequestedRangeNotSatisfiable');
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVFRSRelease->shouldReceive('getMaxFileSize')->andReturns(64);

        $webDAVFRSRelease->createFile('release1', $data);
    }

    /**
     * Testing creation of file succeed
     */
    public function testCreateFilesucceed(): void
    {
        $GLOBALS['ftp_incoming_dir'] = \org\bovigo\vfs\vfsStream::setup()->url();

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('userCanWrite')->andReturns(true);
        $frsff = \Mockery::mock(FRSFileFactory::class);
        $frsff->shouldReceive('isFileBaseNameExists')->andReturn(false);
        $frsff->shouldReceive('createFile')->andReturn(true);

        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('getReleaseID')->andReturns(1234);
        $webDAVFRSRelease->shouldReceive('getRelease')->andReturns($release);

        $frsrf = \Mockery::spy(\FRSReleaseFactory::class);
        $frsrf->shouldReceive('emailNotification')->once();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getFileFactory')->andReturns($frsff);
        $utils->shouldReceive('getIncomingFileSize')->andReturns(64);
        $utils->shouldReceive('getReleaseFactory')->andReturns($frsrf);

        $project = \Mockery::spy(\Project::class);
        $webDAVFRSRelease->shouldReceive('getProject')->andReturns($project);
        $user = \Mockery::spy(\PFUser::class);
        $webDAVFRSRelease->shouldReceive('getUser')->andReturns($user);
        $webDAVFRSRelease->shouldReceive('getUtils')->andReturns($utils);

        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVFRSRelease->shouldReceive('getMaxFileSize')->andReturns(64);

        $webDAVFRSRelease->createFile('release', $data);
    }

    public function testcreateFileIntoIncomingUnlinkFail(): void
    {
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__) . '/_fixtures';

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('unlinkFile')->once()->andReturns(false);
        $webDAVFRSRelease->shouldReceive('openFile')->never();
        $webDAVFRSRelease->shouldReceive('streamCopyToStream')->never();
        $webDAVFRSRelease->shouldReceive('closeFile')->never();
        $this->expectException('Sabre_DAV_Exception');

        $webDAVFRSRelease->createFileIntoIncoming('test.txt', 'text');
    }

    public function testcreateFileIntoIncomingCreateFail(): void
    {
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__) . '/_fixtures';

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('unlinkFile')->never()->andReturns(true);
        $webDAVFRSRelease->shouldReceive('openFile')->once()->andReturns(false);
        $webDAVFRSRelease->shouldReceive('streamCopyToStream')->never();
        $webDAVFRSRelease->shouldReceive('closeFile')->never();
        $this->expectException('Sabre_DAV_Exception');

        $webDAVFRSRelease->createFileIntoIncoming('toto.txt', 'text');
    }

    public function testcreateFileIntoIncomingCloseFail(): void
    {
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__) . '/_fixtures';

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('unlinkFile')->never()->andReturns(true);
        $webDAVFRSRelease->shouldReceive('openFile')->once()->andReturns(true);
        $webDAVFRSRelease->shouldReceive('streamCopyToStream')->once();
        $webDAVFRSRelease->shouldReceive('closeFile')->once()->andReturns(false);
        $this->expectException('Sabre_DAV_Exception');
        $this->expectException('Sabre_DAV_Exception');

        $webDAVFRSRelease->createFileIntoIncoming('toto.txt', 'text');
    }

    public function testcreateFileIntoIncomingSucceed(): void
    {
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__) . '/_fixtures';

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('unlinkFile')->never();
        $webDAVFRSRelease->shouldReceive('openFile')->once()->andReturns(true);
        $webDAVFRSRelease->shouldReceive('streamCopyToStream')->once();
        $webDAVFRSRelease->shouldReceive('closeFile')->once()->andReturns(true);

        $webDAVFRSRelease->createFileIntoIncoming('toto.txt', 'text');
    }

    public function testcreateFileIntoIncomingSucceedWithFileExist(): void
    {
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__) . '/_fixtures';

        $webDAVFRSRelease = \Mockery::mock(\WebDAVFRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRSRelease->shouldReceive('unlinkFile')->once()->andReturns(true);
        $webDAVFRSRelease->shouldReceive('openFile')->once()->andReturns(true);
        $webDAVFRSRelease->shouldReceive('streamCopyToStream')->once();
        $webDAVFRSRelease->shouldReceive('closeFile')->once()->andReturns(true);

        $webDAVFRSRelease->createFileIntoIncoming('test.txt', 'text');
    }
}
