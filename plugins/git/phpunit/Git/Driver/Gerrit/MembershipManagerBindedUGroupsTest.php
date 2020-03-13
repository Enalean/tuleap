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
class MembershipManagerBindedUGroupsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ProjectManager */
    protected $project_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->remote_server_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->remote_server         = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $this->gerrit_user_manager   = \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class);
        $this->project_manager       = \Mockery::spy(\ProjectManager::class);

        $this->remote_server_factory->shouldReceive('getServersForUGroup')->andReturns(array($this->remote_server));
        $this->project_manager->shouldReceive('getChildProjects')->andReturns(array());

        $this->driver         = \Mockery::spy(\Git_Driver_Gerrit::class);
        $this->driver_factory = \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class)->shouldReceive('getDriver')->andReturns($this->driver)->getMock();

        $this->membership_manager = \Mockery::mock(
            \Git_Driver_Gerrit_MembershipManager::class,
            [
                Mockery::mock(Git_Driver_Gerrit_MembershipDao::class),
                $this->driver_factory,
                $this->gerrit_user_manager,
                $this->remote_server_factory,
                Mockery::mock(\Psr\Log\LoggerInterface::class),
                Mockery::mock('UGroupManager'),
                $this->project_manager
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $project = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('mozilla')->getMock();
        $this->ugroup = new ProjectUGroup(array('ugroup_id' => 112, 'name' => 'developers'));
        $this->ugroup->setProject($project);
        $this->ugroup->setSourceGroup(null);
        $this->source = new ProjectUGroup(array('ugroup_id' => 124, 'name' => 'coders'));
        $this->source->setProject($project);
    }

    public function testItAddBindingToAGroup(): void
    {
        $gerrit_ugroup_name = 'mozilla/developers';
        $gerrit_source_name = 'mozilla/coders';
        $this->driver->shouldReceive('addIncludedGroup')->with($this->remote_server, $gerrit_ugroup_name, $gerrit_source_name)->once();

        $this->membership_manager->shouldReceive('createGroupForServer')
            ->with($this->remote_server, $this->source)->once()
            ->andReturns('mozilla/coders');

        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function testItEmptyTheMemberListOnBindingAdd(): void
    {
        $this->membership_manager->shouldReceive('createGroupForServer')->andReturns('mozilla/coders');

        $this->driver->shouldReceive('removeAllGroupMembers')->with($this->remote_server, 'mozilla/developers')->once();

        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function testItReplaceBindingFromAGroupToAnother(): void
    {
        $this->membership_manager->shouldReceive('createGroupForServer');

        $this->ugroup->setSourceGroup($this->source);

        $this->driver->shouldReceive('removeAllIncludedGroups')->with($this->remote_server, 'mozilla/developers')->once();

        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function testItReliesOnCreateGroupForSourceGroupCreation(): void
    {
        $this->membership_manager->shouldReceive('createGroupForServer')->with($this->remote_server, $this->source)->once();
        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function testItRemovesBindingWithAGroup(): void
    {
        $project = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('mozilla')->getMock();
        $ugroup = new ProjectUGroup(array('ugroup_id' => 112, 'name' => 'developers'));
        $ugroup->setProject($project);
        $ugroup->setSourceGroup(null);

        $gerrit_ugroup_name = 'mozilla/developers';
        $this->driver->shouldReceive('removeAllIncludedGroups')->with($this->remote_server, $gerrit_ugroup_name)->once();

        $this->membership_manager->removeUGroupBinding($ugroup);
    }

    public function testItAddsMembersOfPreviousSourceAsHardCodedMembersOnRemove(): void
    {
        $user = new PFUser([
            'language_id' => 'en',
            'ldap_id' => 'blabla'
        ]);
        $gerrit_user = \Mockery::spy(\Git_Driver_Gerrit_User::class);
        $this->gerrit_user_manager->shouldReceive('getGerritUser')->with($user)->andReturns($gerrit_user);

        $source_ugroup = \Mockery::spy(\ProjectUGroup::class);
        $source_ugroup->shouldReceive('getMembers')->andReturns(array($user));

        $project = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('mozilla')->getMock();
        $ugroup = new ProjectUGroup(array('ugroup_id' => 112, 'name' => 'developers'));
        $ugroup->setProject($project);
        $ugroup->setSourceGroup($source_ugroup);

        $this->driver->shouldReceive('addUserToGroup')->with($this->remote_server, $gerrit_user, 'mozilla/developers')->once();

        $this->membership_manager->removeUGroupBinding($ugroup);
    }
}
