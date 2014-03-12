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

interface Git_Driver_Gerrit_removeIncludedGroupTest {
    public function itRemovesAllIncludedGroups();
}

class Git_Driver_GerritLegacy_removeIncludedGroupTest extends TuleapTestCase implements Git_Driver_Gerrit_removeIncludedGroupTest {
    public function setUp() {
        parent::setUp();
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');

        $this->ssh    = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $this->logger = mock('BackendLogger');
        $this->driver = partial_mock(
            'Git_Driver_GerritLegacy',
            array('getGroupId'),
            array($this->ssh, $this->logger)
        );
    }

    public function itRemovesAllIncludedGroups() {
        $id = 272;
        $group_name    = 'gdb/developers';

        stub($this->driver)->getGroupId($this->gerrit_server, $group_name)->returns($id);

        $delete_included_query = 'gerrit gsql --format json -c "DELETE\ FROM\ account_group_includes\ I\ WHERE\ I.group_id='.$id.'"';

        expect($this->ssh)->execute()->count(2);
        expect($this->ssh)->execute($this->gerrit_server, $delete_included_query)->at(0);
        expect($this->ssh)->execute($this->gerrit_server, 'gerrit flush-caches --cache groups_byinclude')->at(1);

        $this->driver->removeAllIncludedGroups($this->gerrit_server, $group_name);
    }
}

class Git_Driver_GerritREST_removeIncludedGroupTest extends Git_Driver_GerritREST_baseTest implements Git_Driver_Gerrit_removeIncludedGroupTest {
    public function itRemovesAllIncludedGroups() {
        $groupname = "parent group";
        $response_with_included_groups = <<<EOS
)]}'
[
  {
    "kind": "gerritcodereview#group",
    "url": "#/admin/groups/uuid-6ef56904c11e6d53c8f2f3657353faaac74bfc6d",
    "options": {},
    "group_id": 7,
    "owner": "enalean",
    "owner_id": "6ef56904c11e6d53c8f2f3657353faaac74bfc6d",
    "id": "6ef56904c11e6d53c8f2f3657353faaac74bfc6d",
    "name": "enalean"
  },
  {
    "kind": "gerritcodereview#group",
    "url": "#/admin/groups/uuid-b99e4455ca98f2ec23d9250f69617e34ceae6bd6",
    "options": {},
    "group_id": 6,
    "owner": "another group",
    "owner_id": "b99e4455ca98f2ec23d9250f69617e34ceae6bd6",
    "id": "b99e4455ca98f2ec23d9250f69617e34ceae6bd6",
    "name": "another group"
  }
]
EOS;

        stub($this->http_client)->getLastResponse()->returns($response_with_included_groups);

        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode($groupname) .'/groups.delete';

        $expected_options = array(
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_POST            => true,
            CURLOPT_HTTPHEADER      => array(Git_Driver_GerritREST::CONTENT_TYPE_JSON),
            CURLOPT_POSTFIELDS      => json_encode(
                array(
                    'groups' => array('enalean', 'another group')
                )
            )
        );

        $url_get_members = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode($groupname) .'/groups';

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

        $this->driver->removeAllIncludedGroups($this->gerrit_server, $groupname);
    }
}