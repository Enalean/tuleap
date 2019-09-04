<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All rights reserved
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
use Tuleap\Git\REST\TestBase;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @group GitTests
 */
class ProjectTest extends TestBase
{

    public function testGetGitRepositories(): void
    {
        $response = $this->getResponse(
            $this->client->get(
                'projects/' . $this->git_project_id . '/git'
            )
        );

        $this->assertGETGitRepositories($response);
    }

    private function assertGETGitRepositories(Response $response): void
    {
        $repositories_response = $response->json();
        $repositories          = $repositories_response['repositories'];

        $this->assertCount(1, $repositories);

        $repository = $repositories[0];
        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertEquals($repository['description'], 'Git repository');
    }

    public function testGetGitRepositoriesWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get(
                'projects/' . $this->git_project_id . '/git'
            ),
            \REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETGitRepositories($response);
    }

    public function testScopeProject(): void
    {
        $query    = urlencode(json_encode(["scope" => "project"]));
        $response = $this->getResponse(
            $this->client->get(
                'projects/' . $this->git_project_id . '/git?query=' . $query
            )
        );

        $repositories_response = $response->json();
        $repositories          = $repositories_response['repositories'];

        $this->assertCount(1, $repositories);
    }

    public function testScopeIndividual(): void
    {
        $query    = urlencode(json_encode(["scope" => "individual"]));
        $response = $this->getResponse(
            $this->client->get(
                'projects/' . $this->git_project_id . '/git?query=' . $query
            )
        );

        $repositories_response = $response->json();
        $repositories          = $repositories_response['repositories'];

        $this->assertCount(0, $repositories);
    }
}
