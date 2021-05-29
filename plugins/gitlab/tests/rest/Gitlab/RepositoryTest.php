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

use Guzzle\Http\Message\Response;

require_once __DIR__ . '/../bootstrap.php';

class RepositoryTest extends TestBase
{
    public function testOptionsGitLabRepositories(): void
    {
        $response = $this->getResponse(
            $this->client->options(
                'gitlab_repositories/' . $this->gitlab_repository_id
            )
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertEquals(['OPTIONS', 'PATCH', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testPatchGitlabRepositoryIntegration(): void
    {
        $gitlab_integration_before_patch = $this->getGitlabRepositoryIntegration()->json()[0];
        self::assertFalse($gitlab_integration_before_patch["allow_artifact_closure"]);

        $patch_body = json_encode(
            [
                'allow_artifact_closure' => true
            ]
        );

        $response = $this->getResponse(
            $this->client->patch(
                'gitlab_repositories/' . $this->gitlab_repository_id,
                null,
                $patch_body
            )
        );
        self::assertSame(200, $response->getStatusCode());

        $gitlab_integration_after_patch = $response->json();
        self::assertTrue($gitlab_integration_after_patch["allow_artifact_closure"]);
    }

    public function testDeleteGitLabRepositories(): void
    {
        $response = $this->getResponse(
            $this->client->delete(
                'gitlab_repositories/' . $this->gitlab_repository_id . '?project_id=' . $this->gitlab_project_id
            )
        );

        self::assertSame(204, $response->getStatusCode());

        self::assertRepositoryDeleted();
    }

    private function assertRepositoryDeleted(): void
    {
        $response = $this->getGitlabRepositoryIntegration();

        self::assertSame(200, $response->getStatusCode());

        self::assertEquals(0, (int) (string) $response->getHeader('X-Pagination-Size'));

        $gitlab_repositories = $response->json();
        self::assertCount(0, $gitlab_repositories);
    }

    private function getGitlabRepositoryIntegration(): Response
    {
        return $this->getResponse(
            $this->client->get(
                'projects/' . $this->gitlab_project_id . '/gitlab_repositories'
            )
        );
    }
}
