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

interface Git_Driver_Gerrit_manageGroupsTest {
    public function itCreatesGroupsIfItNotExistsOnGerrit();
    public function itDoesNotCreateGroupIfItAlreadyExistsOnGerrit();
    public function itInformsAboutGroupCreation();
    public function itRaisesAGerritDriverExceptionOnGroupsCreation();
    public function itCreatesGroupWithoutOwnerWhenSelfOwnedToAvoidChickenEggIssue();
    public function itAsksGerritForTheGroupUUID();
    public function itReturnsNullUUIDIfNotFound();
    public function itAsksGerritForTheGroupId();
    public function itReturnsNullIdIfNotFound();
    public function itReturnsAllGroups();
}

class Git_Driver_GerritLegacy_manageGroupsTest extends Git_Driver_GerritLegacy_baseTest implements Git_Driver_Gerrit_manageGroupsTest {
    private $groupname = 'project/repo-contributors';
    private $expected_query = 'gerrit gsql --format json -c "SELECT\ *\ FROM\ account_groups\ WHERE\ name=\\\'project/repo-contributors\\\'"';
    /** @var Git_Driver_GerritLegacy */
    private $gerrit_driver;

    public function setUp() {
        parent::setUp();
        $this->gerrit_driver = partial_mock(
            'Git_Driver_GerritLegacy',
            array('doesTheGroupExist'),
            array($this->ssh, $this->logger)
        );
    }

    public function itCreatesGroupsIfItNotExistsOnGerrit() {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(false);

        $create_group_command = "gerrit create-group firefox/project_members --owner firefox/project_admins";
        expect($this->ssh)->execute($this->gerrit_server, $create_group_command)->once();
        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }

    public function itDoesNotCreateGroupIfItAlreadyExistsOnGerrit() {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(true);

        $create_group_command = "gerrit create-group firefox/project_members --owner firefox/project_admins";
        expect($this->ssh)->execute($this->gerrit_server, $create_group_command)->never();
        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }

    public function itInformsAboutGroupCreation() {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(false);

        $user_list    = array ();
        expect($this->logger)->info("Gerrit: Group firefox/project_members successfully created")->once();
        $this->gerrit_driver->createGroup($this->gerrit_server,  'firefox/project_members', $user_list);
    }

    public function itRaisesAGerritDriverExceptionOnGroupsCreation(){
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(false);

        $std_err = 'fatal: group "somegroup" already exists';
        $command = "gerrit create-group firefox/project_members --owner firefox/project_admins";

        stub($this->ssh)->execute()->throws(new Git_Driver_Gerrit_RemoteSSHCommandFailure(Git_Driver_GerritLegacy::EXIT_CODE, '', $std_err));

        try {
            $this->gerrit_driver->createGroup($this->gerrit_server,  'firefox/project_members', 'firefox/project_admins');
            $this->fail('An exception was expected');
        } catch (Git_Driver_Gerrit_Exception $e) {
            $this->assertEqual($e->getMessage(), "Command: $command" . PHP_EOL . "Error: $std_err");
        }
    }

    public function itCreatesGroupWithoutOwnerWhenSelfOwnedToAvoidChickenEggIssue() {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(false);

        $create_group_command = "gerrit create-group firefox/project_admins";
        expect($this->ssh)->execute($this->gerrit_server, $create_group_command)->once();
        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_admins', 'firefox/project_admins');
    }

    public function itAsksGerritForTheGroupUUID() {
        $uuid         = 'lsalkj4jlk2jj3452lkj23kj421465';
        $query_result = '{"type":"row","columns":{"group_uuid":"'. $uuid .'"}}'.
                        PHP_EOL .
                        '{"type":"query-stats","rowCount":1,"runTimeMilliseconds":1}';
        stub($this->ssh)->execute($this->gerrit_server, $this->expected_query)->once()->returns($query_result);

        $this->assertEqual($uuid, $this->driver->getGroupUUID($this->gerrit_server, $this->groupname));
    }

    public function itReturnsNullUUIDIfNotFound() {
        $query_result = '{"type":"query-stats","rowCount":0,"runTimeMilliseconds":0}';
        stub($this->ssh)->execute($this->gerrit_server, $this->expected_query)->once()->returns($query_result);

        $this->assertNull($this->driver->getGroupUUID($this->gerrit_server, $this->groupname));
    }

    public function itAsksGerritForTheGroupId() {
        $id         = '272';
        $query_result = '{"type":"row","columns":{"group_id":"'. $id .'"}}'.
                        PHP_EOL .
                        '{"type":"query-stats","rowCount":1,"runTimeMilliseconds":1}';
        stub($this->ssh)->execute($this->gerrit_server, $this->expected_query)->once()->returns($query_result);

        $this->assertEqual($id, $this->driver->getGroupId($this->gerrit_server, $this->groupname));
    }

    public function itReturnsNullIdIfNotFound() {
        $query_result = '{"type":"query-stats","rowCount":0,"runTimeMilliseconds":0}';
        stub($this->ssh)->execute($this->gerrit_server, $this->expected_query)->once()->returns($query_result);

        $this->assertNull($this->driver->getGroupID($this->gerrit_server, $this->groupname));
    }

    public function itReturnsAllGroups() {
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

class Git_DriverREST_Gerrit_manageGroupsTest extends Git_Driver_GerritREST_baseTest implements Git_Driver_Gerrit_manageGroupsTest {

    public function setUp() {
        parent::setUp();
        $this->gerrit_driver = partial_mock(
            'Git_Driver_GerritREST',
            array('doesTheGroupExist'),
            array($this->http_client, $this->logger, $this->body_builder)
        );
    }

    public function itCreatesGroupsIfItNotExistsOnGerrit(){
        stub($this->gerrit_driver)->doesTheGroupExist()->returns(false);

        $url_create_group = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode('firefox/project_members');

        $expected_json_data = json_encode(
            array(
                'owner' => 'firefox/project_admins'
            )
        );

        $expected_options_create_group = array(
            CURLOPT_URL             => $url_create_group,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_PUT             => true,
            CURLOPT_HTTPHEADER      => array(Git_Driver_GerritREST::CONTENT_TYPE_JSON),
            CURLOPT_INFILE          => $this->temporary_file_for_body,
            CURLOPT_INFILESIZE      => strlen($expected_json_data)
        );

        expect($this->body_builder)->getTemporaryFile($expected_json_data)->once();
        expect($this->http_client)->doRequest()->once();
        expect($this->http_client)->addOptions($expected_options_create_group)->once();

        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }

    public function itDoesNotCreateGroupIfItAlreadyExistsOnGerrit(){
        stub($this->gerrit_driver)->doesTheGroupExist()->returns(true);

        expect($this->http_client)->doRequest()->never();
        expect($this->http_client)->addOptions()->never();

        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }
    public function itInformsAboutGroupCreation(){
        stub($this->gerrit_driver)->doesTheGroupExist()->returns(false);

        expect($this->logger)->info()->count(2);
        expect($this->logger)->info("Gerrit REST driver: Create group firefox/project_members")->at(0);
        expect($this->logger)->info("Gerrit REST driver: Group firefox/project_members successfully created")->at(1);

        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }
    public function itRaisesAGerritDriverExceptionOnGroupsCreation(){}

    public function itCreatesGroupWithoutOwnerWhenSelfOwnedToAvoidChickenEggIssue(){
        stub($this->gerrit_driver)->doesTheGroupExist()->returns(false);

        $url_create_group = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode('firefox/project_admins');

        $expected_options_create_group = array(
            CURLOPT_URL             => $url_create_group,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_PUT             => true,
        );

        expect($this->http_client)->doRequest()->once();
        expect($this->http_client)->addOptions($expected_options_create_group)->once();

        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_admins', 'firefox/project_admins');
    }

    public function itAsksGerritForTheGroupUUID(){
        $get_group_response = <<<EOS
)]}'
{
  "kind": "gerritcodereview#group",
  "url": "#/admin/groups/uuid-a1e6742f55dc890205b9db147826964d12c9a775",
  "options": {},
  "group_id": 8,
  "owner": "enalean",
  "owner_id": "a1e6742f55dc890205b9db147826964d12c9a775",
  "id": "a1e6742f55dc890205b9db147826964d12c9a775",
  "name": "enalean"
}
EOS;

        stub($this->http_client)->getLastResponse()->returns($get_group_response);

        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/enalean';

        $expected_options = array(
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_CUSTOMREQUEST   => 'GET'
        );

        expect($this->http_client)->doRequest()->once();
        expect($this->http_client)->addOptions($expected_options)->once();

        $this->assertEqual(
            $this->driver->getGroupUUID($this->gerrit_server, 'enalean'),
            'a1e6742f55dc890205b9db147826964d12c9a775'
        );
    }

    public function itAsksGerritForTheGroupId(){
        $get_group_response = <<<EOS
)]}'
{
  "kind": "gerritcodereview#group",
  "url": "#/admin/groups/uuid-a1e6742f55dc890205b9db147826964d12c9a775",
  "options": {},
  "group_id": 8,
  "owner": "enalean",
  "owner_id": "a1e6742f55dc890205b9db147826964d12c9a775",
  "id": "a1e6742f55dc890205b9db147826964d12c9a775",
  "name": "enalean"
}
EOS;

        stub($this->http_client)->getLastResponse()->returns($get_group_response);

        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/enalean';

        $expected_options = array(
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_CUSTOMREQUEST   => 'GET'
        );

        expect($this->http_client)->doRequest()->once();
        expect($this->http_client)->addOptions($expected_options)->once();

        $this->assertEqual(
            $this->driver->getGroupId($this->gerrit_server, 'enalean'),
            8
        );
    }

    public function itReturnsNullIdIfNotFound() {
        $get_group_response = null;

        stub($this->http_client)->getLastResponse()->returns($get_group_response);

        $this->assertNull($this->driver->getGroupId($this->gerrit_server, 'enalean'));
    }

    public function itReturnsNullUUIDIfNotFound() {
        $get_group_response = null;

        stub($this->http_client)->getLastResponse()->returns($get_group_response);

        $this->assertNull($this->driver->getGroupUUID($this->gerrit_server, 'enalean'));
    }

    public function itReturnsAllGroups() {
        $raiponce = <<<EOS
)]}'
{
  "enalean": {
    "kind": "gerritcodereview#group",
    "url": "#/admin/groups/uuid-6ef56904c11e6d53c8f2f3657353faaac74bfc6d",
    "options": {},
    "group_id": 7,
    "owner": "enalean",
    "owner_id": "6ef56904c11e6d53c8f2f3657353faaac74bfc6d",
    "id": "6ef56904c11e6d53c8f2f3657353faaac74bfc6d"
  },
  "grp": {
    "kind": "gerritcodereview#group",
    "url": "#/admin/groups/uuid-b99e4455ca98f2ec23d9250f69617e34ceae6bd6",
    "options": {},
    "group_id": 6,
    "owner": "grp",
    "owner_id": "b99e4455ca98f2ec23d9250f69617e34ceae6bd6",
    "id": "b99e4455ca98f2ec23d9250f69617e34ceae6bd6"
  }
}
EOS;
        stub($this->http_client)->getLastResponse()->returns($raiponce);

        $expected_result = array(
            "enalean" => "6ef56904c11e6d53c8f2f3657353faaac74bfc6d",
            "grp"     => "b99e4455ca98f2ec23d9250f69617e34ceae6bd6",
        );

        $this->assertEqual($this->driver->getAllGroups($this->gerrit_server), $expected_result);
    }
}