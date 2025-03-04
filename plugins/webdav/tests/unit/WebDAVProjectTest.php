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

use PFUser;
use Project;
use Sabre\DAV\Exception\NotFound;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;
use WebDAVProject;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebDAVProjectTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \WebDAVUtils&\PHPUnit\Framework\MockObject\MockObject
     */
    private $utils;
    private WebDAVProject $webdav_project;
    /**
     * @var Project&\PHPUnit\Framework\MockObject\MockObject
     */
    private $project;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->user    = UserTestBuilder::aUser()->build();
        $this->utils   = $this->createMock(\WebDAVUtils::class);
        $this->project = $this->createMock(\Project::class);

        $this->webdav_project = new WebDAVProject(
            $this->user,
            $this->project,
            12,
            $this->utils
        );

        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    /**
     * Testing when The project have no active services
     */
    public function testGetChildrenNoServices(): void
    {
        $this->utils->method('getEventManager')->willReturn(new \EventManager());

        $this->project->method('usesFile')->willReturn(false);

        self::assertSame([], $this->webdav_project->getChildren());
    }

    /**
     * Testing when the service doesn't exist
     */
    public function testGetChildFailWithNotExist(): void
    {
        $this->utils->method('getEventManager')->willReturn(new \EventManager());

        $this->project->method('usesFile')->willReturn(false);

        $this->expectException(NotFound::class);

        $this->webdav_project->getChild('Files');
    }
}
