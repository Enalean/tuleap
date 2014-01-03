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
 * @group MilestoneTests
 */
class MilestoneTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_NAME),
            $request
        );
    }

    public function testOPTIONSBacklog() {
        $response = $this->getResponse($this->client->options('milestones/1/backlog'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETBacklog() {
        $response = $this->getResponse($this->client->get('milestones/1/backlog'));

        $backlog_items = $response->json();

        $this->assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Hughhhhhhh");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => 9, 'uri' => 'artifacts/9', 'tracker' => array('id' => 9, 'uri' => 'trackers/9')));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Kill you");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact'], array('id' => 10, 'uri' => 'artifacts/10', 'tracker' => array('id' => 9, 'uri' => 'trackers/9')));

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertEquals($third_backlog_item['label'], "Back");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact'], array('id' => 11, 'uri' => 'artifacts/11', 'tracker' => array('id' => 9, 'uri' => 'trackers/9')));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPUTBacklogWithAllIds() {
        $response_put = $this->getResponse($this->client->put('milestones/1/backlog', null, '[11,9,10]'));
        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/1/backlog'));
        $backlog_items = $response_get->json();
        $this->assertCount(3, $backlog_items);
        $this->assertEquals($backlog_items[0]['artifact'], array('id' => 11, 'uri' => 'artifacts/11', 'tracker' => array('id' => 9, 'uri' => 'trackers/9')));
        $this->assertEquals($backlog_items[1]['artifact'], array('id' => 9, 'uri' => 'artifacts/9', 'tracker' => array('id' => 9, 'uri' => 'trackers/9')));
        $this->assertEquals($backlog_items[2]['artifact'], array('id' => 10, 'uri' => 'artifacts/10', 'tracker' => array('id' => 9, 'uri' => 'trackers/9')));
    }

    public function testPUTBacklogWithSomeIds() {
        $response_put = $this->getResponse($this->client->put('milestones/1/backlog', null, '[10,9]'));
        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/1/backlog'));
        $backlog_items = $response_get->json();
        $this->assertCount(3, $backlog_items);
        $this->assertEquals($backlog_items[0]['artifact'], array('id' => 11, 'uri' => 'artifacts/11', 'tracker' => array('id' => 9, 'uri' => 'trackers/9')));
        $this->assertEquals($backlog_items[1]['artifact'], array('id' => 10, 'uri' => 'artifacts/10', 'tracker' => array('id' => 9, 'uri' => 'trackers/9')));
        $this->assertEquals($backlog_items[2]['artifact'], array('id' => 9, 'uri' => 'artifacts/9', 'tracker' => array('id' => 9, 'uri' => 'trackers/9')));
    }

    public function testOPTIONSContent() {
        $response = $this->getResponse($this->client->options('milestones/1/content'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETContent() {
        $response = $this->getResponse($this->client->get('milestones/1/content'));

        $content_items = $response->json();

        $this->assertCount(4, $content_items);

        $first_content_item = $content_items[0];
        $this->assertArrayHasKey('id', $first_content_item);
        $this->assertEquals($first_content_item['label'], "First epic");
        $this->assertEquals($first_content_item['status'], "Open");
        $this->assertEquals($first_content_item['artifact'], array('id' => 3, 'uri' => 'artifacts/3', 'tracker' => array('id' => 5, 'uri' => 'trackers/5')));

        $second_content_item = $content_items[1];
        $this->assertArrayHasKey('id', $second_content_item);
        $this->assertEquals($second_content_item['label'], "Second epic");
        $this->assertEquals($second_content_item['status'], "Closed");
        $this->assertEquals($second_content_item['artifact'], array('id' => 4, 'uri' => 'artifacts/4', 'tracker' => array('id' => 5, 'uri' => 'trackers/5')));

        $third_content_item = $content_items[2];
        $this->assertArrayHasKey('id', $third_content_item);
        $this->assertEquals($third_content_item['label'], "Third epic");
        $this->assertEquals($third_content_item['status'], "Closed");
        $this->assertEquals($third_content_item['artifact'], array('id' => 5, 'uri' => 'artifacts/5', 'tracker' => array('id' => 5, 'uri' => 'trackers/5')));

        $fourth_content_item = $content_items[3];
        $this->assertArrayHasKey('id', $fourth_content_item);
        $this->assertEquals($fourth_content_item['label'], "Fourth epic");
        $this->assertEquals($fourth_content_item['status'], "Open");
        $this->assertEquals($fourth_content_item['artifact'], array('id' => 6, 'uri' => 'artifacts/6', 'tracker' => array('id' => 5, 'uri' => 'trackers/5')));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPUTContent() {
        $response_put = $this->getResponse($this->client->put('milestones/1/content', null, '[3,6]'));

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/1/content'));
        $backlog_items = $response_get->json();

        $this->assertCount(2, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "First epic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => 3, 'uri' => 'artifacts/3', 'tracker' => array('id' => 5, 'uri' => 'trackers/5')));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Fourth epic");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact'], array('id' => 6, 'uri' => 'artifacts/6', 'tracker' => array('id' => 5, 'uri' => 'trackers/5')));
    }

    public function testPUTContentOnlyOneElement() {
        $response_put = $this->getResponse($this->client->put('milestones/1/content', null, '[6]'));

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/1/content'));
        $backlog_items = $response_get->json();

        $this->assertCount(1, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Fourth epic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => 6, 'uri' => 'artifacts/6', 'tracker' => array('id' => 5, 'uri' => 'trackers/5')));

        $this->getResponse($this->client->put('milestones/1/content', null, '[3,6]'));
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
        $this->assertCount(4,$columns);

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
        $this->assertCount(2,$swimlanes);

        $first_swimlane = $swimlanes[0];

        $first_swimlane_card = $first_swimlane['cards'][0];
        $this->assertEquals("2_7", $first_swimlane_card['id']);
        $this->assertEquals("Believe", $first_swimlane_card['label']);
        $this->assertEquals("cards/2_7", $first_swimlane_card['uri']);
        $this->assertEquals(2, $first_swimlane_card['planning_id']);
        $this->assertEquals("Open", $first_swimlane_card['status']);
        $this->assertEquals(null, $first_swimlane_card['accent_color']);
        $this->assertEquals("2", $first_swimlane_card['column_id']);
        $this->assertEquals(array(1,2,4), $first_swimlane_card['allowed_column_ids']);
        $this->assertEquals(array(), $first_swimlane_card['values']);

        $first_swimlane_card_project_reference = $first_swimlane_card['project'];
        $this->assertEquals(101, $first_swimlane_card_project_reference['id']);
        $this->assertEquals("projects/101", $first_swimlane_card_project_reference['uri']);

        $first_swimlane_card_artifact_reference = $first_swimlane_card['artifact'];
        $this->assertEquals(7, $first_swimlane_card_artifact_reference['id']);
        $this->assertEquals("artifacts/7", $first_swimlane_card_artifact_reference['uri']);

        $first_swimlane_card_artifact_tracker_reference = $first_swimlane_card_artifact_reference['tracker'];
        $this->assertEquals(9, $first_swimlane_card_artifact_tracker_reference['id']);
        $this->assertEquals("trackers/9", $first_swimlane_card_artifact_tracker_reference['uri']);
    }
}
