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

use Tuleap\REST\MilestoneBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('MilestonesTest')]
class MilestonesBacklogTest extends MilestoneBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function testOPTIONSBacklog(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'milestones/' . $this->release_artifact_ids[1] . '/backlog'));
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PUT', 'POST', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSBacklogWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'milestones/' . $this->release_artifact_ids[1] . '/backlog'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PUT', 'POST', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETBacklog(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/backlog'));
        $backlog  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(3, $backlog);
        $this->assertFirstThreeElementsOfBacklog($response, $backlog);
    }

    public function testGETBacklogWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/backlog'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $backlog  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(3, $backlog);
        $this->assertFirstThreeElementsOfBacklog($response, $backlog);
    }

    public function testGETBacklogWithAllItems(): void
    {
        $query    = json_encode(['status' => 'all']);
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/backlog?query=' . urlencode($query))
        );

        $backlog_items = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(4, $backlog_items);
        $this->assertFirstThreeElementsOfBacklog($response, $backlog_items);

        $fourth_backlog_item = $backlog_items[3];
        $this->assertArrayHasKey('id', $fourth_backlog_item);
        $this->assertArrayHasKey('accept', $fourth_backlog_item);
        $this->assertArrayHasKey('trackers', $fourth_backlog_item['accept']);
        $this->assertEquals($fourth_backlog_item['accept']['trackers'][0]['id'], $this->tasks_tracker_id);
        $this->assertEquals($fourth_backlog_item['accept']['trackers'][0]['uri'], 'trackers/' . $this->tasks_tracker_id);
        $this->assertEquals($fourth_backlog_item['label'], 'Closed Story');
        $this->assertEquals($fourth_backlog_item['status'], 'Closed');
        $this->assertEquals($fourth_backlog_item['artifact']['id'], $this->story_artifact_ids[12]);
        $this->assertEquals($fourth_backlog_item['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[12]);
        $this->assertEquals($fourth_backlog_item['artifact']['tracker']['id'], $this->user_stories_tracker_id);
    }

    private function assertFirstThreeElementsOfBacklog(\Psr\Http\Message\ResponseInterface $response, array $backlog_items): void
    {
        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertArrayHasKey('accept', $first_backlog_item);
        $this->assertArrayHasKey('trackers', $first_backlog_item['accept']);
        $this->assertEquals($first_backlog_item['accept']['trackers'][0]['id'], $this->tasks_tracker_id);
        $this->assertEquals($first_backlog_item['accept']['trackers'][0]['uri'], 'trackers/' . $this->tasks_tracker_id);
        $this->assertEquals($first_backlog_item['label'], 'Hughhhhhhh');
        $this->assertEquals($first_backlog_item['status'], 'Open');
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
        $this->assertEquals($second_backlog_item['label'], 'Kill you');
        $this->assertEquals($second_backlog_item['status'], 'Open');
        $this->assertEquals($second_backlog_item['artifact']['id'], $this->story_artifact_ids[4]);
        $this->assertEquals($second_backlog_item['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[4]);
        $this->assertEquals($second_backlog_item['artifact']['tracker']['id'], $this->user_stories_tracker_id);

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertArrayHasKey('accept', $third_backlog_item);
        $this->assertArrayHasKey('trackers', $third_backlog_item['accept']);
        $this->assertEquals($third_backlog_item['accept']['trackers'][0]['id'], $this->tasks_tracker_id);
        $this->assertEquals($third_backlog_item['accept']['trackers'][0]['uri'], 'trackers/' . $this->tasks_tracker_id);
        $this->assertEquals($third_backlog_item['label'], 'Back');
        $this->assertEquals($third_backlog_item['status'], 'Open');
        $this->assertEquals($third_backlog_item['artifact']['id'], $this->story_artifact_ids[5]);
        $this->assertEquals($third_backlog_item['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[5]);
        $this->assertEquals($third_backlog_item['artifact']['tracker']['id'], $this->user_stories_tracker_id);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPUTBacklogForbiddenForRESTReadOnlyUserNotInvolvedInProject(): void
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/backlog'
            )->withBody($this->stream_factory->createStream('[]')),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_put->getStatusCode());
    }

    public function testPUTBacklogWithAllIds(): void
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/backlog'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->story_artifact_ids[5] . ',' . $this->story_artifact_ids[3] . ',' . $this->story_artifact_ids[4] . ']'
                )
            )
        );
        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get  = $this->getResponse(
            $this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/backlog')
        );
        $backlog_items = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
        $response_put = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/backlog'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->story_artifact_ids[4] . ',' . $this->story_artifact_ids[5] . ',' . $this->story_artifact_ids[3] . ']'
                )
            )
        );
        $this->assertEquals($response_put->getStatusCode(), 403);

        $response_get  = $this->getResponse($this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/backlog'));
        $backlog_items = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/backlog'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->story_artifact_ids[4] . ',' . $this->story_artifact_ids[3] . ']'
                )
            )
        );
        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get  = $this->getResponse(
            $this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/backlog')
        );
        $backlog_items = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
            'artifact' => ['id' => $this->story_artifact_ids[6]],
        ];

        $response_post = $this->getResponse(
            $this->request_factory->createRequest(
                'POST',
                'milestones/' . $this->release_artifact_ids[1] . '/backlog'
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode($post, JSON_THROW_ON_ERROR)
                )
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_post->getStatusCode());
    }

    public function testPOSTBacklogAppendsId(): void
    {
        $post          = [
            'artifact' => ['id' => $this->story_artifact_ids[6]],
        ];
        $response_post = $this->getResponse(
            $this->request_factory->createRequest(
                'POST',
                'milestones/' . $this->release_artifact_ids[1] . '/backlog'
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode($post)
                )
            )
        );
        $this->assertEquals($response_post->getStatusCode(), 201);

        $response_get  = $this->getResponse($this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/backlog'));
        $backlog_items = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $last_item     = count($backlog_items) - 1;

        $this->assertEquals($backlog_items[$last_item]['artifact']['id'], $this->story_artifact_ids[6]);
        $this->assertEquals($backlog_items[$last_item]['artifact']['uri'], 'artifacts/' . $this->story_artifact_ids[6]);
        $this->assertEquals($backlog_items[$last_item]['artifact']['tracker']['id'], $this->user_stories_tracker_id);
    }

    public function testPOSTBacklogWithoutPermissions(): void
    {
        $post          = [
            'artifact' => ['id' => $this->story_artifact_ids[6]],
        ];
        $response_post = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest(
                'POST',
                'milestones/' . $this->release_artifact_ids[1] . '/backlog'
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode($post)
                )
            )
        );
        $this->assertEquals($response_post->getStatusCode(), 403);
    }
}
