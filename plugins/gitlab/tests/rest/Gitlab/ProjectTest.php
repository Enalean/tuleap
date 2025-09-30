<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All rights reserved
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

namespace Tuleap\Gitlab\REST;

require_once __DIR__ . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProjectTest extends TestBase
{
    public function testOptionsProjectGitLabRepositories(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'projects/' . $this->gitlab_project_id . '/gitlab_repositories')
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGetGitLabRepositories(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . $this->gitlab_project_id . '/gitlab_repositories')
        );

        $this->assertGETGitLabRepositories($response);
    }

    private function assertGETGitLabRepositories(\Psr\Http\Message\ResponseInterface $response): void
    {
        self::assertSame(200, $response->getStatusCode());

        self::assertEquals(1, (int) $response->getHeaderLine('X-Pagination-Size'));

        $gitlab_repositories = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $gitlab_repositories);

        $gitlab_repository = $gitlab_repositories[0];
        self::assertArrayHasKey('id', $gitlab_repository);
        self::assertArrayHasKey('gitlab_repository_id', $gitlab_repository);
        self::assertEquals('path/repo01', $gitlab_repository['name']);
        self::assertEquals('desc', $gitlab_repository['description']);
        self::assertEquals('https://example.com/path/repo01', $gitlab_repository['gitlab_repository_url']);
        self::assertEquals(15412, $gitlab_repository['gitlab_repository_id']);
        self::assertEquals($this->gitlab_repository_id, $gitlab_repository['id']);
        self::assertFalse($gitlab_repository['allow_artifact_closure']);
        self::assertFalse($gitlab_repository['is_webhook_configured']);
        self::assertSame('', $gitlab_repository['create_branch_prefix']);
    }
}
