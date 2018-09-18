<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All rights reserved
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

use Tuleap\REST\MilestoneBase;

/**
 * @group MilestonesTest
 */
class MilestonesBacklogTest extends MilestoneBase
{
    public function testOPTIONSBacklog()
    {
        $response = $this->getResponse($this->client->options('milestones/'.$this->release_artifact_ids[1].'/backlog'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PUT', 'POST', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETBacklog()
    {
        $response = $this->getResponse($this->client->get('milestones/' . $this->release_artifact_ids[1] . '/backlog'));

        $backlog_items = $response->json();

        $this->assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertArrayHasKey('accept', $first_backlog_item);
        $this->assertArrayHasKey('trackers', $first_backlog_item['accept']);
        $this->assertEquals($first_backlog_item['accept']['trackers'][0]['id'], $this->tasks_tracker_id);
        $this->assertEquals($first_backlog_item['accept']['trackers'][0]['uri'], 'trackers/' . $this->tasks_tracker_id);
        $this->assertEquals($first_backlog_item['label'], "Hughhhhhhh");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact']['id'], $this->story_artifact_ids[3]);
        $this->assertEquals($first_backlog_item['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[3]);
        $this->assertEquals($first_backlog_item['artifact']['tracker']['id'], $this->user_stories_tracker_id);

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertArrayHasKey('accept', $second_backlog_item);
        $this->assertArrayHasKey('trackers', $second_backlog_item['accept']);
        $this->assertEquals($second_backlog_item['accept']['trackers'][0]['id'], $this->tasks_tracker_id);
        $this->assertEquals(
            $second_backlog_item['accept']['trackers'][0]['uri'],
            'trackers/' . $this->tasks_tracker_id
        );
        $this->assertEquals($second_backlog_item['label'], "Kill you");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact']['id'], $this->story_artifact_ids[4]);
        $this->assertEquals($second_backlog_item['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[4]);
        $this->assertEquals($second_backlog_item['artifact']['tracker']['id'], $this->user_stories_tracker_id);

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertArrayHasKey('accept', $third_backlog_item);
        $this->assertArrayHasKey('trackers', $third_backlog_item['accept']);
        $this->assertEquals($third_backlog_item['accept']['trackers'][0]['id'], $this->tasks_tracker_id);
        $this->assertEquals($third_backlog_item['accept']['trackers'][0]['uri'], 'trackers/' . $this->tasks_tracker_id);
        $this->assertEquals($third_backlog_item['label'], "Back");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact']['id'], $this->story_artifact_ids[5]);
        $this->assertEquals($third_backlog_item['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[5]);
        $this->assertEquals($third_backlog_item['artifact']['tracker']['id'], $this->user_stories_tracker_id);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPUTBacklogWithAllIds()
    {
        $response_put = $this->getResponse(
            $this->client->put(
                'milestones/' . $this->release_artifact_ids[1] . '/backlog',
                null,
                '[' . $this->story_artifact_ids[5] . ',' . $this->story_artifact_ids[3] . ',' . $this->story_artifact_ids[4] . ']'
            )
        );
        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get  = $this->getResponse(
            $this->client->get('milestones/' . $this->release_artifact_ids[1] . '/backlog')
        );
        $backlog_items = $response_get->json();
        $this->assertCount(3, $backlog_items);

        $this->assertEquals($backlog_items[0]['artifact']['id'], $this->story_artifact_ids[5]);
        $this->assertEquals($backlog_items[0]['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[5]);
        $this->assertEquals($backlog_items[0]['artifact']['tracker']['id'], $this->user_stories_tracker_id);

        $this->assertEquals($backlog_items[1]['artifact']['id'], $this->story_artifact_ids[3]);
        $this->assertEquals($backlog_items[1]['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[3]);
        $this->assertEquals($backlog_items[1]['artifact']['tracker']['id'], $this->user_stories_tracker_id);

        $this->assertEquals($backlog_items[2]['artifact']['id'], $this->story_artifact_ids[4]);
        $this->assertEquals($backlog_items[2]['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[4]);
        $this->assertEquals($backlog_items[2]['artifact']['tracker']['id'], $this->user_stories_tracker_id);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPUTBacklogWithoutPermission()
    {
        $response_put = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->client->put('milestones/'.$this->release_artifact_ids[1].'/backlog', null, '['.$this->story_artifact_ids[4].','.$this->story_artifact_ids[5].','.$this->story_artifact_ids[3].']'));
        $this->assertEquals($response_put->getStatusCode(), 403);

        $response_get = $this->getResponse($this->client->get('milestones/'.$this->release_artifact_ids[1].'/backlog'));
        $backlog_items = $response_get->json();
        $this->assertCount(3, $backlog_items);

        $this->assertEquals($backlog_items[0]['artifact']['id'], $this->story_artifact_ids[5]);
        $this->assertEquals($backlog_items[0]['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[5]);
        $this->assertEquals($backlog_items[0]['artifact']['tracker']['id'], $this->user_stories_tracker_id);

        $this->assertEquals($backlog_items[1]['artifact']['id'], $this->story_artifact_ids[3]);
        $this->assertEquals($backlog_items[1]['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[3]);
        $this->assertEquals($backlog_items[1]['artifact']['tracker']['id'], $this->user_stories_tracker_id);

        $this->assertEquals($backlog_items[2]['artifact']['id'], $this->story_artifact_ids[4]);
        $this->assertEquals($backlog_items[2]['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[4]);
        $this->assertEquals($backlog_items[2]['artifact']['tracker']['id'], $this->user_stories_tracker_id);
    }

    public function testPUTBacklogWithSomeIds()
    {
        $response_put = $this->getResponse(
            $this->client->put(
                'milestones/' . $this->release_artifact_ids[1] . '/backlog',
                null,
                '[' . $this->story_artifact_ids[4] . ',' . $this->story_artifact_ids[3] . ']'
            )
        );
        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get  = $this->getResponse(
            $this->client->get('milestones/' . $this->release_artifact_ids[1] . '/backlog')
        );
        $backlog_items = $response_get->json();
        $this->assertCount(3, $backlog_items);
        $this->assertEquals($backlog_items[0]['artifact']['id'], $this->story_artifact_ids[5]);
        $this->assertEquals($backlog_items[0]['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[5]);
        $this->assertEquals($backlog_items[0]['artifact']['tracker']['id'], $this->user_stories_tracker_id);

        $this->assertEquals($backlog_items[1]['artifact']['id'], $this->story_artifact_ids[4]);
        $this->assertEquals($backlog_items[1]['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[4]);
        $this->assertEquals($backlog_items[1]['artifact']['tracker']['id'], $this->user_stories_tracker_id);

        $this->assertEquals($backlog_items[2]['artifact']['id'], $this->story_artifact_ids[3]);
        $this->assertEquals($backlog_items[2]['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[3]);
        $this->assertEquals($backlog_items[2]['artifact']['tracker']['id'], $this->user_stories_tracker_id);
    }

    public function testPOSTBacklogAppendsId()
    {
        $post          = array(
            'artifact' => array('id' => $this->story_artifact_ids[6])
        );
        $response_post = $this->getResponse(
            $this->client->post(
                'milestones/' . $this->release_artifact_ids[1] . '/backlog',
                null,
                json_encode($post)
            )
        );
        $this->assertEquals($response_post->getStatusCode(), 201);

        $response_get = $this->getResponse($this->client->get('milestones/'.$this->release_artifact_ids[1].'/backlog'));
        $backlog_items = $response_get->json();
        $last_item     = count($backlog_items) - 1;

        $this->assertEquals($backlog_items[$last_item]['artifact']['id'], $this->story_artifact_ids[6]);
        $this->assertEquals($backlog_items[$last_item]['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[6]);
        $this->assertEquals($backlog_items[$last_item]['artifact']['tracker']['id'], $this->user_stories_tracker_id);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPOSTBacklogWithoutPermissions()
    {
        $post = array(
            'artifact' => array('id' => $this->story_artifact_ids[6])
        );
        $response_post = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->client->post(
                'milestones/'.$this->release_artifact_ids[1].'/backlog',
                null,
                json_encode($post)
            )
        );
        $this->assertEquals($response_post->getStatusCode(), 403);
    }
}
