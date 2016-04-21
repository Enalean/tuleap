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

class Git_DriverREST_Gerrit_manageGroupsTest extends Git_Driver_GerritREST_base implements Git_Driver_Gerrit_manageGroupsTest {

    public function setUp() {
        parent::setUp();
        $this->gerrit_driver = partial_mock(
            'Git_Driver_GerritREST',
            array('doesTheGroupExist', 'getGroupUUID'),
            array($this->guzzle_client, $this->logger, 'Digest')
        );
    }

    public function itCreatesGroupsIfItNotExistsOnGerrit(){
        stub($this->gerrit_driver)->doesTheGroupExist()->returns(false);
        stub($this->gerrit_driver)->getGroupUUID($this->gerrit_server, 'firefox/project_admins')->returns('aabbccdd');

        $url_create_group = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode('firefox/project_members');

        $expected_json_data = json_encode(
            array(
                'owner_id' => 'aabbccdd'
            )
        );

        expect($this->guzzle_client)->put(
            $url_create_group,
            array(
                Git_Driver_GerritREST::HEADER_CONTENT_TYPE => Git_Driver_GerritREST::MIME_JSON,
                'verify' => false,
            ),
            $expected_json_data
        )->once();
        stub($this->guzzle_client)->put()->returns($this->guzzle_request);


        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }

    public function itDoesNotCreateGroupIfItAlreadyExistsOnGerrit(){
        stub($this->gerrit_driver)->doesTheGroupExist()->returns(true);
        stub($this->gerrit_driver)->getGroupUUID()->returns('aabbccdd');

        expect($this->guzzle_client)->put()->never();

        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }
    public function itInformsAboutGroupCreation(){
        stub($this->gerrit_driver)->doesTheGroupExist()->returns(false);
        stub($this->gerrit_driver)->getGroupUUID()->returns('aabbccdd');

        stub($this->guzzle_client)->put()->returns($this->guzzle_request);
        expect($this->logger)->info()->count(2);
        expect($this->logger)->info("Gerrit REST driver: Create group firefox/project_members")->at(0);
        expect($this->logger)->info("Gerrit REST driver: Group firefox/project_members successfully created")->at(1);

        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }
    public function itRaisesAGerritDriverExceptionOnGroupsCreation(){}

    public function itCreatesGroupWithoutOwnerWhenSelfOwnedToAvoidChickenEggIssue(){
        stub($this->gerrit_driver)->doesTheGroupExist()->returns(false);
        stub($this->gerrit_driver)->getGroupUUID($this->gerrit_server, Git_Driver_GerritREST::DEFAULT_GROUP_OWNER)->returns('aabbccddee');

        $url_create_group = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode('firefox/project_admins');

        $expected_json_data = json_encode(
            array(
                'owner_id' => 'aabbccddee'
            )
        );

        expect($this->guzzle_client)->put(
            $url_create_group,
            array(
                Git_Driver_GerritREST::HEADER_CONTENT_TYPE => Git_Driver_GerritREST::MIME_JSON,
                'verify' => false,
            ),
            $expected_json_data
        )->once();
        stub($this->guzzle_client)->put()->returns($this->guzzle_request);

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

        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns($get_group_response);
        stub($this->guzzle_request)->send()->returns($response);

        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/enalean';

        expect($this->guzzle_client)->get(
            $url,
            array(
                'verify' => false,
            )
        )->once();
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

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

        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns($get_group_response);
        stub($this->guzzle_request)->send()->returns($response);

        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/enalean';

        expect($this->guzzle_client)->get(
            $url,
            array(
                'verify' => false,
            )
        )->once();
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $this->assertEqual(
            $this->driver->getGroupId($this->gerrit_server, 'enalean'),
            8
        );
    }

    public function itReturnsNullIdIfNotFound() {
        stub($this->guzzle_client)->get()->throws(new Guzzle\Http\Exception\ClientErrorResponseException());

        $this->assertNull($this->driver->getGroupId($this->gerrit_server, 'enalean'));
    }

    public function itReturnsNullUUIDIfNotFound() {
        stub($this->guzzle_client)->get()->throws(new Guzzle\Http\Exception\ClientErrorResponseException());

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
        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns($raiponce);
        stub($this->guzzle_request)->send()->returns($response);

        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/';

        expect($this->guzzle_client)->get(
            $url,
            array(
                'verify' => false,
            )
        )->once();
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $expected_result = array(
            "enalean" => "6ef56904c11e6d53c8f2f3657353faaac74bfc6d",
            "grp"     => "b99e4455ca98f2ec23d9250f69617e34ceae6bd6",
        );

        $this->assertEqual($this->driver->getAllGroups($this->gerrit_server), $expected_result);
    }
}