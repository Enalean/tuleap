<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All rights reserved
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\REST;

use Tuleap\REST\RESTTestDataBuilder;

require_once dirname(__FILE__) . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class WorkflowPostActionTest extends TestBase
{
    public function testGetBasePostAction(): void
    {
        $transition_id = $this->getTransitionId();

        $this->assertPostActionExists($transition_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetBasePostAction')]
    public function testRemoveThePostAction(): void
    {
        $transition_id = $this->getTransitionId();

        $body = json_encode([
            'post_actions' => [],
        ]);
        if ($body === false) {
            $this->fail('Body is empty');
        }

        $response = $this->getResponseByName(
            RESTTestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('PUT', "tracker_workflow_transitions/$transition_id/actions")->withBody($this->stream_factory->createStream($body))
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response_get = $this->getResponseByName(
            RESTTestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('GET', "tracker_workflow_transitions/$transition_id/actions")
        );

        $this->assertEquals(200, $response_get->getStatusCode());

        $post_actions = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEmpty($post_actions);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testRemoveThePostAction')]
    public function testCreateAddToTopBacklogPostAction(): void
    {
        $transition_id = $this->getTransitionId();

        $body = json_encode([
            'post_actions' => [
                [
                    'id' => null,
                    'type' => 'add_to_top_backlog',
                ],
            ],
        ]);
        if ($body === false) {
            $this->fail('Body is empty');
        }

        $response = $this->getResponseByName(
            RESTTestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('PUT', "tracker_workflow_transitions/$transition_id/actions")->withBody($this->stream_factory->createStream($body))
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertPostActionExists($transition_id);
    }

    private function assertPostActionExists(int $transition_id): void
    {
        $response_get = $this->getResponseByName(
            RESTTestDataBuilder::TEST_USER_1_NAME,
            $this->request_factory->createRequest('GET', "tracker_workflow_transitions/$transition_id/actions")
        );

        $this->assertEquals(200, $response_get->getStatusCode());

        $post_actions = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $post_actions);
        if (! is_array($post_actions)) {
            $this->fail('Post action is not an array');
        }
        if (! isset($post_actions[0])) {
            $this->fail('Post action is empty');
        }
        $this->assertEquals('add_to_top_backlog', $post_actions[0]['type']);
    }

    private function getTransitionId(): int
    {
        $tracker = $this->tracker_representations[$this->explicit_backlog_story_tracker_id];

        $status_field_id = 0;
        $from_value_id   = 0;
        $to_value_id     = 0;

        foreach ($tracker['fields'] as $tracker_field) {
            if ($tracker_field['name'] === 'status') {
                $status_field_id = $tracker_field['field_id'];

                foreach ($tracker_field['values'] as $field_value) {
                    if ($field_value['label'] === 'Todo') {
                        $from_value_id = $field_value['id'];
                    }

                    if ($field_value['label'] === 'On Going') {
                        $to_value_id = $field_value['id'];
                    }
                }
                break;
            }
        }

        if ($status_field_id === 0 || $from_value_id === 0 || $to_value_id === 0) {
            $this->fail();
        }

        $found_transition = null;
        foreach ($tracker['workflow']['transitions'] as $transition) {
            if ($transition['from_id'] === $from_value_id && $transition['to_id'] === $to_value_id) {
                $found_transition = $transition;
                break;
            }
        }

        if ($found_transition === null) {
            $this->fail();
        }

        return (int) $found_transition['id'];
    }
}
