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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MembershipManagerCreateGroupTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $logger;
    private $dao;

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
    /**
     * @var \Mockery\Mock|Git_Driver_Gerrit_MembershipManager
     */
    private $membership_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver              = \Mockery::spy(\Git_Driver_Gerrit::class);
        $this->driver_factory      = \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class)->shouldReceive('getDriver')->andReturns($this->driver)->getMock();
        $this->remote_server       = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $this->gerrit_user_manager = \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class);
        $this->project_manager     = \Mockery::spy(\ProjectManager::class);

        $this->ugroup_manager = \Mockery::spy(\UGroupManager::class);

        $this->membership_manager = \Mockery::mock(\Git_Driver_Gerrit_MembershipManager::class)->makePartial()->shouldAllowMockingProtectedMethods();

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

        $this->remote_server_factory  = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->git_repository_factory = \Mockery::spy(\GitRepositoryFactory::class);
        $this->logger                 = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->dao                    = \Mockery::spy(Git_Driver_Gerrit_MembershipDao::class);
        $this->user1                  = \Mockery::spy(\PFUser::class);
        $this->user2                  = \Mockery::spy(\PFUser::class);

        $this->membership_manager = \Mockery::mock(
            \Git_Driver_Gerrit_MembershipManager::class,
            [
                $this->dao,
                $this->driver_factory,
                $this->gerrit_user_manager,
                $this->remote_server_factory,
                $this->logger,
                $this->ugroup_manager,
                $this->project_manager
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->project_manager->shouldReceive('getChildProjects')->andReturns(array());
        $this->membership_manager->shouldReceive('doesGroupExistOnServer')->andReturns(true);
    }

    public function testItCreateGroupOnAllGerritServersTheProjectUses()
    {
        $this->ugroup->shouldReceive('getMembers')->andReturns(array());
        $this->remote_server_factory->shouldReceive('getServersForProject')
            ->with($this->project)
            ->once()
            ->andReturns(array($this->remote_server));
        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function testItCreatesGerritGroupFromUGroup()
    {
        $this->ugroup->shouldReceive('getMembers')->andReturns(array());
        $this->driver->shouldReceive('createGroup')
            ->with($this->remote_server, 'w3c/coders', 'w3c/project_admins')
            ->once()
            ->andReturns('w3c/coders');

        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
        $this->assertEquals('w3c/coders', $gerrit_group_name);
    }

    public function testItAddGroupMembersOnCreation()
    {
        $this->driver->shouldReceive('createGroup')->with($this->remote_server, 'w3c/coders', 'w3c/project_admins')->once();
        $this->driver->shouldReceive('createGroup')->andReturns('w3c/coders');

        $mary = new PFUser([
            'language_id' => 'en',
            'user_id' => 12
        ]);
        $bob  = new PFUser([
            'language_id' => 'en',
            'user_id' => 25
        ]);
        $this->ugroup->shouldReceive('getMembers')->andReturns(array($mary, $bob));

        $this->membership_manager->shouldReceive('addUserToGroupWithoutFlush')->times(2);
        $this->membership_manager->shouldReceive('addUserToGroupWithoutFlush')->with($mary, $this->ugroup)->ordered();
        $this->membership_manager->shouldReceive('addUserToGroupWithoutFlush')->with($bob, $this->ugroup)->ordered();

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function testItStoresTheGroupInTheDb()
    {
        $this->ugroup->shouldReceive('getMembers')->andReturns(array());
        $this->remote_server->shouldReceive('getId')->andReturns(666);

        $this->dao->shouldReceive('addReference')->with(1236, 25698, 666)->once();

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function testItDoesntCreateAGroupThatAlreadyExist()
    {
        $this->ugroup->shouldReceive('getMembers')->andReturns(array());
        $this->driver->shouldReceive('doesTheGroupExist')
            ->with($this->remote_server, 'w3c/coders')
            ->once()
            ->andReturns(true);

        $this->driver->shouldReceive('createGroup')->never();

        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
        $this->assertEquals('w3c/coders', $gerrit_group_name);
    }

    public function testItAddsMembersToAGroupThatAlreadyExists()
    {
        $this->ugroup->shouldReceive('getMembers')->andReturns(array($this->user1, $this->user2));
        $this->ugroup->shouldReceive('getId')->andReturns(123);

        $this->driver->shouldReceive('doesTheGroupExist')->andReturns(true);
        $this->ugroup->shouldReceive('getSourceGroup')->andReturns(false);

        $this->membership_manager->shouldReceive('addUserToGroupWithoutFlush')->times(2);
        $this->membership_manager->shouldReceive('addUserToGroupWithoutFlush')->with($this->user1, $this->ugroup)->ordered();
        $this->membership_manager->shouldReceive('addUserToGroupWithoutFlush')->with($this->user2, $this->ugroup)->ordered();

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function testItCreatesGerritGroupOnEachServer()
    {
        $this->ugroup->shouldReceive('getMembers')->andReturns(array());
        $remote_server1 = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $remote_server2 = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $this->remote_server_factory->shouldReceive('getServersForProject')->andReturns(array($remote_server1, $remote_server2));

        $this->driver->shouldReceive('createGroup')->times(2);
        $this->driver->shouldReceive('createGroup')->with($remote_server1, 'w3c/coders', 'w3c/project_admins')->ordered();
        $this->driver->shouldReceive('createGroup')->with($remote_server2, 'w3c/coders', 'w3c/project_admins')->ordered();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function testItStoresTheGroupInTheDbForEachServer()
    {
        $this->ugroup->shouldReceive('getMembers')->andReturns(array());
        $remote_server1 = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $remote_server2 = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $this->remote_server_factory->shouldReceive('getServersForProject')->andReturns(array($remote_server1, $remote_server2));

        $remote_server1->shouldReceive('getId')->andReturns(666);
        $remote_server2->shouldReceive('getId')->andReturns(667);

        $this->dao->shouldReceive('addReference')->with(1236, 25698, 666)->once();
        $this->dao->shouldReceive('addReference')->with(1236, 25698, 667)->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function testItLogsRemoteSSHErrors()
    {
        $this->remote_server_factory->shouldReceive('getServersForProject')->andReturns(array($this->remote_server));

        $this->driver->shouldReceive('createGroup')->andThrows(new Git_Driver_Gerrit_Exception('whatever'));

        $this->logger->shouldReceive('error')->with('whatever')->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function testItLogsGerritExceptions()
    {
        $this->remote_server_factory->shouldReceive('getServersForProject')->andReturns(array($this->remote_server));

        $this->driver->shouldReceive('createGroup')->andThrows(new Git_Driver_Gerrit_Exception('whatever'));

        $this->logger->shouldReceive('error')->with('whatever')->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function testItLogsAllOtherExceptions()
    {
        $this->remote_server_factory->shouldReceive('getServersForProject')->andReturns(array($this->remote_server));

        $this->driver->shouldReceive('createGroup')->andThrows(new Exception('whatever'));

        $this->logger->shouldReceive('error')->with('Unknown error: whatever')->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function testItContinuesToCreateGroupsEvenIfOneFails()
    {
        $this->ugroup->shouldReceive('getMembers')->andReturns(array());
        $remote_server1 = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $remote_server2 = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $remote_server2->shouldReceive('getId')->andReturns(667);
        $this->remote_server_factory->shouldReceive('getServersForProject')->andReturns(array($remote_server1, $remote_server2));

        $this->driver->shouldReceive('createGroup')->times(2);
        $this->driver->shouldReceive('createGroup')->andThrow(new Exception('whatever'))->ordered();
        $this->driver->shouldReceive('createGroup')->with($remote_server2, \Mockery::any(), \Mockery::any())->ordered();
        $this->dao->shouldReceive('addReference')->with(\Mockery::any(), \Mockery::any(), 667)->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function testItDoesntCreateGroupForSpecialNoneUGroup()
    {
        $this->driver->shouldReceive('createGroup')->never();

        $ugroup            = new ProjectUGroup(array('ugroup_id' => ProjectUGroup::NONE));
        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
        $this->assertEquals('', $gerrit_group_name);
    }

    public function testItDoesntCreateGroupForSpecialWikiAdminGroup()
    {
        $this->driver->shouldReceive('createGroup')->never();

        $ugroup            = new ProjectUGroup(array('ugroup_id' => ProjectUGroup::WIKI_ADMIN));
        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
        $this->assertEquals('', $gerrit_group_name);
    }

    public function testItCreatesGroupForSpecialProjectMembersGroup()
    {
        $this->driver->shouldReceive('createGroup')->once();

        $ugroup = \Mockery::spy(\ProjectUGroup::class);
        $ugroup->shouldReceive('getId')->andReturns(ProjectUGroup::PROJECT_MEMBERS);
        $ugroup->shouldReceive('getNormalizedName')->andReturns('project_members');
        $ugroup->shouldReceive('getProject')->andReturns($this->project);
        $ugroup->shouldReceive('getProjectId')->andReturns(999);
        $ugroup->shouldReceive('getMembers')->andReturns(array());

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }

    public function testItCreatesGroupForSpecialProjectAdminsGroup()
    {
        $this->driver->shouldReceive('createGroup')->once();

        $ugroup = \Mockery::spy(\ProjectUGroup::class);
        $ugroup->shouldReceive('getId')->andReturns(ProjectUGroup::PROJECT_ADMIN);
        $ugroup->shouldReceive('getNormalizedName')->andReturns('project_admin');
        $ugroup->shouldReceive('getProject')->andReturns($this->project);
        $ugroup->shouldReceive('getProjectId')->andReturns(999);
        $ugroup->shouldReceive('getMembers')->andReturns(array());

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }

    public function testItCreatesAnIncludedGroupWhenUGroupIsBinded()
    {
        $source_group = \Mockery::spy(\ProjectUGroup::class);

        $ugroup = \Mockery::spy(\ProjectUGroup::class);
        $ugroup->shouldReceive('getId')->andReturns(25698);
        $ugroup->shouldReceive('getNormalizedName')->andReturns('coders');
        $ugroup->shouldReceive('getProject')->andReturns($this->project);
        $ugroup->shouldReceive('getProjectId')->andReturns(999);
        $ugroup->shouldReceive('getSourceGroup')->andReturns($source_group);

        $this->driver->shouldReceive('createGroup')->with($this->remote_server, 'w3c/coders', 'w3c/project_admins')->once();
        $this->membership_manager->shouldReceive('addUGroupBinding')->with($ugroup, $source_group);

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }
}
