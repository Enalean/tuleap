<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of tuleap.
 *
 * tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\WebDAV;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebDAVUtilsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PFUser&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;
    /**
     * @var \Project&\PHPUnit\Framework\MockObject\MockObject
     */
    private $project;
    /**
     * @var \WebDAVUtils&\PHPUnit\Framework\MockObject\MockObject
     */
    private $utils;
    /**
     * @var \Tuleap\FRS\FRSPermissionManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $frs_permission_manager;
    /**
     * @var \ProjectManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $project_manager;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->user                   = $this->createMock(\PFUser::class);
        $this->project                = $this->createMock(\Project::class);
        $this->utils                  = $this->createPartialMock(\WebDAVUtils::class, ['getFRSPermissionManager', 'getProjectManager']);
        $this->frs_permission_manager = $this->createMock(\Tuleap\FRS\FRSPermissionManager::class);
        $this->project_manager        = $this->createMock(\ProjectManager::class);

        $this->utils->method('getFRSPermissionManager')->willReturn($this->frs_permission_manager);
        $this->utils->method('getProjectManager')->willReturn($this->project_manager);

        $this->project_manager->method('getProject')->with(101)->willReturn($this->project);
    }

    public function testUserIsAdminNotSuperUserNotmember(): void
    {
        $this->user->method('isSuperUser')->willReturn(false);
        $this->user->method('isMember')->willReturn(false);
        $this->frs_permission_manager->method('isAdmin')->with($this->project, $this->user)->willReturn(false);

        self::assertFalse($this->utils->userIsAdmin($this->user, 101));
    }

    public function testUserIsAdminSuperUser(): void
    {
        $this->user->method('isSuperUser')->willReturn(true);
        $this->user->method('isMember')->willReturn(false);

        self::assertTrue($this->utils->userIsAdmin($this->user, 101));
    }

    public function testUserIsAdminFRSAdmin(): void
    {
        $this->user->method('isSuperUser')->willReturn(false);
        $this->frs_permission_manager->method('isAdmin')->with($this->project, $this->user)->willReturn(true);

        self::assertTrue($this->utils->userIsAdmin($this->user, 101));
    }

    public function testUserIsAdminSuperuserFRSAdmin(): void
    {
        $this->user->method('isSuperUser')->willReturn(true);
        $this->frs_permission_manager->method('isAdmin')->with($this->project, $this->user)->willReturn(true);

        self::assertTrue($this->utils->userIsAdmin($this->user, 101));
    }
}
