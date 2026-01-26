<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All rights reserved
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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GroupTest extends TestBase
{
    public function testOptionsGitLabGroups(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'gitlab_groups/')
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertEqualsCanonicalizing(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOptionsGitLabGroupsId(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'gitlab_groups/' . urlencode($this->gitlab_group_id))
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertEqualsCanonicalizing(
            ['OPTIONS', 'PATCH', 'DELETE'],
            explode(', ', $response->getHeaderLine('Allow'))
        );
    }

    public function testOptionsGitLabGroupsIdSynchronize(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'gitlab_groups/' . urlencode($this->gitlab_group_id) . '/synchronize')
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertEqualsCanonicalizing(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testPatchGitlabGroupLinkToUpdateCreateBranchPrefix(): void
    {
        $patch_body = json_encode(['create_branch_prefix' => 'dev-']);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'gitlab_groups/' . urlencode($this->gitlab_group_id))
                ->withBody($this->stream_factory->createStream($patch_body))
        );
        self::assertSame(200, $response->getStatusCode());

        $gitlab_group_after_patch = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('dev-', $gitlab_group_after_patch['create_branch_prefix']);
    }

    public function testPatchGitlabGroupLinkToUpdateArtifactClosure(): void
    {
        $patch_body = json_encode(['allow_artifact_closure' => false]);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'gitlab_groups/' . urlencode($this->gitlab_group_id))
                ->withBody($this->stream_factory->createStream($patch_body))
        );
        self::assertSame(200, $response->getStatusCode());

        $gitlab_group_after_patch = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($gitlab_group_after_patch['allow_artifact_closure']);
    }

    public function testPatchGitlabGroupLinkToUpdateAccessToken(): void
    {
        $patch_body = json_encode(['gitlab_token' => '85c205d88a07']);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'gitlab_groups/' . urlencode($this->gitlab_group_id))
                ->withBody($this->stream_factory->createStream($patch_body))
        );
        self::assertSame(200, $response->getStatusCode());
    }

    public function testPatchGitlabGroupLinkToUpdateEverything(): void
    {
        $patch_body = json_encode([
            'create_branch_prefix'   => 'dev2/',
            'allow_artifact_closure' => true,
            'gitlab_token'           => '58e02dbca834',
        ]);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'gitlab_groups/' . urlencode($this->gitlab_group_id))
                ->withBody($this->stream_factory->createStream($patch_body))
        );
        self::assertSame(200, $response->getStatusCode());

        $gitlab_group_after_patch = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('dev2/', $gitlab_group_after_patch['create_branch_prefix']);
        self::assertTrue($gitlab_group_after_patch['allow_artifact_closure']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPatchGitlabGroupLinkToUpdateEverything')]
    #[\PHPUnit\Framework\Attributes\Depends('testPatchGitlabGroupLinkToUpdateCreateBranchPrefix')]
    #[\PHPUnit\Framework\Attributes\Depends('testPatchGitlabGroupLinkToUpdateArtifactClosure')]
    #[\PHPUnit\Framework\Attributes\Depends('testPatchGitlabGroupLinkToUpdateAccessToken')]
    public function testDelete(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'gitlab_groups/' . urlencode($this->gitlab_group_id))
        );
        self::assertSame(200, $response->getStatusCode());
    }
}
