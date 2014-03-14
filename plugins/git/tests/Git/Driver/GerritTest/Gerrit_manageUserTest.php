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

interface Git_Driver_Gerrit_manageUserTest {
    public function itInitializeUserAccountInGerritWhenUserNeverLoggedToGerritUI();
    public function itExecutesTheInsertCommand();
    public function itExecutesTheDeletionCommand();
    public function itRemovesAllMembers();
}

class Git_Driver_GerritLegacy_manageUserTest extends Git_Driver_GerritLegacy_baseTest implements Git_Driver_Gerrit_manageUserTest {
    private $groupname;
    private $ldap_uid;
    private $user;
    private $account_id;
    private $group_id;

    public function setUp() {
        parent::setUp();
        $this->group                       = 'contributors';
        $this->groupname                   = $this->project_name.'/'.$this->namespace.'/'.$this->repository_name.'-'.$this->group;
        $this->ldap_uid                    = 'someuser';
        $this->user                        = new Git_Driver_Gerrit_User(stub('LDAP_User')->getUid()->returns($this->ldap_uid));

        $this->insert_member_query = 'gerrit gsql --format json -c "INSERT\ INTO\ account_group_members\ (account_id,\ group_id)\ SELECT\ A.account_id,\ G.group_id\ FROM\ account_external_ids\ A,\ account_groups\ G\ WHERE\ A.external_id=\\\'username:'. $this->ldap_uid .'\\\'\ AND\ G.name=\\\''. $this->groupname .'\\\'"';

        $this->set_account_query   = 'gerrit set-account '.$this->ldap_uid;
    }

    public function itExecutesTheDeletionCommand() {
        $remove_member_query = 'gerrit gsql --format json -c "DELETE\ FROM\ account_group_members\ WHERE\ account_id=(SELECT\ account_id\ FROM\ account_external_ids\ WHERE\ external_id=\\\'username:'. $this->ldap_uid .'\\\')\ AND\ group_id=(SELECT\ group_id\ FROM\ account_groups\ WHERE\ name=\\\''. $this->groupname .'\\\')"';

        expect($this->ssh)->execute($this->gerrit_server, $remove_member_query)->once();

        $this->driver->removeUserFromGroup($this->gerrit_server, $this->user, $this->groupname);
    }

    public function itRemovesAllMembers() {
        $remove_all_query = 'gerrit gsql --format json -c "DELETE\ FROM\ account_group_members\ WHERE\ group_id=(SELECT\ group_id\ FROM\ account_groups\ WHERE\ name=\\\''. $this->groupname .'\\\')"';
        expect($this->ssh)->execute()->count(2);
        expect($this->ssh)->execute($this->gerrit_server, $remove_all_query)->at(0);
        expect($this->ssh)->execute($this->gerrit_server, 'gerrit flush-caches --cache accounts')->at(1);

        $this->driver->removeAllGroupMembers($this->gerrit_server, $this->groupname);
    }
    public function itInitializeUserAccountInGerritWhenUserNeverLoggedToGerritUI() {
        expect($this->ssh)->execute($this->gerrit_server, $this->set_account_query)->at(0);

        $this->driver->addUserToGroup($this->gerrit_server, $this->user, $this->groupname);
    }

    public function itExecutesTheInsertCommand() {
        expect($this->ssh)->execute()->count(2);
        expect($this->ssh)->execute($this->gerrit_server, $this->insert_member_query)->at(1);

        $this->driver->addUserToGroup($this->gerrit_server, $this->user, $this->groupname);
    }
}

class Git_DriverREST_Gerrit_manageUserTest extends Git_Driver_GerritREST_baseTest implements Git_Driver_Gerrit_manageUserTest {
    private $username = 'someuser';

    public function setUp() {
        parent::setUp();

        $this->user      = mock('Git_Driver_Gerrit_User');
        $this->group     = 'contributors';
        $this->groupname = $this->project_name.'/'.$this->namespace.'/'.$this->repository_name.'-'.$this->group;

        stub($this->user)->getRealName()->returns('John Doe');
        stub($this->user)->getEmail()->returns('jdoe@example.com');
        stub($this->user)->getSSHUserName()->returns($this->username);

        $url_get_user = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/accounts/'. $this->username;

        $this->expected_options_get_user = array(
            CURLOPT_URL             => $url_get_user,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_CUSTOMREQUEST   => 'GET',
        );
    }

    public function itInitializeUserAccountInGerritWhenUserNeverLoggedToGerritUI(){
        stub($this->http_client)->isLastResponseSuccess()->returns(false);

        $url_create_account = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/accounts/'. $this->username;

        $expected_json_data = json_encode(
            array(
                'name'   => "John Doe",
                'email'  => "jdoe@example.com",
                'groups' => array($this->groupname)
            )
        );

        $expected_options_create_account = array(
            CURLOPT_URL             => $url_create_account,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_PUT             => true,
            CURLOPT_HTTPHEADER      => array(Git_Driver_GerritREST::CONTENT_TYPE_JSON),
            CURLOPT_INFILE          => $this->temporary_file_for_body,
            CURLOPT_INFILESIZE      => strlen($expected_json_data)
        );

        expect($this->body_builder)->getTemporaryFile($expected_json_data)->once();
        expect($this->http_client)->doRequest()->count(2);
        expect($this->http_client)->addOptions()->count(2);
        expect($this->http_client)->addOptions($this->expected_options_get_user)->at(0);
        expect($this->http_client)->addOptions($expected_options_create_account)->at(1);

        $this->driver->addUserToGroup($this->gerrit_server, $this->user, $this->groupname);
    }

    public function itExecutesTheInsertCommand(){
        stub($this->http_client)->isLastResponseSuccess()->returns(true);

        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode($this->groupname)
            .'/members/'. urlencode($this->username);

        $expected_options = array(
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_PUT             => true,
        );

        expect($this->http_client)->doRequest()->count(2);
        expect($this->http_client)->addOptions($this->expected_options_get_user)->at(0);
        expect($this->http_client)->addOptions($expected_options)->at(1);

        $this->driver->addUserToGroup($this->gerrit_server, $this->user, $this->groupname);
    }

    public function itExecutesTheDeletionCommand(){
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode($this->groupname)
            .'/members/'. urlencode($this->username);

        $expected_options = array(
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_CUSTOMREQUEST   => 'DELETE',
        );

        expect($this->http_client)->doRequest()->once();
        expect($this->http_client)->addOptions($expected_options)->once();

        $this->driver->removeUserFromGroup($this->gerrit_server, $this->user, $this->groupname);
    }

    public function itRemovesAllMembers(){
        $response_with_group_members = <<<EOS
)]}'
[
  {
    "_account_id": 1000000,
    "name": "gerrit-adm",
    "username": "gerrit-adm",
    "avatars": []
  },
  {
    "_account_id": 1000002,
    "name": "testUser",
    "email": "test@test.test",
    "username": "testUser",
    "avatars": []
  }
]
EOS;

        stub($this->http_client)->getLastResponse()->returns($response_with_group_members);

        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode($this->groupname)
            .'/members.delete';

        $expected_options = array(
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_POST            => true,
            CURLOPT_HTTPHEADER      => array(Git_Driver_GerritREST::CONTENT_TYPE_JSON),
            CURLOPT_POSTFIELDS      => json_encode(
                array(
                    'members' => array('gerrit-adm', 'testUser')
                )
            )
        );

        $url_get_members = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode($this->groupname)
            .'/members';

        $expected_options_get_members = array(
            CURLOPT_URL             => $url_get_members,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_CUSTOMREQUEST   => 'GET',
        );

        expect($this->http_client)->doRequest()->count(2);
        expect($this->http_client)->addOptions()->count(2);
        expect($this->http_client)->addOptions($expected_options_get_members)->at(0);
        expect($this->http_client)->addOptions($expected_options)->at(1);

        $this->driver->removeAllGroupMembers($this->gerrit_server, $this->groupname);
    }
}