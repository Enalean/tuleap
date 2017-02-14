<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Git;

use GitDataBuilder;
use REST_TestDataBuilder;
use RestBase;

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group GitTests
 */
class GerritTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    protected function getResponseForNonMember($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_2_NAME),
            $request
        );
    }

    public function testOPTIONS() {
        $response = $this->getResponse($this->client->options('gerrit'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGetServers() {
        $response  = $this->getResponse($this->client->get('gerrit'));

        $response_servers = $response->json();
        $this->assertArrayHasKey('servers', $response_servers);

        $servers = $response_servers['servers'];
        $this->assertCount(2, $servers);
        $this->assertEquals($servers[0]['id'], 1);
        $this->assertEquals($servers[0]['html_url'], 'https://localhost:8080');
        $this->assertEquals($servers[1]['id'], 2);
        $this->assertEquals($servers[1]['html_url'], 'http://otherhost:8080');
    }

    public function testGetServersForProject() {
        $url = 'gerrit?for_project=' . GitDataBuilder::PROJECT_TEST_GIT_ID;
        $response  = $this->getResponse($this->client->get($url));

        $response_servers = $response->json();
        $this->assertArrayHasKey('servers', $response_servers);

        $servers = $response_servers['servers'];
        $this->assertCount(3, $servers);
        $this->assertEquals($servers[0]['id'], 1);
        $this->assertEquals($servers[0]['html_url'], 'https://localhost:8080');
        $this->assertEquals($servers[1]['id'], 2);
        $this->assertEquals($servers[1]['html_url'], 'http://otherhost:8080');
        $this->assertEquals($servers[2]['id'], 3);
        $this->assertEquals($servers[2]['html_url'], 'http://restricted:8080');
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGetGitRepositoryThrows403IfUserCantSeeRepository() {
        $response  = $this->getResponseForNonMember($this->client->get('gerrit'));

        $this->assertEquals($response->getStatusCode(), 403);
    }
}