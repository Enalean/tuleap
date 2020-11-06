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

class RepositoryTest extends TestBase
{
    public function testOptionsGitLabRepositories(): void
    {
        $response = $this->getResponse(
            $this->client->options(
                'gitlab_repositories/' . $this->gitlab_repository_id
            )
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals(['OPTIONS', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testDeleteGitLabRepositories(): void
    {
        $response = $this->getResponse(
            $this->client->delete(
                'gitlab_repositories/' . $this->gitlab_repository_id . '?project_id=' . $this->gitlab_project_id
            )
        );

        $this->assertSame(204, $response->getStatusCode());

        $this->assertRepositoryDeleted();
    }

    private function assertRepositoryDeleted(): void
    {
        $response = $this->getResponse(
            $this->client->get(
                'projects/' . $this->gitlab_project_id . '/gitlab_repositories'
            )
        );

        $this->assertSame(200, $response->getStatusCode());

        $this->assertEquals(0, (int) (string) $response->getHeader('X-Pagination-Size'));

        $gitlab_repositories = $response->json();
        $this->assertCount(0, $gitlab_repositories);
    }
}
