<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All rights reserved
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

require_once dirname(__FILE__).'/../lib/autoload.php';

/**
 * @group MilestonesTest
 */
class MilestonesBacklogTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOPTIONSBacklog() {
        $response = $this->getResponse($this->client->options('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PUT', 'POST', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETBacklog() {
        $response = $this->getResponse($this->client->get('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog'));

        $backlog_items = $response->json();

        $this->assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertArrayHasKey('accept', $first_backlog_item);
        $this->assertArrayHasKey('trackers', $first_backlog_item['accept']);
        $this->assertEquals($first_backlog_item['accept']['trackers'][0]['id'], $this->tasks_tracker_id);
        $this->assertEquals($first_backlog_item['accept']['trackers'][0]['uri'], 'trackers/' .  $this->tasks_tracker_id);
        $this->assertEquals($first_backlog_item['label'], "Hughhhhhhh");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => REST_TestDataBuilder::STORY_3_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_3_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'label' => 'User Stories')));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertArrayHasKey('accept', $second_backlog_item);
        $this->assertArrayHasKey('trackers', $second_backlog_item['accept']);
        $this->assertEquals($second_backlog_item['accept']['trackers'][0]['id'],  $this->tasks_tracker_id);
        $this->assertEquals($second_backlog_item['accept']['trackers'][0]['uri'], 'trackers/' .  $this->tasks_tracker_id);
        $this->assertEquals($second_backlog_item['label'], "Kill you");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact'], array('id' => REST_TestDataBuilder::STORY_4_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_4_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'label' => 'User Stories')));

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertArrayHasKey('accept', $third_backlog_item);
        $this->assertArrayHasKey('trackers', $third_backlog_item['accept']);
        $this->assertEquals($third_backlog_item['accept']['trackers'][0]['id'],  $this->tasks_tracker_id);
        $this->assertEquals($third_backlog_item['accept']['trackers'][0]['uri'], 'trackers/' .  $this->tasks_tracker_id);
        $this->assertEquals($third_backlog_item['label'], "Back");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact'], array('id' => REST_TestDataBuilder::STORY_5_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_5_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'label' => 'User Stories')));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPUTBacklogWithAllIds() {
        $response_put = $this->getResponse($this->client->put('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog', null, '['.REST_TestDataBuilder::STORY_5_ARTIFACT_ID.','.REST_TestDataBuilder::STORY_3_ARTIFACT_ID.','.REST_TestDataBuilder::STORY_4_ARTIFACT_ID.']'));
        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog'));
        $backlog_items = $response_get->json();
        $this->assertCount(3, $backlog_items);
        $this->assertEquals($backlog_items[0]['artifact'], array('id' => REST_TestDataBuilder::STORY_5_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_5_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'label' => 'User Stories')));
        $this->assertEquals($backlog_items[1]['artifact'], array('id' => REST_TestDataBuilder::STORY_3_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_3_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'label' => 'User Stories')));
        $this->assertEquals($backlog_items[2]['artifact'], array('id' => REST_TestDataBuilder::STORY_4_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_4_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'label' => 'User Stories')));
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPUTBacklogWithoutPermission() {
        $response_put = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->client->put('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog', null, '['.REST_TestDataBuilder::STORY_4_ARTIFACT_ID.','.REST_TestDataBuilder::STORY_5_ARTIFACT_ID.','.REST_TestDataBuilder::STORY_3_ARTIFACT_ID.']'));
        $this->assertEquals($response_put->getStatusCode(), 403);

        $response_get = $this->getResponse($this->client->get('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog'));
        $backlog_items = $response_get->json();
        $this->assertCount(3, $backlog_items);
        $this->assertEquals($backlog_items[0]['artifact'], array(
            'id' => REST_TestDataBuilder::STORY_5_ARTIFACT_ID,
            'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_5_ARTIFACT_ID,
            'tracker' => array(
                'id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID,
                'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID,
                'label' => 'User Stories'
            )
        ));
        $this->assertEquals($backlog_items[1]['artifact'], array(
            'id' => REST_TestDataBuilder::STORY_3_ARTIFACT_ID,
            'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_3_ARTIFACT_ID,
            'tracker' => array(
                'id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID,
                'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID,
                'label' => 'User Stories'
            )
        ));
        $this->assertEquals($backlog_items[2]['artifact'], array(
            'id' => REST_TestDataBuilder::STORY_4_ARTIFACT_ID,
            'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_4_ARTIFACT_ID,
            'tracker' => array(
                'id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID,
                'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID,
                'label' => 'User Stories'
            )
        ));
    }

    public function testPUTBacklogWithSomeIds() {
        $response_put = $this->getResponse($this->client->put('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog', null, '['.REST_TestDataBuilder::STORY_4_ARTIFACT_ID.','.REST_TestDataBuilder::STORY_3_ARTIFACT_ID.']'));
        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog'));
        $backlog_items = $response_get->json();
        $this->assertCount(3, $backlog_items);
        $this->assertEquals($backlog_items[0]['artifact'], array('id' => REST_TestDataBuilder::STORY_5_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_5_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'label' => 'User Stories')));
        $this->assertEquals($backlog_items[1]['artifact'], array('id' => REST_TestDataBuilder::STORY_4_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_4_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'label' => 'User Stories')));
        $this->assertEquals($backlog_items[2]['artifact'], array('id' => REST_TestDataBuilder::STORY_3_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_3_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'label' => 'User Stories')));
    }

    public function testPOSTBacklogAppendsId() {
        $post = array(
            'artifact' => array('id' => REST_TestDataBuilder::STORY_6_ARTIFACT_ID)
        );
        $response_post = $this->getResponse(
            $this->client->post(
                'milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog',
                null,
                json_encode($post)
            )
        );
        $this->assertEquals($response_post->getStatusCode(), 201);

        $response_get = $this->getResponse($this->client->get('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog'));
        $backlog_items = $response_get->json();
        $last_item = count($backlog_items) -1;
        $this->assertEquals($backlog_items[$last_item]['artifact'], array('id' => REST_TestDataBuilder::STORY_6_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::STORY_6_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::USER_STORIES_TRACKER_ID, 'label' => 'User Stories')));
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPOSTBacklogWithoutPermissions() {
        $post = array(
            'artifact' => array('id' => REST_TestDataBuilder::STORY_6_ARTIFACT_ID)
        );
        $response_post = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->client->post(
                'milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog',
                null,
                json_encode($post)
            )
        );
        $this->assertEquals($response_post->getStatusCode(), 403);
    }
}
