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

use Guzzle\Http\Message\Response;
use REST_TestDataBuilder;
use RestBase;

/**
 * @group BacklogItemsTest
 */
class ProjectBacklogV2Test extends RestBase
{
    protected $base_url  = 'https://localhost/api/v2';

    public function __construct()
    {
        parent::__construct();
        if (isset($_ENV['TULEAP_HOST'])) {
            $this->base_url = $_ENV['TULEAP_HOST'] . '/api/v2';
        }
    }

    public function testOPTIONSBacklog(): void
    {
        $response = $this->getResponse($this->client->options("projects/$this->project_pbi_id/backlog"));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSBacklogWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->options("projects/$this->project_pbi_id/backlog"),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETProjectTopBacklogNoItems(): void
    {
        $response = $this->getResponse($this->client->get("projects/$this->project_pbi_id/backlog?limit=0&offset=0"));

        $this->assertGETProjectBacklog($response);
    }

    public function testGETProjectTopBacklogNoItemsWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->get("projects/$this->project_pbi_id/backlog?limit=0&offset=0"),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETProjectBacklog($response);
    }

    private function assertGETProjectBacklog(Response $response): void
    {
        $backlog = $response->json();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertCount(0, $backlog['content']);
        $this->assertCount(1, $backlog['accept']['trackers']);
        $this->assertCount(0, $backlog['accept']['parent_trackers']);
    }

    public function testGETProjectTopBacklogNoPlannings(): void
    {
        $response = $this->getResponse($this->client->get("projects/$this->project_public_id/backlog"));

        $this->assertEquals($response->getStatusCode(), 404);
    }
}
