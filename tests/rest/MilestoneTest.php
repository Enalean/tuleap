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
 * @group MilestoneTest
 */
class MilestoneTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOPTIONS() {
        $response = $this->getResponse($this->client->options('milestones'));
        $this->assertEquals(array('OPTIONS'), $response->getHeader('Allow')->normalize()->toArray());
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

    public function testOPTIONSContent() {
        $response = $this->getResponse($this->client->options('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/content'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETContent() {
        $response = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/content'));

        $content_items = $response->json();

        $this->assertCount(4, $content_items);

        $first_content_item = $content_items[0];
        $this->assertArrayHasKey('id', $first_content_item);
        $this->assertEquals($first_content_item['label'], "First epic");
        $this->assertEquals($first_content_item['status'], "Open");
        $this->assertEquals($first_content_item['artifact'], array('id' => TestDataBuilder::EPIC_1_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_1_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));

        $second_content_item = $content_items[1];
        $this->assertArrayHasKey('id', $second_content_item);
        $this->assertEquals($second_content_item['label'], "Second epic");
        $this->assertEquals($second_content_item['status'], "Closed");
        $this->assertEquals($second_content_item['artifact'], array('id' => TestDataBuilder::EPIC_2_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_2_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));

        $third_content_item = $content_items[2];
        $this->assertArrayHasKey('id', $third_content_item);
        $this->assertEquals($third_content_item['label'], "Third epic");
        $this->assertEquals($third_content_item['status'], "Closed");
        $this->assertEquals($third_content_item['artifact'], array('id' => TestDataBuilder::EPIC_3_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_3_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));

        $fourth_content_item = $content_items[3];
        $this->assertArrayHasKey('id', $fourth_content_item);
        $this->assertEquals($fourth_content_item['label'], "Fourth epic");
        $this->assertEquals($fourth_content_item['status'], "Open");
        $this->assertEquals($fourth_content_item['artifact'], array('id' => TestDataBuilder::EPIC_4_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_4_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPUTContent() {
        $response_put = $this->getResponse($this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/content', null, '['.TestDataBuilder::EPIC_1_ARTIFACT_ID.','.TestDataBuilder::EPIC_4_ARTIFACT_ID.']'));

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/content'));
        $backlog_items = $response_get->json();

        $this->assertCount(2, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "First epic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_1_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_1_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Fourth epic");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_4_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_4_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));
    }

    public function testPUTContentOnlyOneElement() {
        $response_put = $this->getResponse($this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/content', null, '['.TestDataBuilder::EPIC_4_ARTIFACT_ID.']'));

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/content'));
        $backlog_items = $response_get->json();

        $this->assertCount(1, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Fourth epic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_4_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_4_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));

        $this->getResponse($this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/content', null, '['.TestDataBuilder::EPIC_1_ARTIFACT_ID.','.TestDataBuilder::EPIC_4_ARTIFACT_ID.']'));
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testOPTIONSCardwallOnReleaseGives404() {
        $this->getResponse($this->client->options('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/cardwall'));
    }

    public function testOPTIONSCardwallOnSprintGivesOPTIONSandGET() {
        $response = $this->getResponse($this->client->options('milestones/'.TestDataBuilder::SPRINT_ARTIFACT_ID.'/cardwall'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETCardwall() {
        $response = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::SPRINT_ARTIFACT_ID.'/cardwall'));

        $cardwall = $response->json();

        $this->assertArrayHasKey('columns', $cardwall);
        $columns = $cardwall['columns'];
        $this->assertCount(4, $columns);

        $first_column = $columns[0];
        $this->assertEquals($first_column['id'], 1);
        $this->assertEquals($first_column['label'], "To be done");
        $this->assertEquals($first_column['color'], "#F8F8F8");

        $third_column = $columns[2];
        $this->assertEquals($third_column['id'], 3);
        $this->assertEquals($third_column['label'], "Review");
        $this->assertEquals($third_column['color'], "#F8F8F8");

        $this->assertArrayHasKey('swimlanes', $cardwall);
        $swimlanes = $cardwall['swimlanes'];
        $this->assertCount(2, $swimlanes);

        $first_swimlane = $swimlanes[0];

        $first_swimlane_card = $first_swimlane['cards'][0];
        $this->assertEquals(TestDataBuilder::SPRINT_ARTIFACT_ID.'_'.TestDataBuilder::STORY_1_ARTIFACT_ID, $first_swimlane_card['id']);
        $this->assertEquals("Believe", $first_swimlane_card['label']);
        $this->assertEquals("cards/".TestDataBuilder::SPRINT_ARTIFACT_ID."_".TestDataBuilder::STORY_1_ARTIFACT_ID, $first_swimlane_card['uri']);
        $this->assertEquals(TestDataBuilder::SPRINT_ARTIFACT_ID, $first_swimlane_card['planning_id']);
        $this->assertEquals("Open", $first_swimlane_card['status']);
        $this->assertEquals(null, $first_swimlane_card['accent_color']);
        $this->assertEquals("2", $first_swimlane_card['column_id']);
        $this->assertEquals(array(1,2,4), $first_swimlane_card['allowed_column_ids']);
        $this->assertEquals(array(), $first_swimlane_card['values']);

        $first_swimlane_card_project_reference = $first_swimlane_card['project'];
        $this->assertEquals(TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, $first_swimlane_card_project_reference['id']);
        $this->assertEquals("projects/".TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, $first_swimlane_card_project_reference['uri']);

        $first_swimlane_card_artifact_reference = $first_swimlane_card['artifact'];
        $this->assertEquals(TestDataBuilder::STORY_1_ARTIFACT_ID, $first_swimlane_card_artifact_reference['id']);
        $this->assertEquals("artifacts/".TestDataBuilder::STORY_1_ARTIFACT_ID, $first_swimlane_card_artifact_reference['uri']);

        $first_swimlane_card_artifact_tracker_reference = $first_swimlane_card_artifact_reference['tracker'];
        $this->assertEquals(TestDataBuilder::USER_STORIES_TRACKER_ID, $first_swimlane_card_artifact_tracker_reference['id']);
        $this->assertEquals('trackers/'.TestDataBuilder::USER_STORIES_TRACKER_ID, $first_swimlane_card_artifact_tracker_reference['uri']);
    }

    public function testPUTRemoveSubMilestones() {
        $this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null, '['.TestDataBuilder::SPRINT_ARTIFACT_ID.']');
        $response_put = $this->getResponse($this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null, '[]'));
        $this->assertEquals($response_put->getStatusCode(), 200);
        $response_get = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null));
        $submilestones = $response_get->json();

        $this->assertCount(0, $submilestones);
    }

    public function testPUTOnlyOneSubMilestone() {
        $response_put = $this->getResponse($this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null, '['.TestDataBuilder::SPRINT_ARTIFACT_ID.']'));
        $this->assertEquals($response_put->getStatusCode(), 200);
        $response_get = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null));
        $submilestones = $response_get->json();

        $this->assertCount(1, $submilestones);
        $this->assertEquals(TestDataBuilder::SPRINT_ARTIFACT_ID, $submilestones[0]['id']);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPUTOnlyOneSubMilestoneTwice() {
        $response_put = $this->getResponse($this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null, '['.TestDataBuilder::SPRINT_ARTIFACT_ID.','.TestDataBuilder::SPRINT_ARTIFACT_ID.']'));
        $this->assertEquals($response_put->getStatusCode(), 400);
        $response_get = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null));
        $submilestones = $response_get->json();

        $this->assertCount(0, $submilestones);
    }
}
