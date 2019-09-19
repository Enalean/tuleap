<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once dirname(__FILE__).'/GerritTestBase.php';

class Git_Driver_GerritLegacy_manageGroupsTest extends Git_Driver_GerritLegacy_baseTest implements Git_Driver_Gerrit_manageGroupsTest
{
    private $groupname = 'project/repo-contributors';
    private $expected_query = 'gerrit gsql --format json -c "SELECT\ *\ FROM\ account_groups\ WHERE\ name=\\\'project/repo-contributors\\\'"';
    /** @var Git_Driver_GerritLegacy */
    private $gerrit_driver;

    public function setUp()
    {
        parent::setUp();
        $this->gerrit_driver = partial_mock(
            'Git_Driver_GerritLegacy',
            array('doesTheGroupExist'),
            array($this->ssh, $this->logger)
        );
    }

    public function itCreatesGroupsIfItNotExistsOnGerrit()
    {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(false);

        $create_group_command = "gerrit create-group firefox/project_members --owner firefox/project_admins";
        expect($this->ssh)->execute($this->gerrit_server, $create_group_command)->once();
        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }

    public function itDoesNotCreateGroupIfItAlreadyExistsOnGerrit()
    {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(true);

        $create_group_command = "gerrit create-group firefox/project_members --owner firefox/project_admins";
        expect($this->ssh)->execute($this->gerrit_server, $create_group_command)->never();
        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }

    public function itInformsAboutGroupCreation()
    {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(false);

        expect($this->logger)->info("Gerrit: Group firefox/project_members successfully created")->once();
        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }

    public function itRaisesAGerritDriverExceptionOnGroupsCreation()
    {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(false);

        $std_err = 'fatal: group "somegroup" already exists';
        $command = "gerrit create-group firefox/project_members --owner firefox/project_admins";

        stub($this->ssh)->execute()->throws(new Git_Driver_Gerrit_RemoteSSHCommandFailure(Git_Driver_GerritLegacy::EXIT_CODE, '', $std_err));

        try {
            $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
            $this->fail('An exception was expected');
        } catch (Git_Driver_Gerrit_Exception $e) {
            $this->assertEqual($e->getMessage(), "Command: $command" . PHP_EOL . "Error: $std_err");
        }
    }

    public function itCreatesGroupWithoutOwnerWhenSelfOwnedToAvoidChickenEggIssue()
    {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(false);

        $create_group_command = "gerrit create-group firefox/project_admins";
        expect($this->ssh)->execute($this->gerrit_server, $create_group_command)->once();
        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_admins', 'firefox/project_admins');
    }

    public function itAsksGerritForTheGroupUUID()
    {
        $uuid         = 'lsalkj4jlk2jj3452lkj23kj421465';
        $query_result = '{"type":"row","columns":{"group_uuid":"'. $uuid .'"}}'.
                        PHP_EOL .
                        '{"type":"query-stats","rowCount":1,"runTimeMilliseconds":1}';
        stub($this->ssh)->execute($this->gerrit_server, $this->expected_query)->once()->returns($query_result);

        $this->assertEqual($uuid, $this->driver->getGroupUUID($this->gerrit_server, $this->groupname));
    }

    public function itReturnsNullUUIDIfNotFound()
    {
        $query_result = '{"type":"query-stats","rowCount":0,"runTimeMilliseconds":0}';
        stub($this->ssh)->execute($this->gerrit_server, $this->expected_query)->once()->returns($query_result);

        $this->assertNull($this->driver->getGroupUUID($this->gerrit_server, $this->groupname));
    }

    public function itAsksGerritForTheGroupId()
    {
        $id         = '272';
        $query_result = '{"type":"row","columns":{"group_id":"'. $id .'"}}'.
                        PHP_EOL .
                        '{"type":"query-stats","rowCount":1,"runTimeMilliseconds":1}';
        stub($this->ssh)->execute($this->gerrit_server, $this->expected_query)->once()->returns($query_result);

        $this->assertEqual($id, $this->driver->getGroupId($this->gerrit_server, $this->groupname));
    }

    public function itReturnsNullIdIfNotFound()
    {
        $query_result = '{"type":"query-stats","rowCount":0,"runTimeMilliseconds":0}';
        stub($this->ssh)->execute($this->gerrit_server, $this->expected_query)->once()->returns($query_result);

        $this->assertNull($this->driver->getGroupID($this->gerrit_server, $this->groupname));
    }

    public function itReturnsAllGroups()
    {
        $ls_groups_expected_return = <<<EOS
Administrators	31c2cb467c263d73eb24552a7cc98b7131ac2115	Gerrit Site Administrators	INTERNAL	Administrators	31c2cb467c263d73eb24552a7cc98b7131ac2115	false
Anonymous Users	global:Anonymous-Users	Any user, signed-in or not	SYSTEM	Administrators	31c2cb467c263d73eb24552a7cc98b7131ac2115	false
someProject/group_from_ldap	ec68131cc1adc6b42753c10adb3e3265493f64f9		INTERNAL	chicken-egg/LDAP_Others	ec68131cc1adc6b42753c10adb3e3265493f64f9	false
EOS;
        $expected_query = 'gerrit ls-groups --verbose';
        stub($this->ssh)->execute($this->gerrit_server, $expected_query)->once()->returns($ls_groups_expected_return);

        $expected_result = array(
            'Administrators'              => '31c2cb467c263d73eb24552a7cc98b7131ac2115',
            'Anonymous Users'             => 'global:Anonymous-Users',
            'someProject/group_from_ldap' => 'ec68131cc1adc6b42753c10adb3e3265493f64f9',
        );
        $this->assertEqual($this->driver->getAllGroups($this->gerrit_server), $expected_result);
    }
}
