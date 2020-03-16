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

use DataAccessResult;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Plugin;
use ProjectDao;
use Sabre_DAV_Exception_FileNotFound;
use Sabre_DAV_Exception_Forbidden;
use Tuleap\GlobalLanguageMock;
use WebDAVRoot;

require_once __DIR__ . '/bootstrap.php';

/**
 * This is the unit test of WebDAVRoot
 */
final class WebDAVRootTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    /**
     * @var \Mockery\Mock
     */
    private $webDAVRoot;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectDao
     */
    private $project_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $plugin            = Mockery::mock(Plugin::class);
        $this->user        = Mockery::spy(PFUser::class);
        $this->project_dao = Mockery::mock(ProjectDao::class);

        $this->webDAVRoot = \Mockery::mock(
            WebDAVRoot::class,
            [$plugin, $this->user, 1000000, $this->project_dao]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $plugin->shouldReceive('getId')->andReturn(999);
    }

    /**
     * Testing when There is no public projects
     */
    public function testGetChildrenWithNoPublicProjects(): void
    {
        $this->user->shouldReceive('isAnonymous')->andReturns(true);
        $this->webDAVRoot->shouldReceive('getPublicProjectList')->andReturns(array());
        $this->assertEquals($this->webDAVRoot->getChildren(), array());
    }

    /**
     * Testing when There is no public project with WebDAV plugin activated
     */
    public function testGetChildrenWithNoPublicProjectWithWebDAVActivated(): void
    {
        $this->user->shouldReceive('isAnonymous')->andReturns(true);
        $this->project_dao->shouldReceive('searchByPublicStatus')->andReturns([
            ['group_id' => 101]
        ]);

        $webDAVProject = \Mockery::spy(\WebDAVProject::class);

        $this->webDAVRoot->shouldReceive('getWebDAVProject')->andReturns($webDAVProject);
        $this->webDAVRoot->shouldReceive('isWebDAVAllowedForProject')->andReturnFalse();
        $this->assertEquals($this->webDAVRoot->getChildren(), array());
    }

    /**
     * Testing when there is public projects with WebDAV activated
     */
    public function testGetChildrenWithPublicProjects(): void
    {
        $this->user->shouldReceive('isAnonymous')->andReturns(true);

        $webDAVProject = \Mockery::spy(\WebDAVProject::class);

        $this->webDAVRoot->shouldReceive('getWebDAVProject')->andReturns($webDAVProject);
        $this->webDAVRoot->shouldReceive('getPublicProjectList')->andReturns(array($webDAVProject));

        $this->assertEquals($this->webDAVRoot->getChildren(), array($webDAVProject));
    }

    /**
     * Testing when The user can see no project
     */
    public function testGetChildrenNoUserProjects(): void
    {
        $this->user->shouldReceive('isAnonymous')->andReturns(false);


        $this->webDAVRoot->shouldReceive('getUserProjectList')->andReturns([]);
        $this->assertEquals($this->webDAVRoot->getChildren(), array());
    }

    /**
     * Testing when the user have no projects with WebDAV activated
     */
    public function testGetChildrenUserHaveNoProjectsWithWebDAVActivated(): void
    {
        $this->user->shouldReceive('isAnonymous')->andReturns(false);
        $this->user->shouldReceive('getProjects')->andReturns([
            101
        ]);

        $webDAVProject = \Mockery::spy(\WebDAVProject::class);

        $this->webDAVRoot->shouldReceive('getWebDAVProject')->andReturns($webDAVProject);
        $this->webDAVRoot->shouldReceive('isWebDAVAllowedForProject')->with(101)->andReturnFalse();

        $this->assertEquals($this->webDAVRoot->getChildren(), array());
    }

    /**
     * Testing when the user have projects
     */
    public function testGetChildrenUserHaveProjects(): void
    {
        $this->user->shouldReceive('isAnonymous')->andReturns(false);

        $webDAVProject = \Mockery::spy(\WebDAVProject::class);

        $this->webDAVRoot->shouldReceive('getWebDAVProject')->andReturns($webDAVProject);
        $this->webDAVRoot->shouldReceive('getUserProjectList')->andReturns(array($webDAVProject));

        $this->assertEquals($this->webDAVRoot->getChildren(), array($webDAVProject));
    }

    /**
     * Testing when the project doesn't have WebDAV plugin activated
     */
    public function testGetChildFailWithWebDAVNotActivated(): void
    {
        $this->webDAVRoot->shouldReceive('isWebDAVAllowedForProject')->andReturns(false);

        $this->expectException(Sabre_DAV_Exception_Forbidden::class);

        $dar = Mockery::spy(DataAccessResult::class);
        $dar->shouldReceive('getRow')->andReturn(['group_id' => 101]);
        $this->project_dao->shouldReceive('searchByUnixGroupName')->with('project1')->andReturn($dar);

        $this->webDAVRoot->getChild('project1');
    }

    /**
     * Testing when the project doesn't exist
     */
    public function testGetChildFailWithNotExist(): void
    {
        $this->webDAVRoot->shouldReceive('isWebDAVAllowedForProject')->andReturns(true);
        $project = \Mockery::spy(\WebDAVProject::class);
        $project->shouldReceive('exist')->andReturns(false);

        $dar = Mockery::spy(DataAccessResult::class);
        $dar->shouldReceive('getRow')->andReturn(['group_id' => 101]);
        $this->project_dao->shouldReceive('searchByUnixGroupName')->with('project1')->andReturn($dar);

        $this->webDAVRoot->shouldReceive('getWebDAVProject')->andReturns($project);

        $this->expectException(Sabre_DAV_Exception_FileNotFound::class);

        $this->webDAVRoot->getChild('project1');
    }

    /**
     * Testing when the project is not active
     */
    public function testGetChildFailWithNotActive(): void
    {
        $this->webDAVRoot->shouldReceive('isWebDAVAllowedForProject')->andReturns(true);
        $project = \Mockery::spy(\WebDAVProject::class);
        $project->shouldReceive('exist')->andReturns(true);
        $project->shouldReceive('isActive')->andReturns(false);

        $dar = Mockery::spy(DataAccessResult::class);
        $dar->shouldReceive('getRow')->andReturn(['group_id' => 101]);
        $this->project_dao->shouldReceive('searchByUnixGroupName')->with('project1')->andReturn($dar);

        $this->webDAVRoot->shouldReceive('getWebDAVProject')->andReturns($project);

        $this->expectException(Sabre_DAV_Exception_Forbidden::class);

        $this->webDAVRoot->getChild('project1');
    }

    /**
     * Testing when the user can't access the project
     */
    public function testGetChildFailWithUserCanNotRead(): void
    {
        $this->webDAVRoot->shouldReceive('isWebDAVAllowedForProject')->andReturns(true);
        $project = \Mockery::spy(\WebDAVProject::class);
        $project->shouldReceive('exist')->andReturns(true);
        $project->shouldReceive('isActive')->andReturns(true);
        $project->shouldReceive('userCanRead')->andReturns(false);

        $dar = Mockery::spy(DataAccessResult::class);
        $dar->shouldReceive('getRow')->andReturn(['group_id' => 101]);
        $this->project_dao->shouldReceive('searchByUnixGroupName')->with('project1')->andReturn($dar);

        $this->webDAVRoot->shouldReceive('getWebDAVProject')->andReturns($project);

        $this->expectException(Sabre_DAV_Exception_Forbidden::class);

        $this->webDAVRoot->getChild('project1');
    }

    /**
     * Testing when the project exist, is active and user can read
     */
    public function testSucceedGetChild(): void
    {
        $this->webDAVRoot->shouldReceive('isWebDAVAllowedForProject')->andReturns(true);
        $project = \Mockery::spy(\WebDAVProject::class);
        $project->shouldReceive('exist')->andReturns(true);
        $project->shouldReceive('isActive')->andReturns(true);

        $dar = Mockery::spy(DataAccessResult::class);
        $dar->shouldReceive('getRow')->andReturn(['group_id' => 101]);
        $this->project_dao->shouldReceive('searchByUnixGroupName')->with('project1')->andReturn($dar);

        $project->shouldReceive('userCanRead')->andReturns(true);

        $this->webDAVRoot->shouldReceive('getWebDAVProject')->andReturns($project);

        $this->assertEquals($this->webDAVRoot->getChild('project1'), $project);
    }
}
