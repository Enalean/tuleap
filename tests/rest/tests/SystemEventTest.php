<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\SystemEvent\REST;

use Guzzle\Http\Message\Response;
use RestBase;
use REST_TestDataBuilder;

/**
 * @group SystemEventTests
 */
final class SystemEventTest extends RestBase
{

    public function testGET(): void
    {
        $response  = $this->getResponse(
            $this->client->get('system_event'),
            REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertGETSystemEvents($response);
    }

    public function testGETWithRESTReadOnlyUser(): void
    {
        $response  = $this->getResponse(
            $this->client->get('system_event'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETSystemEvents($response);
    }

    private function assertGETSystemEvents(Response $response): void
    {
        $response_json = $response->json();

        $system_event_01 = $response_json[0];
        $this->assertEquals($system_event_01['id'], 1);
        $this->assertEquals($system_event_01['type'], 'Tuleap\\SystemEvent\\SystemEventUserActiveStatusChange');
        $this->assertEquals($system_event_01['owner'], "root");
    }

    public function testOptions(): void
    {
        $response = $this->getResponse(
            $this->client->options('system_event'),
            REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOptionsWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->options('system_event'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }
}
