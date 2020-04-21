<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Git_Driver_Gerrit_ProjectCreator_CreateParentUmbrellaProjectsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Git_RemoteServer_GerritServer */
    protected $server;

    /** @var Git_Driver_Gerrit */
    protected $driver;

    /** @var Project */
    protected $project;
    protected $project_id = 103;
    protected $project_unix_name = 'mozilla';

    /** @var UGroupManager */
    protected $ugroup_manager;

    /** @var Git_Driver_Gerrit_MembershipManager */
    protected $membership_manager;

    /** @var ProjectManager */
    protected $project_manager;

    /** @var Git_Driver_Gerrit_UmbrellaProjectManager */
    protected $umbrella_manager;

    protected $project_admins_gerrit_name  = 'mozilla/project_admins';

    protected function setUp(): void
    {
        parent::setUp();

        $this->server  = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getId')->andReturn($this->project_id);
        $this->project->shouldReceive('getUnixName')->andReturn($this->project_unix_name);
        $this->project->shouldReceive('isPublic')->andReturnTrue();

        $this->project_admins_gerrit_parent_name = 'grozilla/project_admins';
        $this->parent_project = Mockery::mock(Project::class);
        $this->parent_project->shouldReceive('getId')->andReturn(104);
        $this->parent_project->shouldReceive('getUnixName')->andReturn('grozilla');

        $this->parent_project_admins = Mockery::mock(ProjectUGroup::class);
        $this->parent_project_admins->shouldReceive('getId')->andReturn(ProjectUGroup::PROJECT_ADMIN);
        $this->parent_project_admins->shouldReceive('getNormalizedName')->andReturn('project_admins');

        $this->project_admins = Mockery::mock(ProjectUGroup::class);
        $this->project_admins->shouldReceive('getId')->andReturn(ProjectUGroup::PROJECT_ADMIN);
        $this->project_admins->shouldReceive('getNormalizedName')->andReturn('project_admins');

        $this->driver = \Mockery::spy(\Git_Driver_Gerrit::class);
        $this->driver->shouldReceive('doesTheParentProjectExist')->andReturns(false);

        $driver_factory = \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class)->shouldReceive('getDriver')->andReturns($this->driver)->getMock();

        $this->ugroup_manager = \Mockery::spy(\UGroupManager::class);
        $this->ugroup_manager->shouldReceive('getUGroups')->with($this->project)->andReturns(array($this->project_admins));
        $this->ugroup_manager->shouldReceive('getUGroups')->with($this->parent_project)->andReturns(array($this->parent_project_admins));

        $this->membership_manager = \Mockery::spy(\Git_Driver_Gerrit_MembershipManager::class);

        $this->project_manager = \Mockery::spy(\ProjectManager::class);

        $this->umbrella_manager = new Git_Driver_Gerrit_UmbrellaProjectManager(
            $this->ugroup_manager,
            $this->project_manager,
            $this->membership_manager,
            $driver_factory
        );
    }

    public function testItOnlyCallsCreateParentProjectOnceIfTheProjectHasNoParents(): void
    {
        $this->project_manager->shouldReceive('getParentProject')->with($this->project->getID())->andReturns(null);

        $this->driver->shouldReceive('createProjectWithPermissionsOnly')->with($this->server, $this->project, $this->project_admins_gerrit_name)->once();

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects(array($this->server), $this->project);
    }

    public function testItOnlyCallsCreateParentProjectTwiceIfTheProjectHasOneParent(): void
    {
        $this->project_manager->shouldReceive('getParentProject')->with($this->project->getID())->andReturns($this->parent_project);
        $this->project_manager->shouldReceive('getParentProject')->with($this->parent_project->getID())->andReturns(null);
        $this->driver->shouldReceive('createProjectWithPermissionsOnly')->times(2);

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects(array($this->server), $this->project);
    }

    public function testItCallsCreateParentProjectWithTheCorrectParameters(): void
    {
        $this->project_manager->shouldReceive('getParentProject')->with($this->project->getID())->andReturns($this->parent_project);
        $this->project_manager->shouldReceive('getParentProject')->with($this->parent_project->getID())->andReturns(null);

        $this->driver->shouldReceive('createProjectWithPermissionsOnly')->with($this->server, $this->project, $this->project_admins_gerrit_name)->ordered();
        $this->driver->shouldReceive('createProjectWithPermissionsOnly')->with($this->server, $this->parent_project, $this->project_admins_gerrit_parent_name)->ordered();

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects(array($this->server), $this->project);
    }

    public function testItMigratesTheUserGroupsAlsoForParentUmbrellaProjects(): void
    {
        $this->project_manager->shouldReceive('getParentProject')->with($this->project->getID())->andReturns($this->parent_project);
        $this->project_manager->shouldReceive('getParentProject')->with($this->parent_project->getID())->andReturns(null);

        $this->membership_manager->shouldReceive('createArrayOfGroupsForServer')->times(2);

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects(array($this->server), $this->project);
    }

    public function testItCallsTheDriverToSetTheParentProjectIfAny(): void
    {
        $this->project_manager->shouldReceive('getParentProject')->with($this->project->getID())->andReturns($this->parent_project);
        $this->project_manager->shouldReceive('getParentProject')->with($this->parent_project->getID())->andReturns(null);

        $this->driver->shouldReceive('setProjectInheritance')->with($this->server, $this->project->getUnixName(), $this->parent_project->getUnixName())->once();

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects(array($this->server), $this->project);
    }

    public function testItDoesntCallTheDriverToSetTheParentProjectIfNone(): void
    {
        $this->project_manager->shouldReceive('getParentProject')->with($this->project->getID())->andReturns(null);

        $this->driver->shouldReceive('setProjectInheritance')->with($this->server, $this->project->getUnixName(), $this->parent_project->getUnixName())->never();

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects(array($this->server), $this->project);
    }
}
