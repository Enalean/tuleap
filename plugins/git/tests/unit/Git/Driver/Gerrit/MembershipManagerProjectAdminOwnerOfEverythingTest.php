<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MembershipManagerProjectAdminOwnerOfEverythingTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Git_Driver_Gerrit */
    protected $driver;

    /** @var Git_RemoteServer_GerritServer */
    protected $remote_server;

    /** @var Git_Driver_Gerrit_UserAccountManager */
    protected $gerrit_user_manager;

    /** @var ProjectUGroup */
    protected $admin_ugroup;

    /** @var ProjectUGroup */
    protected $ugroup;

    /** @var UGroupManager */
    protected $ugroup_manager;

    /** @var ProjectManager */
    protected $project_manager;

    public function setUp(): void
    {
        parent::setUp();

        $this->driver              = \Mockery::spy(\Git_Driver_Gerrit::class);
        $this->driver_factory      = \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class)->shouldReceive('getDriver')->andReturns($this->driver)->getMock();
        $this->remote_server       = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $this->gerrit_user_manager = \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class);
        $this->project_manager     = \Mockery::spy(\ProjectManager::class);

        $this->ugroup_manager = \Mockery::spy(\UGroupManager::class);

        $this->membership_manager = \Mockery::mock(
            \Git_Driver_Gerrit_MembershipManager::class,
            [
                \Mockery::spy(Git_Driver_Gerrit_MembershipDao::class),
                $this->driver_factory,
                $this->gerrit_user_manager,
                Mockery::mock('Git_RemoteServer_GerritServerFactory'),
                Mockery::mock(\Psr\Log\LoggerInterface::class),
                $this->ugroup_manager,
                $this->project_manager
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $project_id    = 1236;
        $this->project = \Mockery::spy(\Project::class);
        $this->project->shouldReceive('getID')->andReturns($project_id);
        $this->project->shouldReceive('getUnixName')->andReturns('w3c');

        $this->ugroup = \Mockery::spy(\ProjectUGroup::class);
        $this->ugroup->shouldReceive('getId')->andReturns(25698);
        $this->ugroup->shouldReceive('getNormalizedName')->andReturns('coders');
        $this->ugroup->shouldReceive('getProject')->andReturns($this->project);
        $this->ugroup->shouldReceive('getProjectId')->andReturns($project_id);

        $this->admin_ugroup = \Mockery::spy(\ProjectUGroup::class);
        $this->admin_ugroup->shouldReceive('getId')->andReturns(ProjectUGroup::PROJECT_ADMIN);
        $this->admin_ugroup->shouldReceive('getNormalizedName')->andReturns('project_admins');
        $this->admin_ugroup->shouldReceive('getProject')->andReturns($this->project);
        $this->admin_ugroup->shouldReceive('getProjectId')->andReturns($project_id);
        $this->admin_ugroup->shouldReceive('getMembers')->andReturns(array());

        $this->ugroup_manager->shouldReceive('getUGroup')->andReturns($this->admin_ugroup);

        $this->project_manager->shouldReceive('getChildProjects')->andReturns(array());
    }

    public function testItCheckIfProjectAdminsGroupExist(): void
    {
        $this->ugroup->shouldReceive('getMembers')->andReturns(array());

        $this->membership_manager->shouldReceive('doesGroupExistOnServer')->with($this->remote_server, $this->admin_ugroup)->once();

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function testItCreatesTheProjectAdminGroupWhenNoExist(): void
    {
        $this->ugroup->shouldReceive('getMembers')->andReturns(array());

        $this->membership_manager->shouldReceive('doesGroupExistOnServer')->andReturns(false);
        $this->driver->shouldReceive('createGroup')->times(2);
        $this->driver->shouldReceive('createGroup')->with($this->remote_server, 'w3c/project_admins', 'w3c/project_admins')->ordered();
        $this->driver->shouldReceive('createGroup')->with($this->remote_server, 'w3c/coders', 'w3c/project_admins')->ordered();

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }
}
