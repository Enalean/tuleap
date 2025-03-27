<?php
/**
 * Copyright (c) Enalean 2019-Present. All rights reserved.
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
use Project_AccessPrivateException;
use ProjectManager;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use WebDAVRoot;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebDAVRootTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private WebDAVRoot $webDAVRoot;
    /**
     * @var PFUser&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;
    /**
     * @var ProjectManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $project_manager;
    /**
     * @var \WebDAVUtils&\PHPUnit\Framework\MockObject\MockObject
     */
    private $utils;
    /**
     * @var \PluginManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $plugin_manager;
    /**
     * @var \WebDAVPlugin&\PHPUnit\Framework\MockObject\MockObject
     */
    private $plugin;
    /**
     * @var ProjectAccessChecker&\PHPUnit\Framework\MockObject\MockObject
     */
    private $project_access_checker;

    protected function setUp(): void
    {
        $this->plugin                 = $this->createMock(\WebDAVPlugin::class);
        $this->user                   = $this->createMock(PFUser::class);
        $this->project_manager        = $this->createMock(\ProjectManager::class);
        $this->utils                  = $this->createMock(\WebDAVUtils::class);
        $this->plugin_manager         = $this->createMock(\PluginManager::class);
        $this->project_access_checker = $this->createMock(ProjectAccessChecker::class);

        $this->plugin->method('getId')->willReturn(999);
        $this->user->method('isAnonymous')->willReturn(false);
        $this->utils->method('getEventManager')->willReturn(new \EventManager());

        $this->webDAVRoot = new WebDAVRoot(
            $this->plugin,
            $this->user,
            1000000,
            $this->project_manager,
            $this->utils,
            $this->plugin_manager,
            $this->project_access_checker,
        );

        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    /**
     * Testing when there is public projects with WebDAV activated
     */
    public function testGetChildrenWithPublicProjects(): void
    {
        $webdav_root = new WebDAVRoot(
            $this->plugin,
            UserTestBuilder::anAnonymousUser()->build(),
            1000000,
            $this->project_manager,
            $this->utils,
            $this->plugin_manager,
            $this->project_access_checker,
        );

        $this->expectException(Forbidden::class);

        $webdav_root->getChildren();
    }

    /**
     * Testing when The user can see no project
     */
    public function testGetChildrenNoUserProjects(): void
    {
        $this->user->method('getProjects')->willReturn([]);

        self::assertEquals([], $this->webDAVRoot->getChildren());
    }

    /**
     * Testing when the user have no projects with WebDAV activated
     */
    public function testGetChildrenUserHaveNoProjectsWithWebDAVActivated(): void
    {
        $this->user->method('getProjects')->willReturn([
            '101',
        ]);

        $this->project_manager->method('getProject')->with(101)->willReturn(ProjectTestBuilder::aProject()->withId(101)->build());

        $this->plugin_manager->method('isPluginAllowedForProject')->with($this->plugin, 101)->willReturn(false);

        self::assertEquals([], $this->webDAVRoot->getChildren());
    }

    /**
     * Testing when the user have projects
     */
    public function testGetChildrenUserHaveProjects(): void
    {
        $this->user->method('getProjects')->willReturn([
            '101',
        ]);

        $this->project_manager->method('getProject')->with(101)->willReturn(ProjectTestBuilder::aProject()->withId(101)->withUnixName('FooBar')->build());

        $this->plugin_manager->method('isPluginAllowedForProject')->with($this->plugin, 101)->willReturn(true);

        $this->project_access_checker->expects($this->once())->method('checkUserCanAccessProject');

        $children = $this->webDAVRoot->getChildren();
        self::assertCount(1, $children);
        self::assertEquals('foobar', $children[0]->getName());
    }

    /**
     * Testing when the project doesn't have WebDAV plugin activated
     */
    public function testGetChildFailWithWebDAVNotActivated(): void
    {
        $this->plugin_manager->method('isPluginAllowedForProject')->with($this->plugin, 101)->willReturn(false);

        $this->project_manager->method('getProjectByUnixName')->with('project1')->willReturn(
            ProjectTestBuilder::aProject()->withId(101)->withUnixName('project1')->build()
        );

        $this->expectException(Forbidden::class);

        $this->webDAVRoot->getChild('project1');
    }

    /**
     * Testing when the project doesn't exist
     */
    public function testGetChildFailWithNotExist(): void
    {
        $this->project_manager->method('getProjectByUnixName')->with('project1')->willReturn(null);

        $this->expectException(NotFound::class);

        $this->webDAVRoot->getChild('project1');
    }

    /**
     * Testing when the project is not active
     */
    public function testGetChildFailWithNotActive(): void
    {
        $this->project_manager->method('getProjectByUnixName')->with('project1')->willReturn(
            ProjectTestBuilder::aProject()->withStatusDeleted()->build()
        );

        $this->expectException(Forbidden::class);

        $this->webDAVRoot->getChild('project1');
    }

    /**
     * Testing when the user can't access the project
     */
    public function testGetChildFailWithUserCanNotRead(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->withUnixName('project1')->build();
        $this->project_manager->method('getProjectByUnixName')->with('project1')->willReturn($project);

        $this->plugin_manager->method('isPluginAllowedForProject')->with($this->plugin, 101)->willReturn(true);

        $this->project_access_checker->method('checkUserCanAccessProject')->with($this->user, $project)->willThrowException(
            new Project_AccessPrivateException()
        );

        $this->expectException(Forbidden::class);

        $this->webDAVRoot->getChild('project1');
    }

    /**
     * Testing when the project exist, is active and user can read
     */
    public function testSucceedGetChild(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->withUnixName('project1')->build();
        $this->project_manager->method('getProjectByUnixName')->with('project1')->willReturn($project);

        $this->plugin_manager->method('isPluginAllowedForProject')->with($this->plugin, 101)->willReturn(true);

        $this->project_access_checker->expects($this->once())->method('checkUserCanAccessProject')->with($this->user, $project);

        self::assertEquals('project1', $this->webDAVRoot->getChild('project1')->getName());
    }
}
