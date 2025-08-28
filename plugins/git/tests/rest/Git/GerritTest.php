<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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

use Tuleap\Git\REST\TestBase;
use Tuleap\REST\RESTTestDataBuilder;

require_once dirname(__FILE__) . '/../bootstrap.php';

/**
 * @group GitTests
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class GerritTest extends TestBase
{
    protected function getResponseForNonMember($request)
    {
        return $this->getResponse(
            $request,
            RESTTestDataBuilder::TEST_USER_2_NAME
        );
    }

    public function testOPTIONS(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'gerrit'));
        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSWithReadOnlySiteAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'gerrit'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETServers(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'gerrit'));

        $this->assertGETServers($response);
    }

    public function testGETServersWithReadOnlySiteAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'gerrit'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETServers($response);
    }

    private function assertGETServers(\Psr\Http\Message\ResponseInterface $response): void
    {
        $response_servers = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
        $url      = 'gerrit?for_project=' . $this->git_project_id;
        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        $this->assertGETServersForProject($response);
    }

    public function testGETServersForProjectWithReadOnlyAdmin(): void
    {
        $url      = 'gerrit?for_project=' . $this->git_project_id;
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', $url),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETServersForProject($response);
    }

    public function testGetGitRepositoryThrows403IfUserCantSeeRepository(): void
    {
        $response = $this->getResponseForNonMember($this->request_factory->createRequest('GET', 'gerrit'));

        $this->assertEquals($response->getStatusCode(), 403);
    }

    private function assertGETServersForProject(\Psr\Http\Message\ResponseInterface $response): void
    {
        $response_servers = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
