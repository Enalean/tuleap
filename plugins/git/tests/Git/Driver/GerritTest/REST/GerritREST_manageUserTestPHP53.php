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

require_once 'GerritREST_Base.php';

class Git_DriverREST_Gerrit_manageUserTest extends Git_Driver_GerritREST_base implements Git_Driver_Gerrit_manageUserTest {
    private $username = 'someuser';

    /** @var Git_Driver_Gerrit_User */
    private $user;

    public function setUp() {
        parent::setUp();

        $this->user      = mock('Git_Driver_Gerrit_User');
        $this->group     = 'contributors';
        $this->groupname = $this->project_name.'/'.$this->namespace.'/'.$this->repository_name.'-'.$this->group;

        stub($this->user)->getRealName()->returns('John Doe');
        stub($this->user)->getEmail()->returns('jdoe@example.com');
        stub($this->user)->getSSHUserName()->returns($this->username);
    }

    public function itExecutesTheInsertCommand(){
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode($this->groupname)
            .'/members/'. urlencode($this->username);

        expect($this->guzzle_client)->put($url, '*')->once();
        stub($this->guzzle_client)->put()->returns($this->guzzle_request);

        $this->driver->addUserToGroup($this->gerrit_server, $this->user, $this->groupname);
    }

    public function itExecutesTheDeletionCommand(){
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode($this->groupname)
            .'/members/'. urlencode($this->username);

        expect($this->guzzle_client)->delete($url, '*')->once();
        stub($this->guzzle_client)->delete()->returns($this->guzzle_request);

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

        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode($this->groupname)
            .'/members.delete';

        expect($this->guzzle_client)->post(
            $url,
            array(
                Git_Driver_GerritREST::HEADER_CONTENT_TYPE => Git_Driver_GerritREST::MIME_JSON,
                'verify' => false,
            ),
            json_encode(
                array(
                    'members' => array('gerrit-adm', 'testUser')
                )
            )
        )->once();
        stub($this->guzzle_client)->post()->returns($this->guzzle_request);

        $response     = stub('Guzzle\Http\Message\Response')->getBody(true)->returns($response_with_group_members);
        $post_request = stub('Guzzle\Http\Message\EntityEnclosingRequest')->send()->returns($response);

        $url_get_members = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode($this->groupname)
            .'/members';

        expect($this->guzzle_client)->get(
            $url_get_members,
            array(
                'verify' => false,
            )
        )->once();
        stub($this->guzzle_client)->get()->returns($post_request);

        $this->driver->removeAllGroupMembers($this->gerrit_server, $this->groupname);
    }

    public function itAddsAnSSHKeyforUser() {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/accounts/'. urlencode($this->user->getSSHUserName()) .'/sshkeys';

        $ssh_key = "AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw==";

        $encoded_ssh_key = "AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d";

        expect($this->guzzle_client)->post(
            $url,
            array(
                Git_Driver_GerritREST::HEADER_CONTENT_TYPE => Git_Driver_GerritREST::MIME_TEXT,
                'verify' => false,
            ),
            $encoded_ssh_key
        )->once();
        stub($this->guzzle_client)->post()->returns($this->guzzle_request);
        expect($this->logger)->info()->count(2);

        $this->driver->addSSHKeyToAccount($this->gerrit_server, $this->user, $ssh_key);
    }

    public function itRemovesAnSSHKeyforUser() {
        $url_list_keys = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/accounts/'. urlencode($this->user->getSSHUserName()) .'/sshkeys';

        $existing_keys = <<<EOS
)]}'
[
  {
    "seq": 1,
    "ssh_public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...azertyAw\u003d\u003d john.doe@example.com",
    "encoded_key": "AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...azertyAw\u003d\u003d",
    "algorithm": "ssh-rsa",
    "comment": "john.doe@example.com",
    "valid": true
  },
  {
    "seq": 2,
    "ssh_public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d john.doe@example.com",
    "encoded_key": "AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d",
    "algorithm": "ssh-rsa",
    "comment": "john.doe@example.com",
    "valid": true
  }
]
EOS;

        stub($this->guzzle_client)->get($url_list_keys, '*')->returns($this->getGuzzleRequestWithTextResponse($existing_keys));


        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/accounts/'. urlencode($this->user->getSSHUserName()) .'/sshkeys/2';

        $ssh_key = "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw== john.doe@example.com";

        expect($this->guzzle_client)->delete(
            $url,
            array(
                'verify' => false,
            )
        )->once();
        stub($this->guzzle_client)->delete()->returns($this->guzzle_request);

        expect($this->logger)->info()->count(6);

        $this->driver->removeSSHKeyFromAccount($this->gerrit_server, $this->user, $ssh_key);
    }

    public function itRemovesMultipleTimeTheSSHKeyforUserIfFoundMultipleTimes() {
        $url_list_keys = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/accounts/'. urlencode($this->user->getSSHUserName()) .'/sshkeys';

        $existing_keys = <<<EOS
)]}'
[
  {
    "seq": 1,
    "ssh_public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...azertyAw\u003d\u003d john.doe@example.com",
    "encoded_key": "AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...azertyAw\u003d\u003d",
    "algorithm": "ssh-rsa",
    "comment": "john.doe@example.com",
    "valid": true
  },
  {
    "seq": 2,
    "ssh_public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d john.doe@example.com",
    "encoded_key": "AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d",
    "algorithm": "ssh-rsa",
    "comment": "john.doe@example.com",
    "valid": true
  },
  {
    "seq": 3,
    "ssh_public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d another comment that do not match the requested comment",
    "encoded_key": "AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw\u003d\u003d",
    "algorithm": "ssh-rsa",
    "comment": "another comment that do not match the requested comment",
    "valid": true
  }
]
EOS;
        stub($this->guzzle_client)->get($url_list_keys, '*')->returns($this->getGuzzleRequestWithTextResponse($existing_keys));

        $url_first_key = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/accounts/'. urlencode($this->user->getSSHUserName()) .'/sshkeys/2';

        $ssh_key = "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA0T...YImydZAw== john.doe@example.com";

        expect($this->guzzle_client)->delete(
            $url_first_key,
            array(
                'verify' => false,
            )
        )->at(0);

        $url_second_key = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/accounts/'. urlencode($this->user->getSSHUserName()) .'/sshkeys/3';

        expect($this->guzzle_client)->delete(
            $url_second_key,
            array(
                'verify' => false,
            )
        )->at(1);

        expect($this->guzzle_client)->delete()->count(2);
        stub($this->guzzle_client)->delete()->returns($this->guzzle_request);

        expect($this->logger)->info()->count(8);

        $this->driver->removeSSHKeyFromAccount($this->gerrit_server, $this->user, $ssh_key);
    }
}