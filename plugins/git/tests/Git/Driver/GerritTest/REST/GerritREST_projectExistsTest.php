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

class Git_Driver_GerritREST_projectExistsTest extends Git_Driver_GerritREST_base implements Git_Driver_Gerrit_projectExistsTest {

    public function itReturnsFalseIfParentProjectDoNotExists() {
        stub($this->guzzle_client)->get()->throws(new Guzzle\Http\Exception\ClientErrorResponseException());

        $this->assertFalse($this->driver->doesTheParentProjectExist($this->gerrit_server, $this->project_name));
    }

    public function itReturnsTrueIfParentProjectExists() {
        stub($this->guzzle_client)->get()->returns($this->getGuzzleRequestWithTextResponse(''));

        $this->assertTrue($this->driver->doesTheParentProjectExist($this->gerrit_server, $this->project_name));
    }

    public function itReturnsTrueIfTheProjectExists() {
        stub($this->guzzle_client)->get()->returns($this->getGuzzleRequestWithTextResponse(''));

        $this->assertTrue($this->driver->doesTheProjectExist($this->gerrit_server, $this->project_name));
    }

    public function itReturnsFalseIfTheProjectDoesNotExist() {
        stub($this->guzzle_client)->get()->throws(new Guzzle\Http\Exception\ClientErrorResponseException());

        $this->assertfalse($this->driver->doesTheProjectExist($this->gerrit_server, $this->project_name));
    }

    public function itCallsTheRightOptions() {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/projects/'. urlencode($this->project_name);
        expect($this->guzzle_client)->get($url, '*')->once();
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $this->driver->doesTheParentProjectExist($this->gerrit_server, $this->project_name);
    }
}
