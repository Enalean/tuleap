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
use Project;
use Sabre\DAV\Exception\Forbidden;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use WebDAVFRSFile;
use WebDAVUtils;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebDAVFRSFileTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->user    = UserTestBuilder::aUser()->build();
        $this->project = ProjectTestBuilder::aProject()->build();
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    /**
     * Testing delete when user is not admin
     */
    public function testDeleteFailWithUserNotAdmin(): void
    {
        $utils = $this->createMock(WebDAVUtils::class);
        $utils->expects(self::once())->method('userCanWrite')->with($this->user, $this->project->getID())->willReturn(false);
        $webDAVFile = new WebDAVFRSFile($this->user, $this->project, new \FRSFile(['file_id' => 4]), $utils);

        $this->expectException(Forbidden::class);

        $webDAVFile->delete();
    }

    /**
     * Testing delete when file doesn't exist
     */
    public function testDeleteFileNotExist(): void
    {
        $frsff = $this->createMock(FRSFileFactory::class);
        $frsff->method('delete_file')->willReturn(0);
        $utils = $this->createMock(WebDAVUtils::class);
        $utils->method('getFileFactory')->willReturn($frsff);
        $utils->expects(self::once())->method('userCanWrite')->with($this->user, $this->project->getID())->willReturn(true);
        $project = $this->createMock(Project::class);
        $project->method('getGroupId')->willReturn(102);

        $webDAVFile = new WebDAVFRSFile($this->user, $this->project, new \FRSFile(['file_id' => 4]), $utils);

        $this->expectException(Forbidden::class);

        $webDAVFile->delete();
    }

    /**
     * Testing succeeded delete
     */
    public function testDeleteSucceede(): void
    {
        $frsff = $this->createMock(FRSFileFactory::class);
        $frsff->method('delete_file')->willReturn(1);
        $utils = $this->createMock(WebDAVUtils::class);
        $utils->method('getFileFactory')->willReturn($frsff);
        $utils->expects(self::once())->method('userCanWrite')->with($this->user, $this->project->getID())->willReturn(true);
        $project = $this->createMock(Project::class);
        $project->method('getGroupId')->willReturn(102);

        $webDAVFile = new WebDAVFRSFile($this->user, $this->project, new \FRSFile(['file_id' => 4]), $utils);

        $webDAVFile->delete();
    }
}
