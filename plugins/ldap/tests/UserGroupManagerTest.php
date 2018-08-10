<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\LDAP;

require_once 'bootstrap.php';

use TuleapTestCase;
use LDAP_UserGroupManager;
use LDAP_GroupManager;

class UserGroupManagerTest extends TuleapTestCase
{

    /**
     * @var LDAP_UserGroupManager
     */
    private $manager;

    public function setUp()
    {
        parent::setUp();

        $ldap                    = mock('LDAP');
        $this->ldap_user_manager = mock('LDAP_UserManager');
        $this->ldap_user_dao     = mock('LDAP_UserGroupDao');
        $this->project_manager   = mock('ProjectManager');
        $logger                  = mock('Logger');

        $this->manager = new LDAP_UserGroupManager(
            $ldap,
            $this->ldap_user_manager,
            $this->ldap_user_dao,
            $this->project_manager,
            $logger,
            new \Tuleap\LDAP\GroupSyncSilentNotificationsManager()
        );

        $this->manager->setProjectId(101);
        $this->manager->setGroupDn('whatever');

        $this->project = mock('Project');
        stub($this->project_manager)->getProject(101)->returns($this->project);

        $this->bind_option     = LDAP_GroupManager::BIND_OPTION;
        $this->preserve_option = LDAP_GroupManager::PRESERVE_MEMBERS_OPTION;
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function itRemovesNonProjectMembersFromUserToAddInPrivateProject()
    {
        stub($this->ldap_user_manager)->getUserIdsForLdapUser()->returns(array(
            101 => 101,
            102 => 102
        ));

        stub($this->ldap_user_dao)->getMembersId()->returns(
            array('101' => '101')
        );

        stub($this->project)->isPublic()->returns(false);
        stub($this->project)->getMembersId()->returns(array(101));

        $users_to_be_added = $this->manager->getUsersToBeAdded($this->bind_option);

        $this->assertArrayEmpty($users_to_be_added);
    }

    public function itDoesNotRemoveNonProjectMembersFromUserToAddInPublicProject()
    {
        stub($this->ldap_user_manager)->getUserIdsForLdapUser()->returns(array(
            101 => 101,
            102 => 102
        ));

        stub($this->ldap_user_dao)->getMembersId()->returns(
            array('101' => '101')
        );

        stub($this->project)->isPublic()->returns(true);
        stub($this->project)->getMembersId()->returns(array(101));

        $users_to_be_added = $this->manager->getUsersToBeAdded($this->bind_option);
        $expected_result   = array(102 => 102);

        $this->assertEqual($expected_result, $users_to_be_added);
    }

    public function itAddsNonProjectMembersIntoUserToRemoveInPrivateProject()
    {
        stub($this->ldap_user_manager)->getUserIdsForLdapUser()->returns(array(
            101 => 101,
            102 => 102
        ));

        stub($this->ldap_user_dao)->getMembersId()->returns(array(
            '101' => '101',
            '102' => '102'
        ));

        stub($this->project)->isPublic()->returns(false);
        stub($this->project)->getMembersId()->returns(array(101));

        $users_to_be_removed = $this->manager->getUsersToBeRemoved($this->bind_option);
        $expected_result     = array(102 => 102);

        $this->assertEqual($expected_result, $users_to_be_removed);
    }

    public function itDoesNotAddNonProjectMembersIntoUserToRemoveInPublicProject()
    {
        stub($this->ldap_user_manager)->getUserIdsForLdapUser()->returns(array(
            101 => 101,
            102 => 102
        ));

        stub($this->ldap_user_dao)->getMembersId()->returns(array(
            '101' => '101',
        ));

        stub($this->project)->isPublic()->returns(true);
        stub($this->project)->getMembersId()->returns(array(101));

        $users_to_be_removed = $this->manager->getUsersToBeRemoved($this->bind_option);

        $this->assertArrayEmpty($users_to_be_removed);
    }

    public function itAddsNonProjectMembersIntoUserToRemoveInPrivateProjectEvenWithPreserveMembers()
    {
        stub($this->ldap_user_manager)->getUserIdsForLdapUser()->returns(array(
            101 => 101,
            102 => 102
        ));

        stub($this->ldap_user_dao)->getMembersId()->returns(array(
            '101' => '101',
            '102' => '102'
        ));

        stub($this->project)->isPublic()->returns(false);
        stub($this->project)->getMembersId()->returns(array(101));

        $users_to_be_removed = $this->manager->getUsersToBeRemoved($this->preserve_option);
        $expected_result     = array(102 => 102);

        $this->assertEqual($expected_result, $users_to_be_removed);
    }
}
