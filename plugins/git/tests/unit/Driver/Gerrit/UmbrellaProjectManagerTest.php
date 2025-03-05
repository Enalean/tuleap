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

declare(strict_types=1);

namespace Tuleap\Git\Driver\Gerrit;

use Git_Driver_Gerrit;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_MembershipManager;
use Git_Driver_Gerrit_UmbrellaProjectManager;
use Git_RemoteServer_GerritServer;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use ProjectUGroup;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UmbrellaProjectManagerTest extends TestCase
{
    private Git_RemoteServer_GerritServer&MockObject $server;
    private Git_Driver_Gerrit&MockObject $driver;
    private Project $project;
    private int $project_id           = 103;
    private string $project_unix_name = 'mozilla';
    private Git_Driver_Gerrit_MembershipManager&MockObject $membership_manager;
    private ProjectManager&MockObject $project_manager;
    private Git_Driver_Gerrit_UmbrellaProjectManager $umbrella_manager;
    private string $project_admins_gerrit_name = 'mozilla/project_admins';
    private string $project_admins_gerrit_parent_name;
    private Project $parent_project;
    private ProjectUGroup $parent_project_admins;
    private ProjectUGroup $project_admins;

    protected function setUp(): void
    {
        $this->server  = $this->createMock(Git_RemoteServer_GerritServer::class);
        $this->project = ProjectTestBuilder::aProject()
            ->withId($this->project_id)
            ->withUnixName($this->project_unix_name)
            ->withAccessPublic()
            ->build();

        $this->project_admins_gerrit_parent_name = 'grozilla/project_admins';
        $this->parent_project                    = ProjectTestBuilder::aProject()->withId(104)->withUnixName('grozilla')->build();

        $this->parent_project_admins = ProjectUGroupTestBuilder::buildProjectAdmins();

        $this->project_admins = ProjectUGroupTestBuilder::buildProjectAdmins();

        $this->driver = $this->createMock(Git_Driver_Gerrit::class);
        $this->driver->method('doesTheParentProjectExist')->willReturn(false);

        $driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $driver_factory->method('getDriver')->willReturn($this->driver);

        $ugroup_manager = $this->createMock(UGroupManager::class);
        $ugroup_manager->method('getUGroups')->willReturnCallback(fn(Project $project) => match ($project) {
            $this->project        => [$this->project_admins],
            $this->parent_project => [$this->parent_project_admins],
        });

        $this->membership_manager = $this->createMock(Git_Driver_Gerrit_MembershipManager::class);

        $this->project_manager = $this->createMock(ProjectManager::class);

        $this->umbrella_manager = new Git_Driver_Gerrit_UmbrellaProjectManager(
            $ugroup_manager,
            $this->project_manager,
            $this->membership_manager,
            $driver_factory
        );
    }

    public function testItOnlyCallsCreateParentProjectOnceIfTheProjectHasNoParents(): void
    {
        $this->project_manager->method('getParentProject')->with($this->project->getID())->willReturn(null);

        $this->driver->expects(self::once())->method('createProjectWithPermissionsOnly')->with($this->server, $this->project, $this->project_admins_gerrit_name);
        $this->driver->method('resetProjectInheritance');
        $this->membership_manager->method('createArrayOfGroupsForServer');

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects([$this->server], $this->project);
    }

    public function testItOnlyCallsCreateParentProjectTwiceIfTheProjectHasOneParent(): void
    {
        $this->project_manager->method('getParentProject')
            ->willReturnCallback(fn($id) => match ((int) $id) {
                (int) $this->project->getID()        => $this->parent_project,
                (int) $this->parent_project->getID() => null,
            });
        $this->driver->expects(self::exactly(2))->method('createProjectWithPermissionsOnly');
        $this->driver->method('resetProjectInheritance');
        $this->driver->method('setProjectInheritance');
        $this->membership_manager->method('createArrayOfGroupsForServer');

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects([$this->server], $this->project);
    }

    public function testItCallsCreateParentProjectWithTheCorrectParameters(): void
    {
        $this->project_manager->method('getParentProject')
            ->willReturnCallback(fn($id) => match ((int) $id) {
                (int) $this->project->getID()        => $this->parent_project,
                (int) $this->parent_project->getID() => null,
            });
        $matcher = self::atLeast(2);
        $this->driver->expects($matcher)->method('createProjectWithPermissionsOnly')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->server, $parameters[0]);
                self::assertSame($this->project, $parameters[1]);
                self::assertSame($this->project_admins_gerrit_name, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->server, $parameters[0]);
                self::assertSame($this->parent_project, $parameters[1]);
                self::assertSame($this->project_admins_gerrit_parent_name, $parameters[2]);
            }
        });
        $this->driver->method('resetProjectInheritance');
        $this->driver->method('setProjectInheritance');
        $this->membership_manager->method('createArrayOfGroupsForServer');

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects([$this->server], $this->project);
    }

    public function testItMigratesTheUserGroupsAlsoForParentUmbrellaProjects(): void
    {
        $this->project_manager->method('getParentProject')
            ->willReturnCallback(fn($id) => match ((int) $id) {
                (int) $this->project->getID()        => $this->parent_project,
                (int) $this->parent_project->getID() => null,
            });
        $this->driver->method('createProjectWithPermissionsOnly');
        $this->driver->method('resetProjectInheritance');
        $this->driver->method('setProjectInheritance');

        $this->membership_manager->expects(self::exactly(2))->method('createArrayOfGroupsForServer');

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects([$this->server], $this->project);
    }

    public function testItCallsTheDriverToSetTheParentProjectIfAny(): void
    {
        $this->project_manager->method('getParentProject')
            ->willReturnCallback(fn($id) => match ((int) $id) {
                (int) $this->project->getID()        => $this->parent_project,
                (int) $this->parent_project->getID() => null,
            });
        $this->driver->expects(self::once())->method('setProjectInheritance')->with($this->server, $this->project->getUnixName(), $this->parent_project->getUnixName());
        $this->driver->method('createProjectWithPermissionsOnly');
        $this->driver->method('resetProjectInheritance');
        $this->membership_manager->method('createArrayOfGroupsForServer');

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects([$this->server], $this->project);
    }

    public function testItDoesntCallTheDriverToSetTheParentProjectIfNone(): void
    {
        $this->project_manager->method('getParentProject')->with($this->project->getID())->willReturn(null);
        $this->driver->expects(self::never())->method('setProjectInheritance')->with($this->server, $this->project->getUnixName(), $this->parent_project->getUnixName());
        $this->driver->method('createProjectWithPermissionsOnly');
        $this->driver->method('resetProjectInheritance');
        $this->membership_manager->method('createArrayOfGroupsForServer');

        $this->umbrella_manager->recursivelyCreateUmbrellaProjects([$this->server], $this->project);
    }
}
