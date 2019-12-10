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

require_once __DIR__.'/../../../bootstrap.php';

/**
 * Fix for request #5031 - Fatal error when adding a group in an umbrella parent project
 * @see https://tuleap.net/plugins/tracker/?aid=5031
 */

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MembershipManagerCreateGroupForUmbrellaTest extends TuleapTestCase
{
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

    private $child_project;

    public function setUp()
    {
        parent::setUp();

        $this->driver              = mock('Git_Driver_Gerrit');
        $this->driver_factory      = stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns($this->driver);
        $this->remote_server       = mock('Git_RemoteServer_GerritServer');
        $this->gerrit_user_manager = mock('Git_Driver_Gerrit_UserAccountManager');
        $this->project_manager     = mock('ProjectManager');

        $this->ugroup_manager = mock('UGroupManager');

        $this->membership_manager = partial_mock(
            'Git_Driver_Gerrit_MembershipManager',
            array(
                'doesGroupExistOnServer'
            ),
            array(
                \Mockery::spy(Git_Driver_Gerrit_MembershipDao::class),
                $this->driver_factory,
                $this->gerrit_user_manager,
                mock('Git_RemoteServer_GerritServerFactory'),
                mock('Logger'),
                $this->ugroup_manager,
                $this->project_manager
            )
        );

        $project_id    = 1236;
        $this->project = mock('Project');
        stub($this->project)->getID()->returns($project_id);
        stub($this->project)->getUnixName()->returns('w3c');

        $this->ugroup = mock('ProjectUGroup');
        stub($this->ugroup)->getId()->returns(25698);
        stub($this->ugroup)->getNormalizedName()->returns('coders');
        stub($this->ugroup)->getProject()->returns($this->project);
        stub($this->ugroup)->getProjectId()->returns($project_id);

        $this->admin_ugroup = mock('ProjectUGroup');
        stub($this->admin_ugroup)->getId()->returns(ProjectUGroup::PROJECT_ADMIN);
        stub($this->admin_ugroup)->getNormalizedName()->returns('project_admins');
        stub($this->admin_ugroup)->getProject()->returns($this->project);
        stub($this->admin_ugroup)->getProjectId()->returns($project_id);
        stub($this->admin_ugroup)->getMembers()->returns(array());

        stub($this->ugroup_manager)->getUGroup()->returns($this->admin_ugroup);

        $this->remote_server_factory  = mock('Git_RemoteServer_GerritServerFactory');
        $this->git_repository_factory = mock('GitRepositoryFactory');
        $this->logger = mock('Logger');
        $this->dao    = \Mockery::spy(Git_Driver_Gerrit_MembershipDao::class);
        $this->user1 = mock('PFUser');
        $this->user2 = mock('PFUser');

        $this->membership_manager = partial_mock(
            'Git_Driver_Gerrit_MembershipManager',
            array(
                'addUGroupBinding',
                'addUserToGroupWithoutFlush',
                'doesGroupExistOnServer'
            ),
            array(
                $this->dao,
                $this->driver_factory,
                $this->gerrit_user_manager,
                $this->remote_server_factory,
                $this->logger,
                $this->ugroup_manager,
                $this->project_manager
            )
        );

        stub($this->membership_manager)->doesGroupExistOnServer()->returns(true);

        $child_project = aMockProject()->withId(112)->build();

        stub($this->project_manager)->getChildProjects()->returns(array($child_project));
    }

    public function itCreateGroupOnAllGerritServersTheProjectAndItsChildrenUse()
    {
        stub($this->ugroup)->getMembers()->returns(array());
        expect($this->remote_server_factory)->getServersForProject($this->project)->count(2);
        stub($this->remote_server_factory)->getServersForProject()->returnsAt(0, array(aGerritServer()->withId(3)->build()));
        stub($this->remote_server_factory)->getServersForProject()->returnsAt(1, array(aGerritServer()->withId(5)->build()));
        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }
}
