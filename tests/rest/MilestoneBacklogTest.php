<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOPTIONSBacklog() {
        $response = $this->getResponse($this->client->options('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETBacklog() {
        $response = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog'));

        $backlog_items = $response->json();

        $this->assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Hughhhhhhh");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => TestDataBuilder::STORY_3_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::STORY_3_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::USER_STORIES_TRACKER_ID)));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Kill you");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact'], array('id' => TestDataBuilder::STORY_4_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::STORY_4_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::USER_STORIES_TRACKER_ID)));

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertEquals($third_backlog_item['label'], "Back");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact'], array('id' => TestDataBuilder::STORY_5_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::STORY_5_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::USER_STORIES_TRACKER_ID)));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPUTBacklogWithAllIds() {
        $response_put = $this->getResponse($this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog', null, '['.TestDataBuilder::STORY_5_ARTIFACT_ID.','.TestDataBuilder::STORY_3_ARTIFACT_ID.','.TestDataBuilder::STORY_4_ARTIFACT_ID.']'));
        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog'));
        $backlog_items = $response_get->json();
        $this->assertCount(3, $backlog_items);
        $this->assertEquals($backlog_items[0]['artifact'], array('id' => TestDataBuilder::STORY_5_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::STORY_5_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::USER_STORIES_TRACKER_ID)));
        $this->assertEquals($backlog_items[1]['artifact'], array('id' => TestDataBuilder::STORY_3_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::STORY_3_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::USER_STORIES_TRACKER_ID)));
        $this->assertEquals($backlog_items[2]['artifact'], array('id' => TestDataBuilder::STORY_4_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::STORY_4_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::USER_STORIES_TRACKER_ID)));
    }

    public function testPUTBacklogWithSomeIds() {
        $response_put = $this->getResponse($this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog', null, '['.TestDataBuilder::STORY_4_ARTIFACT_ID.','.TestDataBuilder::STORY_3_ARTIFACT_ID.']'));
        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/backlog'));
        $backlog_items = $response_get->json();
        $this->assertCount(3, $backlog_items);
        $this->assertEquals($backlog_items[0]['artifact'], array('id' => TestDataBuilder::STORY_5_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::STORY_5_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::USER_STORIES_TRACKER_ID)));
        $this->assertEquals($backlog_items[1]['artifact'], array('id' => TestDataBuilder::STORY_4_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::STORY_4_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::USER_STORIES_TRACKER_ID)));
        $this->assertEquals($backlog_items[2]['artifact'], array('id' => TestDataBuilder::STORY_3_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::STORY_3_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::USER_STORIES_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::USER_STORIES_TRACKER_ID)));
    }
}
