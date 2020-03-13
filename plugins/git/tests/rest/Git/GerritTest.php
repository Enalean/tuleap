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

use Guzzle\Http\Message\Response;
use REST_TestDataBuilder;
use Tuleap\Git\REST\TestBase;

require_once dirname(__FILE__) . '/../bootstrap.php';

/**
 * @group GitTests
 */
class GerritTest extends TestBase
{

    protected function getResponseForNonMember($request)
    {
        return $this->getResponse(
            $request,
            REST_TestDataBuilder::TEST_USER_2_NAME
        );
    }

    public function testOPTIONS(): void
    {
        $response = $this->getResponse($this->client->options('gerrit'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSWithReadOnlySiteAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('gerrit'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETServers(): void
    {
        $response = $this->getResponse($this->client->get('gerrit'));

        $this->assertGETServers($response);
    }

    public function testGETServersWithReadOnlySiteAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get('gerrit'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETServers($response);
    }

    private function assertGETServers(Response $response): void
    {
        $response_servers = $response->json();
        $this->assertArrayHasKey('servers', $response_servers);

        $servers = $response_servers['servers'];
        $this->assertCount(2, $servers);
        $this->assertEquals($servers[0]['id'], 1);
        $this->assertEquals($servers[0]['html_url'], 'https://localhost:8080');
        $this->assertEquals($servers[1]['id'], 2);
        $this->assertEquals($servers[1]['html_url'], 'http://otherhost:8080');
    }

    public function testGETServersForProject(): void
    {
        $url = 'gerrit?for_project=' . $this->git_project_id;
        $response  = $this->getResponse($this->client->get($url));

        $this->assertGETServersForProject($response);
    }

    public function testGETServersForProjectWithReadOnlyAdmin(): void
    {
        $url = 'gerrit?for_project=' . $this->git_project_id;
        $response  = $this->getResponse(
            $this->client->get($url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETServersForProject($response);
    }

    public function testGetGitRepositoryThrows403IfUserCantSeeRepository(): void
    {
        $response  = $this->getResponseForNonMember($this->client->get('gerrit'));

        $this->assertEquals($response->getStatusCode(), 403);
    }

    private function assertGETServersForProject(Response $response): void
    {
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
}
