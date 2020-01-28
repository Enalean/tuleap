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

use REST_TestDataBuilder;

require_once dirname(__FILE__).'/../bootstrap.php';

class WorkflowPostActionTest extends TestBase
{
    protected function getSpecificTransition(
        int $tracker_id,
        string $workflow_field_shortname,
        string $from_label,
        string $to_label
    ): array {
        $tracker = $this->tracker_representations[$tracker_id];

        $status_field_id = 0;
        $from_value_id   = 0;
        $to_value_id     = 0;

        foreach ($tracker['fields'] as $tracker_field) {
            if ($tracker_field['name'] === $workflow_field_shortname) {
                $status_field_id = $tracker_field['field_id'];

                foreach ($tracker_field['values'] as $field_value) {
                    if ($field_value['label'] === $from_label) {
                        $from_value_id = $field_value['id'];
                    }

                    if ($field_value['label'] === $to_label) {
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
        foreach ($tracker["workflow"]["transitions"] as $transition) {
            if ($transition['from_id'] === $from_value_id && $transition['to_id'] === $to_value_id) {
                $found_transition = $transition;
                break;
            }
        }

        if ($found_transition === null) {
            $this->fail();
        }

        return $found_transition;
    }

    public function testCreateAddToTopBacklogPostAction(): void
    {
        $transition = $this->getSpecificTransition(
            $this->explicit_backlog_story_tracker_id,
            'status',
            'Todo',
            'On Going'
        );

        $transition_id = $transition['id'];

        $body = json_encode([
            "post_actions" => [
                [
                    "id" => null,
                    "type" => "add_to_top_backlog"
                ]
            ]
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->put(
                "tracker_workflow_transitions/$transition_id/actions",
                null,
                $body
            )
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response_get = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get(
                "tracker_workflow_transitions/$transition_id/actions",
            )
        );

        $this->assertEquals(200, $response_get->getStatusCode());

        $post_actions = $response_get->json();
        $this->assertCount(1, $post_actions);
        $this->assertEquals('add_to_top_backlog', $post_actions[0]['type']);
    }
}
