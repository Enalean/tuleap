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
class RepositoryTest extends TestBase
{
    public function testOptionsGitLabRepositories(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'gitlab_repositories/' . $this->gitlab_repository_id)
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testPatchGitlabRepositoryIntegrationToAllowArtifactClosure(): void
    {
        $gitlab_integration_before_patch = json_decode($this->getGitlabRepositoryIntegration()->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)[0];
        self::assertFalse($gitlab_integration_before_patch['allow_artifact_closure']);

        $patch_body = json_encode(
            [
                'allow_artifact_closure' => true,
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'gitlab_repositories/' . $this->gitlab_repository_id)->withBody($this->stream_factory->createStream($patch_body))
        );
        self::assertSame(200, $response->getStatusCode());

        $gitlab_integration_after_patch = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($gitlab_integration_after_patch['allow_artifact_closure']);
    }

    public function testPatchGitlabRepositoryIntegrationToUpdateCreateBranchPrefix(): void
    {
        $gitlab_integration_before_patch = json_decode($this->getGitlabRepositoryIntegration()->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)[0];
        self::assertSame('', $gitlab_integration_before_patch['create_branch_prefix']);

        $patch_body = json_encode(
            [
                'create_branch_prefix' => 'dev-',
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'gitlab_repositories/' . $this->gitlab_repository_id)->withBody($this->stream_factory->createStream($patch_body))
        );
        self::assertSame(200, $response->getStatusCode());

        $gitlab_integration_after_patch = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('dev-', $gitlab_integration_after_patch['create_branch_prefix']);
    }

    public function testPatchGitlabRepositoryIntegrationToUpdateCreateBranchPrefixFailsIfPrefixIsNotValid(): void
    {
        $gitlab_integration_before_patch = json_decode($this->getGitlabRepositoryIntegration()->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)[0];

        $patch_body = json_encode(
            [
                'create_branch_prefix' => 'dev:',
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'gitlab_repositories/' . $this->gitlab_repository_id)->withBody($this->stream_factory->createStream($patch_body))
        );
        self::assertSame(400, $response->getStatusCode());
    }

    public function testDeleteGitLabRepositories(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'gitlab_repositories/' . $this->gitlab_repository_id . '?project_id=' . $this->gitlab_project_id)
        );

        self::assertSame(204, $response->getStatusCode());

        $this->assertRepositoryDeleted();
    }

    private function assertRepositoryDeleted(): void
    {
        $response = $this->getGitlabRepositoryIntegration();

        self::assertSame(200, $response->getStatusCode());

        self::assertEquals(0, (int) $response->getHeaderLine('X-Pagination-Size'));

        $gitlab_repositories = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(0, $gitlab_repositories);
    }

    private function getGitlabRepositoryIntegration(): \Psr\Http\Message\ResponseInterface
    {
        return $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . $this->gitlab_project_id . '/gitlab_repositories')
        );
    }
}
