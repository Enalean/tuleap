<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
use FRSPackageFactory;
use PermissionsManager;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use WebDAVFRS;
use WebDAVFRSPackage;
use WebDAVUtils;

/**
 * This is the unit test of WebDAVProject
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebDAVFRSTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    /**
     * Testing when The project have no packages
     */
    public function testGetChildrenNoPackages(): void
    {
        $webDAVFRS = $this->createPartialMock(WebDAVFRS::class, ['getPackageList']);
        $webDAVFRS->method('getPackageList')->willReturn([]);

        self::assertSame([], $webDAVFRS->getChildren());
    }

    /**
     * Testing when the user can't read packages
     */
    public function testGetChildrenUserCanNotRead(): void
    {
        $webDAVFRS = $this->createPartialMock(WebDAVFRS::class, ['getPackageList', 'getWebDAVPackage']);

        $package = $this->createMock(WebDAVFRSPackage::class);
        $package->method('userCanRead')->willReturn(false);
        $webDAVFRS->method('getWebDAVPackage')->willReturn($package);

        $FRSPackage = $this->createMock(FRSPackage::class);
        $webDAVFRS->method('getPackageList')->willReturn([$FRSPackage]);

        self::assertSame([], $webDAVFRS->getChildren());
    }

    /**
     * Testing when the user can read packages
     */
    public function testGetChildrenUserCanRead(): void
    {
        $webDAVFRS = $this->createPartialMock(WebDAVFRS::class, ['getPackageList', 'getWebDAVPackage']);

        $package = $this->createMock(WebDAVFRSPackage::class);
        $package->method('userCanRead')->willReturn(true);

        $webDAVFRS->method('getWebDAVPackage')->willReturn($package);

        $FRSPackage = $this->createMock(FRSPackage::class);
        $webDAVFRS->method('getPackageList')->willReturn([$FRSPackage]);

        self::assertSame([$package], $webDAVFRS->getChildren());
    }

    /**
     * Testing when the package doesn't exist
     */
    public function testGetChildFailWithNotExist(): void
    {
        $webDAVFRS = $this->createPartialMock(WebDAVFRS::class, ['getFRSPackageFromName', 'getWebDAVPackage', 'getUtils']);

        $FRSPackage    = $this->createMock(FRSPackage::class);
        $WebDAVPackage = $this->createMock(WebDAVFRSPackage::class);
        $WebDAVPackage->method('exist')->willReturn(false);
        $WebDAVPackage->method('getPackageId')->willReturn(1);

        $webDAVFRS->method('getFRSPackageFromName')->willReturn($FRSPackage);
        $webDAVFRS->method('getWebDAVPackage')->willReturn($WebDAVPackage);

        $this->expectException(NotFound::class);

        $webDAVFRS->method('getUtils')->willReturn(new WebDAVUtils());
        $webDAVFRS->getChild((string) $WebDAVPackage->getPackageId());
    }

    /**
     * Testing when the user can't read the package
     */
    public function testGetChildFailWithUserCanNotRead(): void
    {
        $webDAVFRS = $this->createPartialMock(WebDAVFRS::class, ['getFRSPackageFromName', 'getWebDAVPackage', 'getUtils']);

        $FRSPackage    = $this->createMock(FRSPackage::class);
        $WebDAVPackage = $this->createMock(WebDAVFRSPackage::class);
        $WebDAVPackage->method('exist')->willReturn(true);
        $WebDAVPackage->method('userCanRead')->willReturn(false);
        $WebDAVPackage->method('getPackageId')->willReturn(1);

        $webDAVFRS->method('getFRSPackageFromName')->willReturn($FRSPackage);
        $webDAVFRS->method('getWebDAVPackage')->willReturn($WebDAVPackage);

        $this->expectException(Forbidden::class);

        $webDAVFRS->method('getUtils')->willReturn(new WebDAVUtils());
        $webDAVFRS->getChild((string) $WebDAVPackage->getPackageId());
    }

    /**
     * Testing when the package exist and user can read
     */
    public function testSucceedGetChild(): void
    {
        $webDAVFRS = $this->createPartialMock(WebDAVFRS::class, ['getFRSPackageFromName', 'getWebDAVPackage', 'getUtils']);

        $FRSPackage    = $this->createMock(FRSPackage::class);
        $WebDAVPackage = $this->createMock(WebDAVFRSPackage::class);
        $WebDAVPackage->method('exist')->willReturn(true);
        $WebDAVPackage->method('userCanRead')->willReturn(true);
        $WebDAVPackage->method('getPackageId')->willReturn(1);

        $webDAVFRS->method('getFRSPackageFromName')->willReturn($FRSPackage);
        $webDAVFRS->method('getWebDAVPackage')->willReturn($WebDAVPackage);

        $webDAVFRS->method('getUtils')->willReturn(new WebDAVUtils());
        $this->assertEquals($webDAVFRS->getChild((string) $WebDAVPackage->getPackageId()), $WebDAVPackage);
    }

    /**
     * Testing creation of package when user is not admin
     */
    public function testCreateDirectoryFailWithUserNotAdmin(): void
    {
        $webDAVFRS = $this->createPartialMock(WebDAVFRS::class, ['userCanWrite']);

        $webDAVFRS->method('userCanWrite')->willReturn(false);
        $this->expectException(Forbidden::class);

        $webDAVFRS->createDirectory('pkg');
    }

    /**
     * Testing creation of package when the name already exist
     */
    public function testCreateDirectoryFailWithNameExist(): void
    {
        $webDAVFRS = $this->getMockBuilder(WebDAVFRS::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                1,
            ])
            ->onlyMethods(['userCanWrite', 'getUtils'])
            ->getMock();

        $webDAVFRS->method('userCanWrite')->willReturn(true);
        $frspf = $this->createMock(FRSPackageFactory::class);
        $frspf->method('isPackageNameExist')->willReturn(true);
        $utils = $this->createMock(WebDAVUtils::class);
        $utils->method('getPackageFactory')->willReturn($frspf);
        $webDAVFRS->method('getUtils')->willReturn($utils);
        $this->expectException(MethodNotAllowed::class);

        $webDAVFRS->createDirectory('pkg');
    }

    /**
     * Testing creation of package succeed
     */
    public function testCreateDirectorysucceed(): void
    {
        $webDAVFRS = $this->getMockBuilder(WebDAVFRS::class)
            ->setConstructorArgs([
                UserTestBuilder::anActiveUser()->build(),
                ProjectTestBuilder::aProject()->build(),
                1,
            ])
            ->onlyMethods(['userCanWrite', 'getUtils'])
            ->getMock();

        $webDAVFRS->method('userCanWrite')->willReturn(true);
        $frspf = $this->createMock(FRSPackageFactory::class);
        $frspf->method('isPackageNameExist')->willReturn(false);
        $frspf->method('create');
        $utils = $this->createMock(WebDAVUtils::class);
        $utils->method('getPackageFactory')->willReturn($frspf);
        $pm = $this->createMock(PermissionsManager::class);
        $utils->method('getPermissionsManager')->willReturn($pm);
        $webDAVFRS->method('getUtils')->willReturn($utils);

        $this->expectNotToPerformAssertions();

        $webDAVFRS->createDirectory('pkg');
    }
}
