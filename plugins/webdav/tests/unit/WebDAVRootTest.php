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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Project_AccessPrivateException;
use ProjectManager;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use WebDAVRoot;

final class WebDAVRootTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Mockery\Mock
     */
    private $webDAVRoot;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\WebDAVUtils
     */
    private $utils;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PluginManager
     */
    private $plugin_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\WebDAVPlugin
     */
    private $plugin;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;

    protected function setUp(): void
    {
        $this->plugin                 = Mockery::mock(\WebDAVPlugin::class, ['getId' => 999]);
        $this->user                   = Mockery::mock(PFUser::class, ['isAnonymous' => false]);
        $this->project_manager        = Mockery::mock(\ProjectManager::class);
        $this->utils                  = Mockery::mock(\WebDAVUtils::class, ['getEventManager' => new \EventManager()]);
        $this->plugin_manager         = Mockery::mock(\PluginManager::class);
        $this->project_access_checker = Mockery::mock(ProjectAccessChecker::class);

        $this->webDAVRoot = new WebDAVRoot(
            $this->plugin,
            $this->user,
            1000000,
            $this->project_manager,
            $this->utils,
            $this->plugin_manager,
            $this->project_access_checker,
        );
    }

    /**
     * Testing when there is public projects with WebDAV activated
     */
    public function testGetChildrenWithPublicProjects(): void
    {
        $this->user->shouldReceive('isAnonymous')->andReturns(true);

        $this->expectException(Forbidden::class);

        $this->webDAVRoot->getChildren();
    }

    /**
     * Testing when The user can see no project
     */
    public function testGetChildrenNoUserProjects(): void
    {
        $this->user->shouldReceive('getProjects')->andReturn([]);

        self::assertEquals([], $this->webDAVRoot->getChildren());
    }

    /**
     * Testing when the user have no projects with WebDAV activated
     */
    public function testGetChildrenUserHaveNoProjectsWithWebDAVActivated(): void
    {
        $this->user->shouldReceive('getProjects')->andReturns([
            '101',
        ]);

        $this->project_manager->shouldReceive('getProject')->with(101)->andReturn(ProjectTestBuilder::aProject()->withId(101)->build());

        $this->plugin_manager->shouldReceive('isPluginAllowedForProject')->with($this->plugin, 101)->andReturnFalse();

        self::assertEquals([], $this->webDAVRoot->getChildren());
    }

    /**
     * Testing when the user have projects
     */
    public function testGetChildrenUserHaveProjects(): void
    {
        $this->user->shouldReceive('getProjects')->andReturns([
            '101',
        ]);

        $this->project_manager->shouldReceive('getProject')->with(101)->andReturn(ProjectTestBuilder::aProject()->withId(101)->withUnixName('FooBar')->build());

        $this->plugin_manager->shouldReceive('isPluginAllowedForProject')->with($this->plugin, 101)->andReturnTrue();

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->once();

        $children = $this->webDAVRoot->getChildren();
        self::assertCount(1, $children);
        self::assertEquals('foobar', $children[0]->getName());
    }

    /**
     * Testing when the project doesn't have WebDAV plugin activated
     */
    public function testGetChildFailWithWebDAVNotActivated(): void
    {
        $this->plugin_manager->shouldReceive('isPluginAllowedForProject')->with($this->plugin, 101)->andReturnFalse();

        $this->project_manager->shouldReceive('getProjectByUnixName')->with('project1')->andReturn(ProjectTestBuilder::aProject()->withId(101)->withUnixName('project1')->build());

        $this->expectException(Forbidden::class);

        $this->webDAVRoot->getChild('project1');
    }

    /**
     * Testing when the project doesn't exist
     */
    public function testGetChildFailWithNotExist(): void
    {
        $this->project_manager->shouldReceive('getProjectByUnixName')->with('project1')->andReturn(null);

        $this->expectException(NotFound::class);

        $this->webDAVRoot->getChild('project1');
    }

    /**
     * Testing when the project is not active
     */
    public function testGetChildFailWithNotActive(): void
    {
        $this->project_manager->shouldReceive('getProjectByUnixName')->with('project1')->andReturn(ProjectTestBuilder::aProject()->withStatusDeleted()->build());

        $this->expectException(Forbidden::class);

        $this->webDAVRoot->getChild('project1');
    }

    /**
     * Testing when the user can't access the project
     */
    public function testGetChildFailWithUserCanNotRead(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->withUnixName('project1')->build();
        $this->project_manager->shouldReceive('getProjectByUnixName')->with('project1')->andReturn($project);

        $this->plugin_manager->shouldReceive('isPluginAllowedForProject')->with($this->plugin, 101)->andReturnTrue();

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->user, $project)->andThrow(new Project_AccessPrivateException());

        $this->expectException(Forbidden::class);

        $this->webDAVRoot->getChild('project1');
    }

    /**
     * Testing when the project exist, is active and user can read
     */
    public function testSucceedGetChild(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->withUnixName('project1')->build();
        $this->project_manager->shouldReceive('getProjectByUnixName')->with('project1')->andReturn($project);

        $this->plugin_manager->shouldReceive('isPluginAllowedForProject')->with($this->plugin, 101)->andReturnTrue();

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->user, $project)->once();

        self::assertEquals('project1', $this->webDAVRoot->getChild('project1')->getName());
    }
}
