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

use Tuleap\AgileDashboard\REST\DataBuilder;

require_once dirname(__FILE__).'/bootstrap.php';

/**
 * @group KanbanTests
 */
class KanbanTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOPTIONSKanban() {
        $response = $this->getResponse($this->client->options('kanban'));
        $this->assertEquals(array('OPTIONS'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETKanban() {
        $response = $this->getResponse($this->client->get('kanban/'. REST_TestDataBuilder::KANBAN_ID));
        $kanban   = $response->json();

        $this->assertEquals('My first kanban', $kanban['label']);
        $this->assertEquals($this->kanban_tracker_id, $kanban['tracker']['id']);

        $this->assertEquals('Archive', $kanban['archive']['label']);
        $this->assertArrayHasKey('user_can_add_in_place', $kanban['backlog']);
        $this->assertArrayHasKey('user_can_add_in_place', $kanban['columns'][0]);
        $this->assertNull($kanban['columns'][0]['limit']);
    }

    public function testPATCHKanban() {
        $this->assertThatLabelIsUpdated("Willy's really weary");
        $this->assertThatLabelIsUpdated("My first kanban"); // go back to original value
        $this->assertThatBacklogIsToggled();
        $this->assertThatArchiveIsToggled();
        $this->assertThatColumnIsToggled();
    }

    private function assertThatBacklogIsToggled() {
        $initial_state_response = $this->getResponse($this->client->get('kanban/'. REST_TestDataBuilder::KANBAN_ID));
        $initial_state_kanban   = $initial_state_response->json();
        $this->assertFalse($initial_state_kanban['backlog']['is_open']);

        $patch_response = $this->getResponse($this->client->patch(
            'kanban/'. REST_TestDataBuilder::KANBAN_ID,
            null,
            json_encode(
                array(
                    'collapse_backlog' => false
                )
            )
        ));
        $this->assertEquals($patch_response->getStatusCode(), 200);

        $new_state_response = $this->getResponse($this->client->get('kanban/'. REST_TestDataBuilder::KANBAN_ID));
        $new_state_kanban   = $new_state_response->json();
        $this->assertTrue($new_state_kanban['backlog']['is_open']);
    }

    private function assertThatArchiveIsToggled() {
        $initial_state_response = $this->getResponse($this->client->get('kanban/'. REST_TestDataBuilder::KANBAN_ID));
        $initial_state_kanban   = $initial_state_response->json();
        $this->assertFalse($initial_state_kanban['archive']['is_open']);

        $patch_response = $this->getResponse($this->client->patch(
            'kanban/'. REST_TestDataBuilder::KANBAN_ID,
            null,
            json_encode(
                array(
                    'collapse_archive' => false
                )
            )
        ));
        $this->assertEquals($patch_response->getStatusCode(), 200);

        $new_state_response = $this->getResponse($this->client->get('kanban/'. REST_TestDataBuilder::KANBAN_ID));
        $new_state_kanban   = $new_state_response->json();
        $this->assertTrue($new_state_kanban['archive']['is_open']);
    }

    private function assertThatColumnIsToggled() {
        $initial_state_response = $this->getResponse($this->client->get('kanban/'. REST_TestDataBuilder::KANBAN_ID));
        $initial_state_kanban   = $initial_state_response->json();
        $first_column = $initial_state_kanban['columns'][0];
        $this->assertTrue($first_column['is_open']);

        $patch_response = $this->getResponse($this->client->patch(
            'kanban/'. REST_TestDataBuilder::KANBAN_ID,
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

        $new_state_response = $this->getResponse($this->client->get('kanban/'. REST_TestDataBuilder::KANBAN_ID));
        $new_state_kanban   = $new_state_response->json();
        $new_state_first_column = $new_state_kanban['columns'][0];
        $this->assertFalse($new_state_first_column['is_open']);
    }

    private function assertThatLabelIsUpdated($new_label) {
        $patch_response = $this->getResponse($this->client->patch(
            'kanban/'. REST_TestDataBuilder::KANBAN_ID,
            null,
            json_encode(
                array(
                    'label' => $new_label
                )
            )
        ));
        $this->assertEquals($patch_response->getStatusCode(), 200);

        $response = $this->getResponse($this->client->get('kanban/'. REST_TestDataBuilder::KANBAN_ID));
        $kanban   = $response->json();

        $this->assertEquals($new_label, $kanban['label']);

    }

    public function testGETBacklog() {
        $url = 'kanban/'. REST_TestDataBuilder::KANBAN_ID .'/backlog';

        $response = $this->getResponse($this->client->get($url))->json();

        $this->assertEquals(2, $response['total_size']);
        $this->assertEquals('Do something', $response['collection'][0]['label']);
        $this->assertEquals('Do something v2', $response['collection'][1]['label']);
    }

    /**
     * @depends testGETBacklog
     */
    public function testPATCHBacklog() {
        $url = 'kanban/'. REST_TestDataBuilder::KANBAN_ID.'/backlog';

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                'order' => array(
                    'ids'         => array($this->kanban_artifact_ids[1]),
                    'direction'   => 'after',
                    'compared_to' => REST_TestDataBuilder::KANBAN_ARTIFACT_ID_2
                )
            ))
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                REST_TestDataBuilder::KANBAN_ARTIFACT_ID_2,
                $this->kanban_artifact_ids[1],
            ),
            $this->getIdsOrderedByPriority($url)
        );
    }

    public function testGETItems() {
        $url = 'kanban/'. REST_TestDataBuilder::KANBAN_ID .'/items?column_id='. REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID;

        $response = $this->getResponse($this->client->get($url))->json();

        $this->assertEquals(2, $response['total_size']);
        $this->assertEquals('Doing something', $response['collection'][0]['label']);
        $this->assertEquals('Doing something v2', $response['collection'][1]['label']);
        $this->assertArrayHasKey('timeinfo', $response['collection'][0]);
        $this->assertArrayHasKey('timeinfo', $response['collection'][1]);
    }

    /**
     * @depends testGETItems
     */
    public function testPATCHItems() {
        $url = 'kanban/'. REST_TestDataBuilder::KANBAN_ID.'/items?column_id='. REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID;

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                'order' => array(
                    'ids'         => array(REST_TestDataBuilder::KANBAN_ARTIFACT_ID_3),
                    'direction'   => 'after',
                    'compared_to' => REST_TestDataBuilder::KANBAN_ARTIFACT_ID_4
                )
            ))
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                REST_TestDataBuilder::KANBAN_ARTIFACT_ID_4,
                REST_TestDataBuilder::KANBAN_ARTIFACT_ID_3,
            ),
            $this->getIdsOrderedByPriority($url)
        );
    }

    private function getIdsOrderedByPriority($uri) {
        $response     = $this->getResponse($this->client->get($uri))->json();
        $actual_order = array();
        $collection   = $response['collection'];

        foreach($collection as $kanban_backlog_item) {
            $actual_order[] = $kanban_backlog_item['id'];
        }

        return $actual_order;
    }

    public function testGETArchive() {
        $url = 'kanban/'. REST_TestDataBuilder::KANBAN_ID .'/archive';

        $response = $this->getResponse($this->client->get($url))->json();

        $this->assertEquals(2, $response['total_size']);
        $this->assertEquals('Something archived', $response['collection'][0]['label']);
        $this->assertEquals('Something archived v2', $response['collection'][1]['label']);
    }

    /**
     * @depends testGETArchive
     */
    public function testPATCHArchive() {
        $url = 'kanban/'. REST_TestDataBuilder::KANBAN_ID.'/archive';

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                'order' => array(
                    'ids'         => array(REST_TestDataBuilder::KANBAN_ARTIFACT_ID_5),
                    'direction'   => 'after',
                    'compared_to' => REST_TestDataBuilder::KANBAN_ARTIFACT_ID_6
                )
            ))
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                REST_TestDataBuilder::KANBAN_ARTIFACT_ID_6,
                REST_TestDataBuilder::KANBAN_ARTIFACT_ID_5,
            ),
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testPATCHArchive
     */
    public function testPATCHBacklogWithAdd() {
        $url = 'kanban/'. REST_TestDataBuilder::KANBAN_ID.'/backlog';

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                'add' => array(
                    'ids' => array(REST_TestDataBuilder::KANBAN_ARTIFACT_ID_6)
                )
            ))
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                REST_TestDataBuilder::KANBAN_ARTIFACT_ID_2,
                $this->kanban_artifact_ids[1],
                REST_TestDataBuilder::KANBAN_ARTIFACT_ID_6
            ),
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testPATCHBacklogWithAdd
     */
    public function testPATCHColumnWithAddAndOrder() {
        $url = 'kanban/'. REST_TestDataBuilder::KANBAN_ID.'/items?column_id='. REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID;

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                'add' => array(
                    'ids' => array(REST_TestDataBuilder::KANBAN_ARTIFACT_ID_6)
                ),
                'order' => array(
                    'ids'         => array(REST_TestDataBuilder::KANBAN_ARTIFACT_ID_6),
                    'direction'   => 'after',
                    'compared_to' => REST_TestDataBuilder::KANBAN_ARTIFACT_ID_4
                )
            ))
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                REST_TestDataBuilder::KANBAN_ARTIFACT_ID_4,
                REST_TestDataBuilder::KANBAN_ARTIFACT_ID_6,
                REST_TestDataBuilder::KANBAN_ARTIFACT_ID_3
            ),
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testPATCHColumnWithAddAndOrder
     */
    public function testPATCHArchiveWithAddAndOrder() {
        $url = 'kanban/'. REST_TestDataBuilder::KANBAN_ID.'/archive';

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                'add' => array(
                    'ids' => array(REST_TestDataBuilder::KANBAN_ARTIFACT_ID_6)
                ),
                'order' => array(
                    'ids'         => array(REST_TestDataBuilder::KANBAN_ARTIFACT_ID_6),
                    'direction'   => 'after',
                    'compared_to' => REST_TestDataBuilder::KANBAN_ARTIFACT_ID_5
                )
            ))
        ));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                REST_TestDataBuilder::KANBAN_ARTIFACT_ID_5,
                REST_TestDataBuilder::KANBAN_ARTIFACT_ID_6
            ),
            $this->getIdsOrderedByPriority($url)
        );
    }

    /**
     * @depends testPATCHArchiveWithAddAndOrder
     */
    public function testPOSTKanbanColumn() {
        $data = json_encode(array(
            'label' => 'objective'
        ));

        $response = $this->getResponse($this->client->post(
            'kanban/'. REST_TestDataBuilder::KANBAN_ID.'/columns',
            null,
            $data
        ));

        $this->assertEquals($response->getStatusCode(), 201);

        $response_get = $this->getResponse($this->client->get('kanban/'. REST_TestDataBuilder::KANBAN_ID));
        $kanban       = $response_get->json();

        $this->assertEquals($kanban['columns'][3]['label'], 'objective');

        return $kanban['columns'][3]['id'];
    }



    /**
     * @depends testPOSTKanbanColumn
     */
    public function testPUTKanbanColumn($new_column_id) {
        $data = json_encode(array(
            $new_column_id,
            REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID,
            REST_TestDataBuilder::KANBAN_TO_BE_DONE_COLUMN_ID,
            REST_TestDataBuilder::KANBAN_REVIEW_COLUMN_ID,
        ));

        $response = $this->getResponse($this->client->put(
            'kanban/'. REST_TestDataBuilder::KANBAN_ID.'/columns',
            null,
            $data
        ));

        $this->assertEquals($response->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('kanban/'. REST_TestDataBuilder::KANBAN_ID));
        $kanban       = $response_get->json();

        $this->assertEquals($kanban['columns'][0]['id'], $new_column_id);
        $this->assertEquals($kanban['columns'][1]['id'], REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID);
        $this->assertEquals($kanban['columns'][2]['id'], REST_TestDataBuilder::KANBAN_TO_BE_DONE_COLUMN_ID);
        $this->assertEquals($kanban['columns'][3]['id'], REST_TestDataBuilder::KANBAN_REVIEW_COLUMN_ID);
    }

    /**
     * @depends testPOSTKanbanColumn
     */
    public function testDELETEKanbanColumns() {
        $url = 'kanban_columns/'. REST_TestDataBuilder::KANBAN_REVIEW_COLUMN_ID.'?kanban_id='. REST_TestDataBuilder::KANBAN_ID;

        $response = $this->getResponse($this->client->delete($url, null));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testPUTKanbanColumn
     */
    public function testOPTIONSKanbanItems() {
        $response = $this->getResponse($this->client->options('kanban_items'));
        $this->assertEquals(array('OPTIONS', 'GET', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @depends testPATCHArchiveWithAddAndOrder
     */
    public function testPOSTKanbanItemsInBacklog() {
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
     * @depends testPOSTKanbanItemsInBacklog
     */
    public function testPOSTKanbanItemsInColmun() {
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

    public function testGETKanbanItem() {
        $response = $this->getResponse($this->client->get('kanban_items/' . $this->kanban_artifact_ids[1]));
        $item = $response->json();

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($item['label'], 'Do something');
        $this->assertEquals($item['in_column'], 'backlog');
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     * @depends testPOSTKanbanItemsInColmun
     */
    public function testPOSTKanbanItemsInUnknowColmun() {
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
    public function testDELETEKanban() {
        $response = $this->getResponse($this->client->delete('kanban/'. REST_TestDataBuilder::KANBAN_ID));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     * @depends testDELETEKanban
     */
    public function testGETDeletedKanban() {
        $response = $this->getResponse($this->client->get('kanban/'. REST_TestDataBuilder::KANBAN_ID));

        $this->assertEquals($response->getStatusCode(), 404);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGETCumulativeFlowInvalidDate()
    {
        $url = 'kanban/' . DataBuilder::KANBAN_CUMULATIVE_FLOW_ID . '/cumulative_flow?' . http_build_query(
                array(
                    'start_date'             => '2016-09-29',
                    'end_date'               => '2016-09-28',
                    'interval_between_point' => 1
                ));

        $response = $this->getResponse($this->client->get($url));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGETCumulativeFlowTooMuchPointsRequested()
    {
        $url = 'kanban/' . DataBuilder::KANBAN_CUMULATIVE_FLOW_ID . '/cumulative_flow?' . http_build_query(
                array(
                    'start_date'             => '2011-04-19',
                    'end_date'               => '2016-09-29',
                    'interval_between_point' => 1
                ));

        $response = $this->getResponse($this->client->get($url));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testGETCumulativeFlow()
    {
        $url = 'kanban/' . DataBuilder::KANBAN_CUMULATIVE_FLOW_ID . '/cumulative_flow?' . http_build_query(
                array(
                    'start_date'             => '2016-09-22',
                    'end_date'               => '2016-09-28',
                    'interval_between_point' => 1
                ));

        $response      = $this->getResponse($this->client->get($url));
        $item          = $response->json();

        $this->assertEquals($response->getStatusCode(), 200);
        $columns = $item['columns'];
        $this->assertEquals(5 , count($columns));

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
