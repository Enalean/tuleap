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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MembershipManagerProjectAdminOwnerOfEverythingTest extends TuleapTestCase
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

        stub($this->project_manager)->getChildProjects()->returns(array());
    }

    public function itCheckIfProjectAdminsGroupExist()
    {
        stub($this->ugroup)->getMembers()->returns(array());

        expect($this->membership_manager)->doesGroupExistOnServer($this->remote_server, $this->admin_ugroup)->once();

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function itCreatesTheProjectAdminGroupWhenNoExist()
    {
        stub($this->ugroup)->getMembers()->returns(array());

        stub($this->membership_manager)->doesGroupExistOnServer()->returns(false);
        expect($this->driver)->createGroup()->count(2);
        expect($this->driver)->createGroup($this->remote_server, 'w3c/project_admins', 'w3c/project_admins')->at(0);
        expect($this->driver)->createGroup($this->remote_server, 'w3c/coders', 'w3c/project_admins')->at(1);

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }
}
