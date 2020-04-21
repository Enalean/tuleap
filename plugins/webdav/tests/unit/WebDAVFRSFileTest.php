<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Sabre_DAV_Exception_Forbidden;
use Tuleap\GlobalLanguageMock;
use WebDAVFRSFile;
use WebDAVUtils;

require_once __DIR__ . '/bootstrap.php';

/**
 * This is the unit test of WebDAVFRSFile
 */
final class WebDAVFRSFileTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * Testing delete when user is not admin
     */
    public function testDeleteFailWithUserNotAdmin(): void
    {
        $webDAVFile = Mockery::mock(WebDAVFRSFile::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFile->shouldReceive('userCanWrite')->andReturnFalse();

        $this->expectException(Sabre_DAV_Exception_Forbidden::class);

        $webDAVFile->delete();
    }

    /**
     * Testing delete when file doesn't exist
     */
    public function testDeleteFileNotExist(): void
    {
        $webDAVFile = Mockery::mock(WebDAVFRSFile::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFile->shouldReceive('userCanWrite')->andReturnTrue();

        $frsff = \Mockery::mock(FRSFileFactory::class);
        $frsff->shouldReceive('delete_file')->andReturn(0);
        $utils = Mockery::mock(WebDAVUtils::class);
        $utils->shouldReceive('getFileFactory')->andReturn($frsff);
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getGroupId')->andReturn(102);
        $webDAVFile->shouldReceive('getProject')->andReturn($project);
        $webDAVFile->shouldReceive('getUtils')->andReturn($utils);
        $webDAVFile->shouldReceive('getFileID')->andReturn(4);

        $this->expectException(Sabre_DAV_Exception_Forbidden::class);

        $webDAVFile->delete();
    }

    /**
     * Testing succeeded delete
     */
    public function testDeleteSucceede(): void
    {
        $webDAVFile = Mockery::mock(WebDAVFRSFile::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFile->shouldReceive('userCanWrite')->andReturnTrue();

        $frsff = \Mockery::mock(FRSFileFactory::class);
        $frsff->shouldReceive('delete_file')->andReturn(1);
        $utils = Mockery::mock(WebDAVUtils::class);
        $utils->shouldReceive('getFileFactory')->andReturn($frsff);
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getGroupId')->andReturn(102);
        $webDAVFile->shouldReceive('getProject')->andReturn($project);
        $webDAVFile->shouldReceive('getUtils')->andReturn($utils);
        $webDAVFile->shouldReceive('getFileID')->andReturn(4);

        $webDAVFile->delete();
    }
}
