<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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
use Tuleap\REST\MilestoneBase;

/**
 * @group MilestonesTest
 */
class MilestonesBacklogTest extends MilestoneBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function testOPTIONSBacklog(): void
    {
        $response = $this->getResponse($this->client->options('milestones/' . $this->release_artifact_ids[1] . '/backlog'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PUT', 'POST', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSBacklogWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->options('milestones/' . $this->release_artifact_ids[1] . '/backlog'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'PUT', 'POST', 'PATCH'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETBacklog(): void
    {
        $response = $this->getResponse($this->client->get('milestones/' . $this->release_artifact_ids[1] . '/backlog'));

        $this->assertBacklog($response);
    }

    public function testGETBacklogWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->get('milestones/' . $this->release_artifact_ids[1] . '/backlog'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertBacklog($response);
    }

    private function assertBacklog(Response $response): void
    {
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

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPUTBacklogForbiddenForRESTReadOnlyUserNotInvolvedInProject(): void
    {
        $response_put = $this->getResponse(
            $this->client->put(
                'milestones/' . $this->release_artifact_ids[1] . '/backlog',
                null,
                '[]'
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_put->getStatusCode());
    }

    public function testPUTBacklogWithAllIds(): void
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

    public function testPUTBacklogWithoutPermission(): void
    {
        $response_put = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->client->put('milestones/' . $this->release_artifact_ids[1] . '/backlog', null, '[' . $this->story_artifact_ids[4] . ',' . $this->story_artifact_ids[5] . ',' . $this->story_artifact_ids[3] . ']'));
        $this->assertEquals($response_put->getStatusCode(), 403);

        $response_get = $this->getResponse($this->client->get('milestones/' . $this->release_artifact_ids[1] . '/backlog'));
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

    public function testPUTBacklogWithSomeIds(): void
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

    public function testPOSTBacklogForbiddenForRESTReadOnlyUserNotInvolvedInProject(): void
    {
        $post = [
            'artifact' => array('id' => $this->story_artifact_ids[6])
        ];

        $response_post = $this->getResponse(
            $this->client->post(
                'milestones/' . $this->release_artifact_ids[1] . '/backlog',
                null,
                $post
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_post->getStatusCode());
    }

    public function testPOSTBacklogAppendsId(): void
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

        $response_get = $this->getResponse($this->client->get('milestones/' . $this->release_artifact_ids[1] . '/backlog'));
        $backlog_items = $response_get->json();
        $last_item     = count($backlog_items) - 1;

        $this->assertEquals($backlog_items[$last_item]['artifact']['id'], $this->story_artifact_ids[6]);
        $this->assertEquals($backlog_items[$last_item]['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[6]);
        $this->assertEquals($backlog_items[$last_item]['artifact']['tracker']['id'], $this->user_stories_tracker_id);
    }

    public function testPOSTBacklogWithoutPermissions(): void
    {
        $post = array(
            'artifact' => array('id' => $this->story_artifact_ids[6])
        );
        $response_post = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->client->post(
                'milestones/' . $this->release_artifact_ids[1] . '/backlog',
                null,
                json_encode($post)
            )
        );
        $this->assertEquals($response_post->getStatusCode(), 403);
    }
}
