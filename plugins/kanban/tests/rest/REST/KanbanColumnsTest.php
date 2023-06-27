<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All rights reserved
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

namespace Tuleap\Kanban\REST;

use REST_TestDataBuilder;

/**
 * @group KanbanTests
 */
class KanbanColumnsTest extends TestBase
{
    public function testOPTIONSKanbanColumns()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'kanban_columns'));
        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSKanbanColumnsWithRESTReadOnlyUser()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'kanban_columns'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME,
        );

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testPATCHKanbanColumns()
    {
        $url = 'kanban_columns/' . REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID . '?kanban_id=' . REST_TestDataBuilder::KANBAN_ID;

        $response = $this->getResponse($this->request_factory->createRequest(
            'PATCH',
            $url
        )->withBody($this->stream_factory->createStream(json_encode([
            "wip_limit" => 200,
            "label"     => "yummy",
        ]))));

        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponse($this->request_factory->createRequest(
            'GET',
            'kanban/' . REST_TestDataBuilder::KANBAN_ID
        ));
        $kanban   = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($kanban['columns'][1]['limit'], 200);
        $this->assertEquals($kanban['columns'][1]['label'], "yummy");
    }

    public function testPATCHKanbanColumnsDeniedForRESTReadOnlyUserNotInvolvedInProject()
    {
        $url = 'kanban_columns/' . REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID . '?kanban_id=' . REST_TestDataBuilder::KANBAN_ID;

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'PATCH',
                $url
            )->withBody($this->stream_factory->createStream(json_encode([
                "wip_limit" => 200,
                "label"     => "yummy",
            ]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME,
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testDELETEKanbanColumnsDeniedForRESTReadOnlyUserNotInvolvedInProject(): void
    {
        $url      = 'kanban_columns/' . REST_TestDataBuilder::KANBAN_OTHER_VALUE_COLUMN_ID . '?kanban_id=' . REST_TestDataBuilder::KANBAN_ID;
        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', $url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testDELETEKanbanColumns(): void
    {
        $url = 'kanban_columns/' . REST_TestDataBuilder::KANBAN_OTHER_VALUE_COLUMN_ID . '?kanban_id=' . REST_TestDataBuilder::KANBAN_ID;

        $response = $this->getResponse($this->request_factory->createRequest('DELETE', $url));
        $this->assertEquals($response->getStatusCode(), 200);
    }
}
