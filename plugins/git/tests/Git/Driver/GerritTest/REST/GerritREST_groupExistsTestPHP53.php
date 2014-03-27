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

class Git_DriverREST_Gerrit_groupExistsTest extends Git_Driver_GerritREST_base implements Git_Driver_Gerrit_groupExistsTest {

    public function setUp() {
        parent::setUp();

        $this->group         = 'contributors';
        $this->groupname     = $this->project_name.'/'.$this->namespace.'/'.$this->repository_name.'-'.$this->group;

    }

    public function itReturnsTrueIfGroupExists(){
        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns('');
        stub($this->guzzle_request)->send()->returns($response);

        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $this->assertTrue($this->driver->doesTheGroupExist($this->gerrit_server, $this->groupname));
    }

    public function itReturnsFalseIfGroupDoNotExists(){
        stub($this->guzzle_client)->get()->throws(new Guzzle\Http\Exception\ClientErrorResponseException());

        $this->assertFalse($this->driver->doesTheGroupExist($this->gerrit_server, $this->groupname));
    }

    public function itCallsTheRightOptions() {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'. urlencode($this->groupname);

        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns('');
        stub($this->guzzle_request)->send()->returns($response);

        expect($this->guzzle_client)->get(
            $url,
            array(
                'verify' => false,
            )
        )->once();
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $this->driver->doesTheGroupExist($this->gerrit_server, $this->groupname);
    }
}