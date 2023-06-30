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

namespace Tuleap\Kanban\REST;

use REST_TestDataBuilder;

/**
 * @group KanbanTests
 */
final class KanbanTest extends TestBase
{
    public function testOPTIONSKanban(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'kanban'));
        $this->assertEquals(['OPTIONS', 'GET', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSKanbanWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'kanban'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(
            ['OPTIONS', 'GET', 'PATCH', 'DELETE'],
            explode(', ', $response->getHeaderLine('Allow'))
        );
    }

    public function testGETKanban(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'kanban/' . REST_TestDataBuilder::KANBAN_ID));

        $this->assertGETKanban($response);
    }

    public function testGETKanbanWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'kanban/' . REST_TestDataBuilder::KANBAN_ID),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETKanban($response);
    }

    private function assertGETKanban(\Psr\Http\Message\ResponseInterface $response): void
    {
        $kanban = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals('My first kanban', $kanban['label']);
        $this->assertEquals($this->kanban_tracker_id, $kanban['tracker']['id']);

        $this->assertEquals('Archive', $kanban['archive']['label']);
        $this->assertArrayHasKey('user_can_add_in_place', $kanban['backlog']);
        $this->assertArrayHasKey('user_can_add_in_place', $kanban['columns'][0]);
        $this->assertNull($kanban['columns'][0]['limit']);
    }

    public function testPATCHKanban()
    {
        $this->assertThatLabelIsUpdated("Willy's really weary");
        $this->assertThatLabelIsUpdated("My first kanban"); // go back to original value
        $this->assertThatBacklogIsToggled();
        $this->assertThatArchiveIsToggled();
        $this->assertThatColumnIsToggled();
    }

    private function assertThatBacklogIsToggled()
    {
        $initial_state_response = $this->getResponse($this->request_factory->createRequest('GET', 'kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $initial_state_kanban   = json_decode($initial_state_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($initial_state_kanban['backlog']['is_open']);

        $patch_response = $this->getResponse($this->request_factory->createRequest('PATCH', 'kanban/' . REST_TestDataBuilder::KANBAN_ID)->withBody($this->stream_factory->createStream(json_encode(
            [
                'collapse_backlog' => true,
            ]
        ))));
        $this->assertEquals($patch_response->getStatusCode(), 200);

        $new_state_response = $this->getResponse($this->request_factory->createRequest('GET', 'kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $new_state_kanban   = json_decode($new_state_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($new_state_kanban['backlog']['is_open']);
    }

    private function assertThatArchiveIsToggled()
    {
        $initial_state_response = $this->getResponse($this->request_factory->createRequest('GET', 'kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $initial_state_kanban   = json_decode($initial_state_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($initial_state_kanban['archive']['is_open']);

        $patch_response = $this->getResponse($this->request_factory->createRequest('PATCH', 'kanban/' . REST_TestDataBuilder::KANBAN_ID)->withBody($this->stream_factory->createStream(json_encode(
            [
                'collapse_archive' => false,
            ]
        ))));
        $this->assertEquals($patch_response->getStatusCode(), 200);

        $new_state_response = $this->getResponse($this->request_factory->createRequest('GET', 'kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $new_state_kanban   = json_decode($new_state_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($new_state_kanban['archive']['is_open']);
    }

    private function assertThatColumnIsToggled()
    {
        $initial_state_response = $this->getResponse($this->request_factory->createRequest('GET', 'kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $initial_state_kanban   = json_decode($initial_state_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $first_column           = $initial_state_kanban['columns'][0];
        $this->assertTrue($first_column['is_open']);

        $patch_response = $this->getResponse($this->request_factory->createRequest('PATCH', 'kanban/' . REST_TestDataBuilder::KANBAN_ID)->withBody($this->stream_factory->createStream(json_encode(
            [
                'collapse_column' => [
                    'column_id' => $first_column['id'],
                    'value'     => true,
                ],
            ]
        ))));
        $this->assertEquals($patch_response->getStatusCode(), 200);

        $new_state_response     = $this->getResponse($this->request_factory->createRequest('GET', 'kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $new_state_kanban       = json_decode($new_state_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $new_state_first_column = $new_state_kanban['columns'][0];
        $this->assertFalse($new_state_first_column['is_open']);
    }

    private function assertThatLabelIsUpdated($new_label)
    {
        $patch_response = $this->getResponse($this->request_factory->createRequest('PATCH', 'kanban/' . REST_TestDataBuilder::KANBAN_ID)->withBody($this->stream_factory->createStream(json_encode(
            [
                'label' => $new_label,
            ]
        ))));
        $this->assertEquals($patch_response->getStatusCode(), 200);

        $response = $this->getResponse($this->request_factory->createRequest('GET', 'kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $kanban   = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($new_label, $kanban['label']);
    }

    public function testPATCHKanbanWithReadOnlyAdmin()
    {
        $patch_response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'kanban/' . REST_TestDataBuilder::KANBAN_ID)->withBody($this->stream_factory->createStream(json_encode(
                [
                    'label' => 'SomeNewLabel',
                ]
            ))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $patch_response->getStatusCode());
    }

    public function testGETBacklog(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/backlog';

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        $this->assertGETBacklog($response);
    }

    public function testGETBacklogWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/backlog';

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', $url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETBacklog($response);
    }

    private function assertGETBacklog(\Psr\Http\Message\ResponseInterface $response): void
    {
        $response_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(2, $response_json['total_size']);
        $this->assertEquals('Do something', $response_json['collection'][0]['label']);
        $this->assertEquals('Do something v2', $response_json['collection'][1]['label']);
    }

    /**
     * @depends testGETBacklog
     */
    public function testPATCHBacklog(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/backlog';

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', $url)->withBody($this->stream_factory->createStream(json_encode([
            'order' => [
                'ids'         => [$this->kanban_artifact_ids[1]],
                'direction'   => 'after',
                'compared_to' => $this->kanban_artifact_ids[2],
            ],
        ]))));
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            [
                $this->kanban_artifact_ids[2],
                $this->kanban_artifact_ids[1],
            ],
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testGETBacklog
     */
    public function testPATCHBacklogWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/backlog';

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $url)->withBody($this->stream_factory->createStream(json_encode([
                'order' => [
                    'ids'         => [$this->kanban_artifact_ids[1]],
                    'direction'   => 'after',
                    'compared_to' => $this->kanban_artifact_ids[2],
                ],
            ]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testGETItems(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/items?column_id=' . REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID;

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        $this->assertGETItems($response);
    }

    public function testGETItemsWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/items?column_id=' . REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID;

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', $url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETItems($response);
    }

    private function assertGETItems(\Psr\Http\Message\ResponseInterface $response): void
    {
        $response_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(2, $response_json['total_size']);
        $this->assertEquals('Doing something', $response_json['collection'][0]['label']);
        $this->assertEquals('Doing something v2', $response_json['collection'][1]['label']);
        $this->assertArrayHasKey('timeinfo', $response_json['collection'][0]);
        $this->assertArrayHasKey('timeinfo', $response_json['collection'][1]);
    }

    /**
     * @depends testGETItems
     */
    public function testPATCHItems(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/items?column_id=' . REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID;

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', $url)->withBody($this->stream_factory->createStream(json_encode([
            'order' => [
                'ids'         => [$this->kanban_artifact_ids[3]],
                'direction'   => 'after',
                'compared_to' => $this->kanban_artifact_ids[4],
            ],
        ]))));
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            [
                $this->kanban_artifact_ids[4],
                $this->kanban_artifact_ids[3],
            ],
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testGETItems
     */
    public function testPATCHItemsWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/items?column_id=' . REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID;

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $url)->withBody($this->stream_factory->createStream(json_encode([
                'order' => [
                    'ids'         => [$this->kanban_artifact_ids[3]],
                    'direction'   => 'after',
                    'compared_to' => $this->kanban_artifact_ids[4],
                ],
            ]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    private function getIdsOrderedByPriority($uri)
    {
        $response     = json_decode($this->getResponse($this->request_factory->createRequest('GET', $uri))->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $actual_order = [];
        $collection   = $response['collection'];

        foreach ($collection as $kanban_backlog_item) {
            $actual_order[] = $kanban_backlog_item['id'];
        }

        return $actual_order;
    }

    public function testGETArchive(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/archive';

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        $this->assertGETArchive($response);
    }

    public function testGETArchiveWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/archive';

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', $url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETArchive($response);
    }

    private function assertGETArchive(\Psr\Http\Message\ResponseInterface $response): void
    {
        $response_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(2, $response_json['total_size']);
        $this->assertEquals('Something archived', $response_json['collection'][0]['label']);
        $this->assertEquals('Something archived v2', $response_json['collection'][1]['label']);
    }

    /**
     * @depends testGETArchive
     */
    public function testPATCHArchive(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/archive';

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', $url)->withBody($this->stream_factory->createStream(json_encode([
            'order' => [
                'ids'         => [$this->kanban_artifact_ids[5]],
                'direction'   => 'after',
                'compared_to' => $this->kanban_artifact_ids[6],
            ],
        ]))));
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            [
                $this->kanban_artifact_ids[6],
                $this->kanban_artifact_ids[5],
            ],
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testGETArchive
     */
    public function testPATCHArchiveWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/archive';

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $url)->withBody($this->stream_factory->createStream(json_encode([
                'order' => [
                    'ids'         => [$this->kanban_artifact_ids[5]],
                    'direction'   => 'after',
                    'compared_to' => $this->kanban_artifact_ids[6],
                ],
            ]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testPATCHArchive
     */
    public function testPATCHBacklogWithAdd()
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/backlog';

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', $url)->withBody($this->stream_factory->createStream(json_encode([
            'add' => [
                'ids' => [$this->kanban_artifact_ids[6]],
            ],
        ]))));
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            [
                $this->kanban_artifact_ids[2],
                $this->kanban_artifact_ids[1],
                $this->kanban_artifact_ids[6],
            ],
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testPATCHBacklogWithAdd
     */
    public function testPATCHColumnWithAddAndOrder()
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/items?column_id=' . REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID;

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', $url)->withBody($this->stream_factory->createStream(json_encode([
            'add' => [
                'ids' => [$this->kanban_artifact_ids[6]],
            ],
            'order' => [
                'ids'         => [$this->kanban_artifact_ids[6]],
                'direction'   => 'after',
                'compared_to' => $this->kanban_artifact_ids[4],
            ],
        ]))));
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            [
                $this->kanban_artifact_ids[4],
                $this->kanban_artifact_ids[6],
                $this->kanban_artifact_ids[3],
            ],
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testPATCHColumnWithAddAndOrder
     */
    public function testPATCHArchiveWithAddAndOrder()
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/archive';

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', $url)->withBody($this->stream_factory->createStream(json_encode([
            'add' => [
                'ids' => [$this->kanban_artifact_ids[6]],
            ],
            'order' => [
                'ids'         => [$this->kanban_artifact_ids[6]],
                'direction'   => 'after',
                'compared_to' => $this->kanban_artifact_ids[5],
            ],
        ]))));
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            [
                $this->kanban_artifact_ids[5],
                $this->kanban_artifact_ids[6],
            ],
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testPATCHArchiveWithAddAndOrder
     */
    public function testPOSTKanbanColumn()
    {
        $data = json_encode([
            'label' => 'objective',
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('POST', 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/columns')->withBody($this->stream_factory->createStream($data)));

        $this->assertEquals(201, $response->getStatusCode());

        $response_get = $this->getResponse($this->request_factory->createRequest('GET', 'kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $kanban       = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals('objective', $kanban['columns'][3]['label']);

        return $kanban['columns'][3]['id'];
    }

    public function testPOSTKanbanColumnWithReadOnlyAdmin(): void
    {
        $data = json_encode([
            'label' => 'objective',
        ]);

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/columns')->withBody($this->stream_factory->createStream($data)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testPOSTKanbanColumn
     */
    public function testPUTKanbanColumn($new_column_id)
    {
        $data = json_encode([
            $new_column_id,
            REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID,
            REST_TestDataBuilder::KANBAN_TO_BE_DONE_COLUMN_ID,
            REST_TestDataBuilder::KANBAN_REVIEW_COLUMN_ID,
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/columns')->withBody($this->stream_factory->createStream($data)));

        $this->assertEquals(200, $response->getStatusCode());

        $response_get = $this->getResponse($this->request_factory->createRequest('GET', 'kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $kanban       = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($kanban['columns'][0]['id'], $new_column_id);
        $this->assertEquals($kanban['columns'][1]['id'], REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID);
        $this->assertEquals($kanban['columns'][2]['id'], REST_TestDataBuilder::KANBAN_TO_BE_DONE_COLUMN_ID);
        $this->assertEquals($kanban['columns'][3]['id'], REST_TestDataBuilder::KANBAN_REVIEW_COLUMN_ID);
    }

    /**
     * @depends testPOSTKanbanColumn
     */
    public function testPUTKanbanColumnWithReadOnlyAdmin($new_column_id): void
    {
        $data = json_encode([
            $new_column_id,
            REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID,
            REST_TestDataBuilder::KANBAN_TO_BE_DONE_COLUMN_ID,
            REST_TestDataBuilder::KANBAN_REVIEW_COLUMN_ID,
        ]);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/columns')->withBody($this->stream_factory->createStream($data)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testPOSTKanbanColumn
     */
    public function testDELETEKanbanColumns()
    {
        $url = 'kanban_columns/' . REST_TestDataBuilder::KANBAN_REVIEW_COLUMN_ID . '?kanban_id=' . REST_TestDataBuilder::KANBAN_ID;

        $response = $this->getResponse($this->request_factory->createRequest('DELETE', $url));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testPOSTKanbanColumn
     */
    public function testDELETEKanbanColumnsWithReadOnlyAdmin(): void
    {
        $url = 'kanban_columns/' . REST_TestDataBuilder::KANBAN_REVIEW_COLUMN_ID . '?kanban_id=' . REST_TestDataBuilder::KANBAN_ID;

        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', $url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testPUTKanbanColumn
     */
    public function testOPTIONSKanbanItems()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'kanban_items'));
        $this->assertEquals(['OPTIONS', 'GET', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSKanbanItemsWithReadOnlyAdmin()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'kanban_items'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    /**
     * @depends testPATCHArchiveWithAddAndOrder
     */
    public function testPOSTKanbanItemsInBacklog(): void
    {
        $url = 'kanban_items/';

        $response = $this->getResponse($this->request_factory->createRequest('POST', $url)->withBody($this->stream_factory->createStream(json_encode([
            "item" => [
                "label"     => "New item in backlog",
                "kanban_id" => REST_TestDataBuilder::KANBAN_ID,
            ],
        ]))));

        $this->assertEquals(201, $response->getStatusCode());

        $item = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals("New item in backlog", $item['label']);
        $this->assertEquals('backlog', $item['in_column']);
    }

    /**
     * @depends testPATCHArchiveWithAddAndOrder
     */
    public function testPOSTKanbanItemsWithReadOnlyAdmin(): void
    {
        $url = 'kanban_items/';

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', $url)->withBody($this->stream_factory->createStream(json_encode([
                "item" => [
                    "label" => "New item in backlog",
                    "kanban_id" => REST_TestDataBuilder::KANBAN_ID,
                ],
            ]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

        /**
     * @depends testPOSTKanbanItemsInBacklog
     */
    public function testPOSTKanbanItemsInColmun()
    {
        $url = 'kanban_items/';

        $response = $this->getResponse($this->request_factory->createRequest('POST', $url)->withBody($this->stream_factory->createStream(json_encode([
            "item" => [
                "label"     => "New item in column",
                "kanban_id" => REST_TestDataBuilder::KANBAN_ID,
                "column_id" => REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID,
            ],
        ]))));

        $this->assertEquals(201, $response->getStatusCode());

        $item = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals("New item in column", $item['label']);
        $this->assertEquals(REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID, $item['in_column']);
    }

    public function testGETKanbanItem(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'kanban_items/' . $this->kanban_artifact_ids[1]));

        $this->assertGETKanbanItems($response);
    }

    public function testGETKanbanItemWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'kanban_items/' . $this->kanban_artifact_ids[1]),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETKanbanItems($response);
    }

    private function assertGETKanbanItems(\Psr\Http\Message\ResponseInterface $response): void
    {
        $this->assertEquals(200, $response->getStatusCode());

        $item = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Do something', $item['label']);
        $this->assertEquals('backlog', $item['in_column']);
    }

    /**
     * @depends testPOSTKanbanItemsInColmun
     */
    public function testPOSTKanbanItemsInUnknowColmun()
    {
        $url = 'kanban_items/';

        $response = $this->getResponse($this->request_factory->createRequest('POST', $url)->withBody($this->stream_factory->createStream(json_encode([
            "item" => [
                "label"     => "New item in column",
                "kanban_id" => REST_TestDataBuilder::KANBAN_ID,
                "column_id" => 99999999,
            ],
        ]))));

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testPOSTKanbanItemsInUnknowColmun
     */
    public function testOPTIONSTrackerReports()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/tracker_reports'));
        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSTrackerReportsWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/tracker_reports'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testPUTTrackerReports()
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/tracker_reports';

        $response = $this->getResponse($this->request_factory->createRequest('PUT', $url)->withBody($this->stream_factory->createStream(json_encode([
            "tracker_report_ids" => [$this->tracker_report_id],
        ]))));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPUTTrackerReportsWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/tracker_reports';

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', $url)->withBody($this->stream_factory->createStream(json_encode([
                "tracker_report_ids" => [$this->tracker_report_id],
            ]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals((403), $response->getStatusCode());
    }

    public function testPUTTrackerReportsWithEmptyArray()
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/tracker_reports';

        $response = $this->getResponse($this->request_factory->createRequest('PUT', $url)->withBody($this->stream_factory->createStream(json_encode([
            "tracker_report_ids" => [],
        ]))));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testPUTTrackerReportsWithEmptyArray
     */
    public function testPUTTrackerReportsThrowsExceptionOnReportThatDoesNotExist()
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/tracker_reports';

        $response = $this->getResponse($this->request_factory->createRequest('PUT', $url)->withBody($this->stream_factory->createStream(json_encode([
            "tracker_report_ids" => [-1],
        ]))));
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @depends testDELETEKanbanWithReadOnlyAdmin
     */
    public function testDELETEKanban()
    {
        $response = $this->getResponse($this->request_factory->createRequest('DELETE', 'kanban/' . REST_TestDataBuilder::KANBAN_ID));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testPUTTrackerReportsThrowsExceptionOnReportThatDoesNotExist
     */
    public function testDELETEKanbanWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'kanban/' . REST_TestDataBuilder::KANBAN_ID),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testDELETEKanban
     */
    public function testGETDeletedKanban()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'kanban/' . REST_TestDataBuilder::KANBAN_ID));

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGETCumulativeFlowInvalidDate()
    {
        $url = 'kanban/' . DataBuilder::KANBAN_CUMULATIVE_FLOW_ID . '/cumulative_flow?' . http_build_query(
            [
                'start_date'             => '2016-09-29',
                'end_date'               => '2016-09-28',
                'interval_between_point' => 1,
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGETCumulativeFlowTooMuchPointsRequested()
    {
        $url = 'kanban/' . DataBuilder::KANBAN_CUMULATIVE_FLOW_ID . '/cumulative_flow?' . http_build_query(
            [
                'start_date'             => '2011-04-19',
                'end_date'               => '2016-09-29',
                'interval_between_point' => 1,
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGETCumulativeFlow(): void
    {
        $url = 'kanban/' . DataBuilder::KANBAN_CUMULATIVE_FLOW_ID . '/cumulative_flow?' . http_build_query(
            [
                'start_date'             => '2016-09-22',
                'end_date'               => '2016-09-28',
                'interval_between_point' => 1,
            ]
        );

        $response = $this->getResponse($this->request_factory->createRequest('GET', $url));

        $this->assertGETCumulativeFlow($response);
    }

    public function testGETCumulativeFlowWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . DataBuilder::KANBAN_CUMULATIVE_FLOW_ID . '/cumulative_flow?' . http_build_query(
            [
                'start_date' => '2016-09-22',
                'end_date' => '2016-09-28',
                'interval_between_point' => 1,
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', $url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETCumulativeFlow($response);
    }

    private function assertGETCumulativeFlow(\Psr\Http\Message\ResponseInterface $response): void
    {
        $item = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(200, $response->getStatusCode());
        $columns = $item['columns'];
        $this->assertEquals(5, count($columns));

        $archive_column = $columns[0];
        $this->assertEquals('Archive', $archive_column['label']);
        $this->assertEquals([
            [
                'start_date'         => '2016-09-22',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-23',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-24',
                'kanban_items_count' => 1,
            ],
            [
                'start_date'         => '2016-09-25',
                'kanban_items_count' => 1,
            ],
            [
                'start_date'         => '2016-09-26',
                'kanban_items_count' => 1,
            ],
            [
                'start_date'         => '2016-09-27',
                'kanban_items_count' => 2,
            ],
            [
                'start_date'         => '2016-09-28',
                'kanban_items_count' => 3,
            ],
        ], $archive_column['values']);

        $open3_column = $columns[1];
        $this->assertEquals('Open3', $open3_column['label']);
        $this->assertEquals([
            [
                'start_date'         => '2016-09-22',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-23',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-24',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-25',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-26',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-27',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-28',
                'kanban_items_count' => 0,
            ],
        ], $open3_column['values']);

        $open2_column = $columns[2];
        $this->assertEquals('Open2', $open2_column['label']);
        $this->assertEquals([
            [
                'start_date'         => '2016-09-22',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-23',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-24',
                'kanban_items_count' => 1,
            ],
            [
                'start_date'         => '2016-09-25',
                'kanban_items_count' => 1,
            ],
            [
                'start_date'         => '2016-09-26',
                'kanban_items_count' => 1,
            ],
            [
                'start_date'         => '2016-09-27',
                'kanban_items_count' => 1,
            ],
            [
                'start_date'         => '2016-09-28',
                'kanban_items_count' => 0,
            ],
        ], $open2_column['values']);

        $open1_column = $columns[3];
        $this->assertEquals('Open1', $open1_column['label']);
        $this->assertEquals([
            [
                'start_date'         => '2016-09-22',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-23',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-24',
                'kanban_items_count' => 1,
            ],
            [
                'start_date'         => '2016-09-25',
                'kanban_items_count' => 1,
            ],
            [
                'start_date'         => '2016-09-26',
                'kanban_items_count' => 1,
            ],
            [
                'start_date'         => '2016-09-27',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-28',
                'kanban_items_count' => 0,
            ],
        ], $open1_column['values']);

        $backlog_column = $columns[4];
        $this->assertEquals('Backlog', $backlog_column['label']);
        $this->assertEquals([
            [
                'start_date'         => '2016-09-22',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-23',
                'kanban_items_count' => 1,
            ],
            [
                'start_date'         => '2016-09-24',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-25',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-26',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-27',
                'kanban_items_count' => 0,
            ],
            [
                'start_date'         => '2016-09-28',
                'kanban_items_count' => 0,
            ],
        ], $backlog_column['values']);
    }
}
