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
class MembershipManagerBindedUGroupsTest extends TuleapTestCase
{

    /** @var ProjectManager */
    protected $project_manager;

    public function setUp()
    {
        parent::setUp();

        $this->remote_server_factory = mock('Git_RemoteServer_GerritServerFactory');
        $this->remote_server         = mock('Git_RemoteServer_GerritServer');
        $this->gerrit_user_manager   = mock('Git_Driver_Gerrit_UserAccountManager');
        $this->project_manager       = mock('ProjectManager');

        stub($this->remote_server_factory)->getServersForUGroup()->returns(array($this->remote_server));
        stub($this->project_manager)->getChildProjects()->returns(array());

        $this->driver         = mock('Git_Driver_Gerrit');
        $this->driver_factory = stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns($this->driver);

        $this->membership_manager = partial_mock(
            'Git_Driver_Gerrit_MembershipManager',
            array('createGroupForServer'),
            array(
                safe_mock(Git_Driver_Gerrit_MembershipDao::class),
                $this->driver_factory,
                $this->gerrit_user_manager,
                $this->remote_server_factory,
                mock('Logger'),
                mock('UGroupManager'),
                $this->project_manager
            )
        );

        $project = stub('Project')->getUnixName()->returns('mozilla');
        $this->ugroup = new ProjectUGroup(array('ugroup_id' => 112, 'name' => 'developers'));
        $this->ugroup->setProject($project);
        $this->ugroup->setSourceGroup(null);
        $this->source = new ProjectUGroup(array('ugroup_id' => 124, 'name' => 'coders'));
        $this->source->setProject($project);
    }

    public function itAddBindingToAGroup()
    {
        $gerrit_ugroup_name = 'mozilla/developers';
        $gerrit_source_name = 'mozilla/coders';
        expect($this->driver)->addIncludedGroup($this->remote_server, $gerrit_ugroup_name, $gerrit_source_name)->once();

        expect($this->membership_manager)->createGroupForServer($this->remote_server, $this->source)->once();
        stub($this->membership_manager)->createGroupForServer()->returns('mozilla/coders');

        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function itEmptyTheMemberListOnBindingAdd()
    {
        stub($this->membership_manager)->createGroupForServer()->returns('mozilla/coders');

        expect($this->driver)->removeAllGroupMembers($this->remote_server, 'mozilla/developers')->once();

        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function itReplaceBindingFromAGroupToAnother()
    {
        $this->ugroup->setSourceGroup($this->source);

        expect($this->driver)->removeAllIncludedGroups($this->remote_server, 'mozilla/developers')->once();

        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function itReliesOnCreateGroupForSourceGroupCreation()
    {
        expect($this->membership_manager)->createGroupForServer($this->remote_server, $this->source)->once();
        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function itRemovesBindingWithAGroup()
    {
        $project = stub('Project')->getUnixName()->returns('mozilla');
        $ugroup = new ProjectUGroup(array('ugroup_id' => 112, 'name' => 'developers'));
        $ugroup->setProject($project);
        $ugroup->setSourceGroup(null);

        $gerrit_ugroup_name = 'mozilla/developers';
        expect($this->driver)->removeAllIncludedGroups($this->remote_server, $gerrit_ugroup_name)->once();

        $this->membership_manager->removeUGroupBinding($ugroup);
    }

    public function itAddsMembersOfPreviousSourceAsHardCodedMembersOnRemove()
    {
        $user = aUser()->withLdapId('blabla')->build();
        $gerrit_user = mock('Git_Driver_Gerrit_User');
        stub($this->gerrit_user_manager)->getGerritUser($user)->returns($gerrit_user);

        $source_ugroup = mock('ProjectUGroup');
        stub($source_ugroup)->getMembers()->returns(array($user));

        $project = stub('Project')->getUnixName()->returns('mozilla');
        $ugroup = new ProjectUGroup(array('ugroup_id' => 112, 'name' => 'developers'));
        $ugroup->setProject($project);
        $ugroup->setSourceGroup($source_ugroup);

        expect($this->driver)->addUserToGroup($this->remote_server, $gerrit_user, 'mozilla/developers')->once();

        $this->membership_manager->removeUGroupBinding($ugroup);
    }
}
