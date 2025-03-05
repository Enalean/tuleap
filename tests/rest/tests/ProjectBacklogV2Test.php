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

namespace Tuleap\REST;

use Psr\Http\Message\ResponseInterface;
use REST_TestDataBuilder;
use RestBase;

/**
 * @group BacklogItemsTest
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProjectBacklogV2Test extends RestBase
{
    public function testOPTIONSBacklog(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', "v2/projects/$this->project_pbi_id/backlog"));
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSBacklogWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', "v2/projects/$this->project_pbi_id/backlog"),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETProjectTopBacklogNoItems(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', "v2/projects/$this->project_pbi_id/backlog?limit=0&offset=0"));

        $this->assertGETProjectBacklog($response);
    }

    public function testGETProjectTopBacklogNoItemsWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "v2/projects/$this->project_pbi_id/backlog?limit=0&offset=0"),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETProjectBacklog($response);
    }

    private function assertGETProjectBacklog(ResponseInterface $response): void
    {
        $backlog = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertCount(0, $backlog['content']);
        $this->assertCount(1, $backlog['accept']['trackers']);
        $this->assertCount(0, $backlog['accept']['parent_trackers']);
    }

    public function testGETProjectTopBacklogNoPlannings(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', "v2/projects/$this->project_public_id/backlog"));

        $this->assertEquals($response->getStatusCode(), 404);
    }
}
