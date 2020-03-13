<?php
/**
 * Copyright (c) Enalean, 2014-2017. All rights reserved
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

use Guzzle\Http\Message\Response;
use Tuleap\AgileDashboard\REST\DataBuilder;
use Tuleap\AgileDashboard\REST\TestBase;

require_once dirname(__FILE__) . '/bootstrap.php';

/**
 * @group KanbanTests
 */
final class KanbanTest extends TestBase
{

    public function testOPTIONSKanban(): void
    {
        $response = $this->getResponse($this->client->options('kanban'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH', 'DELETE'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSKanbanWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('kanban'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(
            ['OPTIONS', 'GET', 'PATCH', 'DELETE'],
            $response->getHeader('Allow')->normalize()->toArray()
        );
    }

    public function testGETKanban(): void
    {
        $response = $this->getResponse($this->client->get('kanban/' . REST_TestDataBuilder::KANBAN_ID));

        $this->assertGETKanban($response);
    }

    public function testGETKanbanWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get('kanban/' . REST_TestDataBuilder::KANBAN_ID),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETKanban($response);
    }

    private function assertGETKanban(Response $response): void
    {
        $kanban   = $response->json();

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
        $initial_state_response = $this->getResponse($this->client->get('kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $initial_state_kanban   = $initial_state_response->json();
        $this->assertFalse($initial_state_kanban['backlog']['is_open']);

        $patch_response = $this->getResponse($this->client->patch(
            'kanban/' . REST_TestDataBuilder::KANBAN_ID,
            null,
            json_encode(
                array(
                    'collapse_backlog' => false
                )
            )
        ));
        $this->assertEquals($patch_response->getStatusCode(), 200);

        $new_state_response = $this->getResponse($this->client->get('kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $new_state_kanban   = $new_state_response->json();
        $this->assertTrue($new_state_kanban['backlog']['is_open']);
    }

    private function assertThatArchiveIsToggled()
    {
        $initial_state_response = $this->getResponse($this->client->get('kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $initial_state_kanban   = $initial_state_response->json();
        $this->assertFalse($initial_state_kanban['archive']['is_open']);

        $patch_response = $this->getResponse($this->client->patch(
            'kanban/' . REST_TestDataBuilder::KANBAN_ID,
            null,
            json_encode(
                array(
                    'collapse_archive' => false
                )
            )
        ));
        $this->assertEquals($patch_response->getStatusCode(), 200);

        $new_state_response = $this->getResponse($this->client->get('kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $new_state_kanban   = $new_state_response->json();
        $this->assertTrue($new_state_kanban['archive']['is_open']);
    }

    private function assertThatColumnIsToggled()
    {
        $initial_state_response = $this->getResponse($this->client->get('kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $initial_state_kanban   = $initial_state_response->json();
        $first_column = $initial_state_kanban['columns'][0];
        $this->assertTrue($first_column['is_open']);

        $patch_response = $this->getResponse($this->client->patch(
            'kanban/' . REST_TestDataBuilder::KANBAN_ID,
            null,
            json_encode(
                array(
                    'collapse_column' => array(
                        'column_id' => $first_column['id'],
                        'value'     => true
                    )
                )
            )
        ));
        $this->assertEquals($patch_response->getStatusCode(), 200);

        $new_state_response = $this->getResponse($this->client->get('kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $new_state_kanban   = $new_state_response->json();
        $new_state_first_column = $new_state_kanban['columns'][0];
        $this->assertFalse($new_state_first_column['is_open']);
    }

    private function assertThatLabelIsUpdated($new_label)
    {
        $patch_response = $this->getResponse($this->client->patch(
            'kanban/' . REST_TestDataBuilder::KANBAN_ID,
            null,
            json_encode(
                array(
                    'label' => $new_label
                )
            )
        ));
        $this->assertEquals($patch_response->getStatusCode(), 200);

        $response = $this->getResponse($this->client->get('kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $kanban   = $response->json();

        $this->assertEquals($new_label, $kanban['label']);
    }

    public function testPATCHKanbanWithReadOnlyAdmin()
    {
        $patch_response = $this->getResponse(
            $this->client->patch(
                'kanban/' . REST_TestDataBuilder::KANBAN_ID,
                null,
                json_encode(
                    array(
                        'label' => 'SomeNewLabel'
                    )
                )
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($patch_response->getStatusCode(), 403);
    }

    public function testGETBacklog(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/backlog';

        $response = $this->getResponse($this->client->get($url));

        $this->assertGETBacklog($response);
    }

    public function testGETBacklogWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/backlog';

        $response = $this->getResponse(
            $this->client->get($url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETBacklog($response);
    }

    private function assertGETBacklog(Response $response): void
    {
        $response_json = $response->json();

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

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                'order' => array(
                    'ids'         => array($this->kanban_artifact_ids[1]),
                    'direction'   => 'after',
                    'compared_to' => $this->kanban_artifact_ids[2]
                )
            ))
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->kanban_artifact_ids[2],
                $this->kanban_artifact_ids[1],
            ),
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
            $this->client->patch(
                $url,
                null,
                json_encode(array(
                    'order' => array(
                        'ids'         => array($this->kanban_artifact_ids[1]),
                        'direction'   => 'after',
                        'compared_to' => $this->kanban_artifact_ids[2]
                    )
                ))
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testGETItems(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/items?column_id=' . REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID;

        $response = $this->getResponse($this->client->get($url));

        $this->assertGETItems($response);
    }

    public function testGETItemsWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/items?column_id=' . REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID;

        $response = $this->getResponse(
            $this->client->get($url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETItems($response);
    }

    private function assertGETItems(Response $response): void
    {
        $response_json = $response->json();

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

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                'order' => array(
                    'ids'         => array($this->kanban_artifact_ids[3]),
                    'direction'   => 'after',
                    'compared_to' => $this->kanban_artifact_ids[4]
                )
            ))
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->kanban_artifact_ids[4],
                $this->kanban_artifact_ids[3],
            ),
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
            $this->client->patch(
                $url,
                null,
                json_encode(array(
                    'order' => array(
                        'ids'         => array($this->kanban_artifact_ids[3]),
                        'direction'   => 'after',
                        'compared_to' => $this->kanban_artifact_ids[4]
                    )
                ))
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 403);
    }

    private function getIdsOrderedByPriority($uri)
    {
        $response     = $this->getResponse($this->client->get($uri))->json();
        $actual_order = array();
        $collection   = $response['collection'];

        foreach ($collection as $kanban_backlog_item) {
            $actual_order[] = $kanban_backlog_item['id'];
        }

        return $actual_order;
    }

    public function testGETArchive(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/archive';

        $response = $this->getResponse($this->client->get($url));

        $this->assertGETArchive($response);
    }

    public function testGETArchiveWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/archive';

        $response = $this->getResponse(
            $this->client->get($url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETArchive($response);
    }

    private function assertGETArchive(Response $response): void
    {
        $response_json = $response->json();

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

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                'order' => array(
                    'ids'         => array($this->kanban_artifact_ids[5]),
                    'direction'   => 'after',
                    'compared_to' => $this->kanban_artifact_ids[6]
                )
            ))
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->kanban_artifact_ids[6],
                $this->kanban_artifact_ids[5],
            ),
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
            $this->client->patch(
                $url,
                null,
                json_encode(array(
                    'order' => array(
                        'ids'         => array($this->kanban_artifact_ids[5]),
                        'direction'   => 'after',
                        'compared_to' => $this->kanban_artifact_ids[6]
                    )
                ))
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 403);
    }

    /**
     * @depends testPATCHArchive
     */
    public function testPATCHBacklogWithAdd()
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/backlog';

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                'add' => array(
                    'ids' => array($this->kanban_artifact_ids[6])
                )
            ))
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->kanban_artifact_ids[2],
                $this->kanban_artifact_ids[1],
                $this->kanban_artifact_ids[6]
            ),
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testPATCHBacklogWithAdd
     */
    public function testPATCHColumnWithAddAndOrder()
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/items?column_id=' . REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID;

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                'add' => array(
                    'ids' => array($this->kanban_artifact_ids[6])
                ),
                'order' => array(
                    'ids'         => array($this->kanban_artifact_ids[6]),
                    'direction'   => 'after',
                    'compared_to' => $this->kanban_artifact_ids[4]
                )
            ))
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->kanban_artifact_ids[4],
                $this->kanban_artifact_ids[6],
                $this->kanban_artifact_ids[3]
            ),
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testPATCHColumnWithAddAndOrder
     */
    public function testPATCHArchiveWithAddAndOrder()
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/archive';

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                'add' => array(
                    'ids' => array($this->kanban_artifact_ids[6])
                ),
                'order' => array(
                    'ids'         => array($this->kanban_artifact_ids[6]),
                    'direction'   => 'after',
                    'compared_to' => $this->kanban_artifact_ids[5]
                )
            ))
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->kanban_artifact_ids[5],
                $this->kanban_artifact_ids[6]
            ),
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testPATCHArchiveWithAddAndOrder
     */
    public function testPOSTKanbanColumn()
    {
        $data = json_encode(array(
            'label' => 'objective'
        ));

        $response = $this->getResponse($this->client->post(
            'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/columns',
            null,
            $data
        ));

        $this->assertEquals($response->getStatusCode(), 201);

        $response_get = $this->getResponse($this->client->get('kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $kanban       = $response_get->json();

        $this->assertEquals($kanban['columns'][3]['label'], 'objective');

        return $kanban['columns'][3]['id'];
    }

    public function testPOSTKanbanColumnWithReadOnlyAdmin(): void
    {
        $data = json_encode(array(
            'label' => 'objective'
        ));

        $response = $this->getResponse(
            $this->client->post(
                'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/columns',
                null,
                $data
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 403);
    }



    /**
     * @depends testPOSTKanbanColumn
     */
    public function testPUTKanbanColumn($new_column_id)
    {
        $data = json_encode(array(
            $new_column_id,
            REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID,
            REST_TestDataBuilder::KANBAN_TO_BE_DONE_COLUMN_ID,
            REST_TestDataBuilder::KANBAN_REVIEW_COLUMN_ID,
        ));

        $response = $this->getResponse($this->client->put(
            'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/columns',
            null,
            $data
        ));

        $this->assertEquals($response->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('kanban/' . REST_TestDataBuilder::KANBAN_ID));
        $kanban       = $response_get->json();

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
        $data = json_encode(array(
            $new_column_id,
            REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID,
            REST_TestDataBuilder::KANBAN_TO_BE_DONE_COLUMN_ID,
            REST_TestDataBuilder::KANBAN_REVIEW_COLUMN_ID,
        ));

        $response = $this->getResponse(
            $this->client->put(
                'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/columns',
                null,
                $data
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 403);
    }

    /**
     * @depends testPOSTKanbanColumn
     */
    public function testDELETEKanbanColumns()
    {
        $url = 'kanban_columns/' . REST_TestDataBuilder::KANBAN_REVIEW_COLUMN_ID . '?kanban_id=' . REST_TestDataBuilder::KANBAN_ID;

        $response = $this->getResponse($this->client->delete($url, null));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testPOSTKanbanColumn
     */
    public function testDELETEKanbanColumnsWithReadOnlyAdmin(): void
    {
        $url = 'kanban_columns/' . REST_TestDataBuilder::KANBAN_REVIEW_COLUMN_ID . '?kanban_id=' . REST_TestDataBuilder::KANBAN_ID;

        $response = $this->getResponse(
            $this->client->delete($url, null),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 403);
    }

    /**
     * @depends testPUTKanbanColumn
     */
    public function testOPTIONSKanbanItems()
    {
        $response = $this->getResponse($this->client->options('kanban_items'));
        $this->assertEquals(array('OPTIONS', 'GET', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSKanbanItemsWithReadOnlyAdmin()
    {
        $response = $this->getResponse(
            $this->client->options('kanban_items'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'GET', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @depends testPATCHArchiveWithAddAndOrder
     */
    public function testPOSTKanbanItemsInBacklog(): void
    {
        $url = 'kanban_items/';

        $response = $this->getResponse($this->client->post(
            $url,
            null,
            json_encode(array(
                "item" => array(
                    "label"     => "New item in backlog",
                    "kanban_id" => REST_TestDataBuilder::KANBAN_ID
                )
            ))
        ));

        $this->assertEquals($response->getStatusCode(), 201);

        $item = $response->json();
        $this->assertEquals($item['label'], "New item in backlog");
        $this->assertEquals($item['in_column'], 'backlog');
    }

    /**
     * @depends testPATCHArchiveWithAddAndOrder
     */
    public function testPOSTKanbanItemsWithReadOnlyAdmin(): void
    {
        $url = 'kanban_items/';

        $response = $this->getResponse(
            $this->client->post(
                $url,
                null,
                json_encode(array(
                    "item" => array(
                        "label" => "New item in backlog",
                        "kanban_id" => REST_TestDataBuilder::KANBAN_ID
                    )
                ))
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 403);
    }

        /**
     * @depends testPOSTKanbanItemsInBacklog
     */
    public function testPOSTKanbanItemsInColmun()
    {
        $url = 'kanban_items/';

        $response = $this->getResponse($this->client->post(
            $url,
            null,
            json_encode(array(
                "item" => array(
                    "label"     => "New item in column",
                    "kanban_id" => REST_TestDataBuilder::KANBAN_ID,
                    "column_id" => REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID,
                )
            ))
        ));

        $this->assertEquals($response->getStatusCode(), 201);

        $item = $response->json();
        $this->assertEquals($item['label'], "New item in column");
        $this->assertEquals($item['in_column'], REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID);
    }

    public function testGETKanbanItem(): void
    {
        $response = $this->getResponse($this->client->get('kanban_items/' . $this->kanban_artifact_ids[1]));

        $this->assertGETKanbanItems($response);
    }

    public function testGETKanbanItemWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get('kanban_items/' . $this->kanban_artifact_ids[1]),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETKanbanItems($response);
    }

    private function assertGETKanbanItems(Response $response): void
    {
        $this->assertEquals($response->getStatusCode(), 200);

        $item = $response->json();
        $this->assertEquals($item['label'], 'Do something');
        $this->assertEquals($item['in_column'], 'backlog');
    }

    /**
     * @depends testPOSTKanbanItemsInColmun
     */
    public function testPOSTKanbanItemsInUnknowColmun()
    {
        $url = 'kanban_items/';

        $response = $this->getResponse($this->client->post(
            $url,
            null,
            json_encode(array(
                "item" => array(
                    "label"     => "New item in column",
                    "kanban_id" => REST_TestDataBuilder::KANBAN_ID,
                    "column_id" => 99999999,
                )
            ))
        ));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @depends testPOSTKanbanItemsInUnknowColmun
     */
    public function testOPTIONSTrackerReports()
    {
        $response = $this->getResponse($this->client->options('kanban/' . REST_TestDataBuilder::KANBAN_ID . '/tracker_reports'));
        $this->assertEquals(array('OPTIONS', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSTrackerReportsWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('kanban/' . REST_TestDataBuilder::KANBAN_ID . '/tracker_reports'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testPUTTrackerReports()
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/tracker_reports';

        $response = $this->getResponse($this->client->put(
            $url,
            null,
            json_encode(array(
                "tracker_report_ids" => array($this->tracker_report_id)
            ))
        ));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPUTTrackerReportsWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/tracker_reports';

        $response = $this->getResponse(
            $this->client->put(
                $url,
                null,
                json_encode(array(
                    "tracker_report_ids" => array($this->tracker_report_id)
                ))
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), (403));
    }

    public function testPUTTrackerReportsWithEmptyArray()
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/tracker_reports';

        $response = $this->getResponse($this->client->put(
            $url,
            null,
            json_encode(array(
                "tracker_report_ids" => array()
            ))
        ));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testPUTTrackerReportsWithEmptyArray
     */
    public function testPUTTrackerReportsThrowsExceptionOnReportThatDoesNotExist()
    {
        $url = 'kanban/' . REST_TestDataBuilder::KANBAN_ID . '/tracker_reports';

        $response = $this->getResponse($this->client->put(
            $url,
            null,
            json_encode(array(
                "tracker_report_ids" => array(-1)
            ))
        ));
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @depends testDELETEKanbanWithReadOnlyAdmin
     */
    public function testDELETEKanban()
    {
        $response = $this->getResponse($this->client->delete('kanban/' . REST_TestDataBuilder::KANBAN_ID));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testPUTTrackerReportsThrowsExceptionOnReportThatDoesNotExist
     */
    public function testDELETEKanbanWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->delete('kanban/' . REST_TestDataBuilder::KANBAN_ID),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 403);
    }

    /**
     * @depends testDELETEKanban
     */
    public function testGETDeletedKanban()
    {
        $response = $this->getResponse($this->client->get('kanban/' . REST_TestDataBuilder::KANBAN_ID));

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testGETCumulativeFlowInvalidDate()
    {
        $url = 'kanban/' . DataBuilder::KANBAN_CUMULATIVE_FLOW_ID . '/cumulative_flow?' . http_build_query(
            array(
                    'start_date'             => '2016-09-29',
                    'end_date'               => '2016-09-28',
                    'interval_between_point' => 1
            )
        );

        $response = $this->getResponse($this->client->get($url));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testGETCumulativeFlowTooMuchPointsRequested()
    {
        $url = 'kanban/' . DataBuilder::KANBAN_CUMULATIVE_FLOW_ID . '/cumulative_flow?' . http_build_query(
            array(
                    'start_date'             => '2011-04-19',
                    'end_date'               => '2016-09-29',
                    'interval_between_point' => 1
            )
        );

        $response = $this->getResponse($this->client->get($url));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testGETCumulativeFlow(): void
    {
        $url = 'kanban/' . DataBuilder::KANBAN_CUMULATIVE_FLOW_ID . '/cumulative_flow?' . http_build_query(
            array(
                    'start_date'             => '2016-09-22',
                    'end_date'               => '2016-09-28',
                    'interval_between_point' => 1
            )
        );

        $response = $this->getResponse($this->client->get($url));

        $this->assertGETCumulativeFlow($response);
    }

    public function testGETCumulativeFlowWithReadOnlyAdmin(): void
    {
        $url = 'kanban/' . DataBuilder::KANBAN_CUMULATIVE_FLOW_ID . '/cumulative_flow?' . http_build_query(
            array(
                    'start_date' => '2016-09-22',
                    'end_date' => '2016-09-28',
                    'interval_between_point' => 1
            )
        );

        $response = $this->getResponse(
            $this->client->get($url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETCumulativeFlow($response);
    }

    private function assertGETCumulativeFlow(Response $response): void
    {
        $item = $response->json();
        $this->assertEquals($response->getStatusCode(), 200);
        $columns = $item['columns'];
        $this->assertEquals(5, count($columns));

        $archive_column = $columns[0];
        $this->assertEquals('Archive', $archive_column['label']);
        $this->assertEquals(array(
            array(
                'start_date'         => '2016-09-22',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-23',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-24',
                'kanban_items_count' => 1
            ),
            array(
                'start_date'         => '2016-09-25',
                'kanban_items_count' => 1
            ),
            array(
                'start_date'         => '2016-09-26',
                'kanban_items_count' => 1
            ),
            array(
                'start_date'         => '2016-09-27',
                'kanban_items_count' => 2
            ),
            array(
                'start_date'         => '2016-09-28',
                'kanban_items_count' => 3
            )
        ), $archive_column['values']);

        $open3_column = $columns[1];
        $this->assertEquals('Open3', $open3_column['label']);
        $this->assertEquals(array(
            array(
                'start_date'         => '2016-09-22',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-23',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-24',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-25',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-26',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-27',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-28',
                'kanban_items_count' => 0
            )
        ), $open3_column['values']);

        $open2_column = $columns[2];
        $this->assertEquals('Open2', $open2_column['label']);
        $this->assertEquals(array(
            array(
                'start_date'         => '2016-09-22',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-23',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-24',
                'kanban_items_count' => 1
            ),
            array(
                'start_date'         => '2016-09-25',
                'kanban_items_count' => 1
            ),
            array(
                'start_date'         => '2016-09-26',
                'kanban_items_count' => 1
            ),
            array(
                'start_date'         => '2016-09-27',
                'kanban_items_count' => 1
            ),
            array(
                'start_date'         => '2016-09-28',
                'kanban_items_count' => 0
            )
        ), $open2_column['values']);

        $open1_column = $columns[3];
        $this->assertEquals('Open1', $open1_column['label']);
        $this->assertEquals(array(
            array(
                'start_date'         => '2016-09-22',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-23',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-24',
                'kanban_items_count' => 1
            ),
            array(
                'start_date'         => '2016-09-25',
                'kanban_items_count' => 1
            ),
            array(
                'start_date'         => '2016-09-26',
                'kanban_items_count' => 1
            ),
            array(
                'start_date'         => '2016-09-27',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-28',
                'kanban_items_count' => 0
            )
        ), $open1_column['values']);

        $backlog_column = $columns[4];
        $this->assertEquals('Backlog', $backlog_column['label']);
        $this->assertEquals(array(
            array(
                'start_date'         => '2016-09-22',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-23',
                'kanban_items_count' => 1
            ),
            array(
                'start_date'         => '2016-09-24',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-25',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-26',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-27',
                'kanban_items_count' => 0
            ),
            array(
                'start_date'         => '2016-09-28',
                'kanban_items_count' => 0
            )
        ), $backlog_column['values']);
    }
}
