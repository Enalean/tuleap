<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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

require_once __DIR__ . '/../bootstrap.php';

/**
 * @group GitTests
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProjectTest extends TestBase
{
    public function testGetGitRepositories(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . $this->git_project_id . '/git')
        );

        $this->assertGETGitRepositories($response);
    }

    private function assertGETGitRepositories(\Psr\Http\Message\ResponseInterface $response): void
    {
        $repositories_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $repositories          = $repositories_response['repositories'];

        $this->assertCount(1, $repositories);
        $this->assertEquals(1, (int) $response->getHeaderLine('X-Pagination-Size'));

        $repository = $repositories[0];
        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertEquals($repository['description'], 'Git repository');
    }

    public function testGetGitRepositoriesWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . $this->git_project_id . '/git'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETGitRepositories($response);
    }

    public function testScopeProject(): void
    {
        $query    = urlencode(json_encode(['scope' => 'project']));
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . $this->git_project_id . '/git?query=' . $query)
        );

        $repositories_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $repositories          = $repositories_response['repositories'];

        $this->assertCount(1, $repositories);
    }

    public function testScopeIndividual(): void
    {
        $query    = urlencode(json_encode(['scope' => 'individual']));
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . $this->git_project_id . '/git?query=' . $query)
        );

        $repositories_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $repositories          = $repositories_response['repositories'];

        $this->assertCount(0, $repositories);
    }
}
