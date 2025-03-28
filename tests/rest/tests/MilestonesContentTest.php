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
class MilestonesContentTest extends MilestoneBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function testOPTIONSContent(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'milestones/' . $this->release_artifact_ids[1] . '/content'));
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PUT', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSContentWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'milestones/' . $this->release_artifact_ids[1] . '/content'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PUT', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETContent(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/content'));

        $this->assertGETContent($response);
    }

    public function testGETContentWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/content'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETContent($response);
    }

    private function assertGETContent(\Psr\Http\Message\ResponseInterface $response): void
    {
        $content_items = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(4, $content_items);

        $first_content_item = $content_items[0];
        $this->assertArrayHasKey('id', $first_content_item);
        $this->assertEquals($first_content_item['label'], 'First epic');
        $this->assertEquals($first_content_item['status'], 'Open');
        $this->assertEquals($first_content_item['artifact']['id'], $this->epic_artifact_ids[1]);
        $this->assertEquals($first_content_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[1]);
        $this->assertEquals($first_content_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $second_content_item = $content_items[1];
        $this->assertArrayHasKey('id', $second_content_item);
        $this->assertEquals($second_content_item['label'], 'Second epic');
        $this->assertEquals($second_content_item['status'], 'Closed');
        $this->assertEquals($second_content_item['artifact']['id'], $this->epic_artifact_ids[2]);
        $this->assertEquals($second_content_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[2]);
        $this->assertEquals($second_content_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $third_content_item = $content_items[2];
        $this->assertArrayHasKey('id', $third_content_item);
        $this->assertEquals($third_content_item['label'], 'Third epic');
        $this->assertEquals($third_content_item['status'], 'Closed');
        $this->assertEquals($third_content_item['artifact']['id'], $this->epic_artifact_ids[3]);
        $this->assertEquals($third_content_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[3]);
        $this->assertEquals($third_content_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $fourth_content_item = $content_items[3];
        $this->assertArrayHasKey('id', $fourth_content_item);
        $this->assertEquals($fourth_content_item['label'], 'Fourth epic');
        $this->assertEquals($fourth_content_item['status'], 'Open');
        $this->assertEquals($fourth_content_item['artifact']['id'], $this->epic_artifact_ids[4]);
        $this->assertEquals($fourth_content_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[4]);
        $this->assertEquals($fourth_content_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPUTContent(): void
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/content'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->epic_artifact_ids[1] . ',' . $this->epic_artifact_ids[4] . ']'
                )
            )
        );

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get  = $this->getResponse(
            $this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/content')
        );
        $backlog_items = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], 'First epic');
        $this->assertEquals($first_backlog_item['status'], 'Open');
        $this->assertEquals($first_backlog_item['artifact']['id'], $this->epic_artifact_ids[1]);
        $this->assertEquals($first_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[1]);
        $this->assertEquals($first_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], 'Fourth epic');
        $this->assertEquals($second_backlog_item['status'], 'Open');
        $this->assertEquals($second_backlog_item['artifact']['id'], $this->epic_artifact_ids[4]);
        $this->assertEquals($second_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[4]);
        $this->assertEquals($second_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPUTContent')]
    public function testPUTContentWithSameValueAsPreviouslyReturns200(): void
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/content'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->epic_artifact_ids[1] . ',' . $this->epic_artifact_ids[4] . ']'
                )
            )
        );

        $this->assertEquals($response_put->getStatusCode(), 200);
        $this->assertEquals($response_put->getBody()->getContents(), '');
    }

    public function testPUTContentWithoutPermission(): void
    {
        $response_put = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/content'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->epic_artifact_ids[4] . ',' . $this->epic_artifact_ids[1] . ']'
                )
            )
        );

        $this->assertEquals($response_put->getStatusCode(), 403);
        $this->assertArrayHasKey('error', json_decode($response_put->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testPUTContentWithRESTReadOnlyUserNotInvolvedInProject(): void
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/content'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->epic_artifact_ids[4] . ',' . $this->epic_artifact_ids[1] . ']'
                )
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_put->getStatusCode());
        $this->assertArrayHasKey('error', json_decode($response_put->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testPUTContentOnlyOneElement(): void
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/content'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->epic_artifact_ids[4] . ']'
                )
            )
        );

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get  = $this->getResponse(
            $this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/content')
        );
        $backlog_items = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], 'Fourth epic');
        $this->assertEquals($first_backlog_item['status'], 'Open');
        $this->assertEquals($first_backlog_item['artifact']['id'], $this->epic_artifact_ids[4]);
        $this->assertEquals($first_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[4]);
        $this->assertEquals($first_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/content'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->epic_artifact_ids[1] . ',' . $this->epic_artifact_ids[4] . ']'
                )
            )
        );
    }
}
