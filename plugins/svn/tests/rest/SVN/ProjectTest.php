<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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

namespace Tuleap\SVN\REST;

use Guzzle\Http\Message\Response;
use REST_TestDataBuilder;

require_once dirname(__FILE__) . '/../bootstrap.php';

/**
 * @group SVNTests
 */
class ProjectTest extends TestBase
{
    public function testGETRepositories(): void
    {
        $response  = $this->getResponse($this->client->get(
            'projects/' . $this->svn_project_id . '/svn'
        ));

        $this->assertRepositories($response);
    }

    public function testGETRepositoriesWithRESTReadOnlyUser(): void
    {
        $response  = $this->getResponse(
            $this->client->get('projects/' . $this->svn_project_id . '/svn'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertRepositories($response);
    }

    private function assertRepositories(Response $response): void
    {
        $repositories_response = $response->json();
        $repositories          = $repositories_response['repositories'];

        $this->assertCount(2, $repositories);
        $this->assertEquals(2, (int) (string) $response->getHeader('X-Pagination-Size'));

        $repository_01 = $repositories[0];
        $this->assertArrayHasKey('id', $repository_01);
        $this->assertEquals($repository_01['name'], 'repo01');
        $this->assertEquals($repository_01['svn_url'], $this->svn_domain . '/svnplugin/SVN-plugin-test/repo01');

        $repository_02 = $repositories[1];
        $this->assertArrayHasKey('id', $repository_02);
        $this->assertEquals($repository_02['name'], 'repo02');
        $this->assertEquals($repository_02['svn_url'], $this->svn_domain . '/svnplugin/SVN-plugin-test/repo02');
    }

    public function testGETRepositoriesWithQuery()
    {
        $query = http_build_query(
            array(
                'query' => json_encode(array('name' => 'repo01'))
            )
        );

        $response  = $this->getResponse($this->client->get(
            "projects/$this->svn_project_id/svn?$query"
        ));

        $repositories_response = $response->json();
        $repositories          = $repositories_response['repositories'];

        $this->assertCount(1, $repositories);
        $this->assertEquals(1, (int) (string) $response->getHeader('X-Pagination-Size'));

        $repository = $repositories[0];
        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertEquals($repository['svn_url'], $this->svn_domain . '/svnplugin/SVN-plugin-test/repo01');
    }

    public function testOPTIONS()
    {
        $response  = $this->getResponse($this->client->options(
            'projects/' . $this->svn_project_id . '/svn'
        ));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSWithRESTReadOnlyUser()
    {
        $response  = $this->getResponse(
            $this->client->options('projects/' . $this->svn_project_id . '/svn'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }
}
