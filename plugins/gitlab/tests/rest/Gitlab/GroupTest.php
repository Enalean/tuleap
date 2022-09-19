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

require_once __DIR__ . '/../bootstrap.php';

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
        self::assertEqualsCanonicalizing(['OPTIONS', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testPatchGitlabGroupLinkToUpdateCreateBranchPrefix(): void
    {
        $patch_body = json_encode(
            [
                'create_branch_prefix' => "dev-",
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'gitlab_groups/' . urlencode($this->gitlab_group_id))
                ->withBody($this->stream_factory->createStream($patch_body))
        );
        self::assertSame(200, $response->getStatusCode());

        $gitlab_group_after_patch = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame("dev-", $gitlab_group_after_patch["create_branch_prefix"]);
    }
}
