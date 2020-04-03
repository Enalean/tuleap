<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Tests\REST\Workflows;

use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

class SimpleModeTest extends TrackerBase
{
    private function gatherWorkflowInformation(): array
    {
        $tracker = $this->tracker_representations[$this->simple_mode_workflow_tracker_id];

        $done_id       = 0;
        $closed_id     = 0;
        $open_id       = 0;
        $date_field_id = 0;
        $int_field_id  = 0;
        foreach ($tracker['fields'] as $tracker_field) {
            if ($tracker_field['name'] === 'status') {
                foreach ($tracker_field['values'] as $field_value) {
                    if ($field_value['label'] === 'Done') {
                        $done_id = $field_value['id'];
                    }

                    if ($field_value['label'] === 'Closed') {
                        $closed_id = $field_value['id'];
                    }
                    if ($field_value['label'] === 'Open') {
                        $open_id = $field_value['id'];
                    }
                }
            }
            if ($tracker_field['name'] === 'closed_date') {
                $date_field_id = $tracker_field['field_id'];
            }
            if ($tracker_field['name'] === 'points') {
                $int_field_id = $tracker_field['field_id'];
            }
        }

        if (
            $done_id === 0
            || $closed_id === 0
            || $date_field_id === 0
            || $open_id === 0
            || $int_field_id === 0
        ) {
            $this->fail();
        }

        return [
            'done_id'       => $done_id,
            'closed_id'     => $closed_id,
            'date_field_id' => $date_field_id,
            'int_field_id'  => $int_field_id
        ];
    }

    public function testPOSTTrackerWorkflowTransitions(): int
    {
        $infos = $this->gatherWorkflowInformation();

        $body     = json_encode(
            [
                'tracker_id' => $this->simple_mode_workflow_tracker_id,
                'from_id'    => $infos['done_id'],
                'to_id'      => $infos['closed_id']
            ]
        );
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->post('tracker_workflow_transitions', null, $body)
        );

        $this->assertEquals(201, $response->getStatusCode());
        $transition_reference = $response->json();
        return $transition_reference['id'];
    }

    /**
     * @depends testPOSTTrackerWorkflowTransitions
     */
    public function testCreatedPostActionDuplicatesPreConditions(int $transition_id): void
    {
        $infos = $this->gatherWorkflowInformation();
        $date_field_id = $infos['date_field_id'];
        $response      = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get("tracker_workflow_transitions/$transition_id")
        );
        $this->assertEquals(200, $response->getStatusCode());
        $pre_conditions = $response->json();

        $expected_ugroup = $this->tracker_workflows_project_id . '_3';
        $this->assertSame([$expected_ugroup], $pre_conditions['authorized_user_group_ids']);
        $this->assertTrue($pre_conditions['is_comment_required']);
        $this->assertSame([$date_field_id], $pre_conditions['not_empty_field_ids']);
    }

    /**
     * @depends testPOSTTrackerWorkflowTransitions
     */
    public function testCreatedPostActionDuplicatesPostActions(int $transition_id)
    {
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get("tracker_workflow_transitions/$transition_id/actions")
        );
        $this->assertEquals(200, $response->getStatusCode());
        $post_actions = $response->json();

        $this->assertCount(1, $post_actions);
        $this->assertSame('https://example.com/2', $post_actions[0]['job_url']);
    }

    public function testPATCHTrackerWorkflowTransitionsDuplicatesPreConditionsOnAllSiblingTransitions(): void
    {
        $infos = $this->gatherWorkflowInformation();

        $transition = $this->getSpecificTransition(
            $this->simple_mode_workflow_tracker_id,
            'status',
            'Open',
            'Done'
        );
        $transition_id         = $transition['id'];
        $project_admins_ugroup = $this->tracker_workflows_project_id . '_4';
        $date_field_id         = $infos['date_field_id'];
        $body                  = json_encode(
            [
                'authorized_user_group_ids' => [$project_admins_ugroup],
                'is_comment_required'       => true,
                'not_empty_field_ids'       => [$date_field_id]
            ]
        );

        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch("tracker_workflow_transitions/$transition_id", null, $body)
        );
        $this->assertEquals(200, $response->getStatusCode());

        $sibling_transition    = $this->getSpecificTransition(
            $this->simple_mode_workflow_tracker_id,
            'status',
            'Closed',
            'Done'
        );
        $sibling_transition_id = $sibling_transition['id'];
        $response              = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get("tracker_workflow_transitions/$sibling_transition_id")
        );
        $this->assertEquals(200, $response->getStatusCode());
        $sibling_pre_conditions = $response->json();

        $this->assertSame([$project_admins_ugroup], $sibling_pre_conditions['authorized_user_group_ids']);
        $this->assertTrue($sibling_pre_conditions['is_comment_required']);
        $this->assertSame([$date_field_id], $sibling_pre_conditions['not_empty_field_ids']);
    }

    public function testPUTTrackerWorkflowTransitionsActions(): int
    {
        $infos = $this->gatherWorkflowInformation();

        $date_field_id = $infos['date_field_id'];
        $int_field_id  = $infos['int_field_id'];
        $transition    = $this->getSpecificTransition(
            $this->simple_mode_workflow_tracker_id,
            'status',
            'Open',
            'Done'
        );
        $transition_id = $transition['id'];

        $body     = json_encode(
            [
                'post_actions' => [
                    [
                        'id'         => null,
                        'type'       => 'set_field_value',
                        'field_id'   => $int_field_id,
                        'field_type' => 'int',
                        'value'      => 9001
                    ],
                    [
                        'id'         => null,
                        'type'       => 'set_field_value',
                        'field_id'   => $date_field_id,
                        'field_type' => 'date',
                        'value'      => ''
                    ]
                ]
            ]
        );
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->put("tracker_workflow_transitions/$transition_id/actions", null, $body)
        );
        $this->assertEquals(200, $response->getStatusCode());

        $sibling_transition    = $this->getSpecificTransition(
            $this->simple_mode_workflow_tracker_id,
            'status',
            'Closed',
            'Done'
        );
        $sibling_transition_id = $sibling_transition['id'];
        $response              = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get("tracker_workflow_transitions/$sibling_transition_id/actions")
        );
        $this->assertEquals(200, $response->getStatusCode());
        $sibling_post_actions = $response->json();

        $this->assertCount(2, $sibling_post_actions);
        $date_post_action = $sibling_post_actions[0];
        $int_post_action  = $sibling_post_actions[1];

        $this->assertSame('', $date_post_action['value']);
        $this->assertSame($date_field_id, $date_post_action['field_id']);
        $this->assertSame(9001, $int_post_action['value']);
        $this->assertSame($int_field_id, $int_post_action['field_id']);

        return $transition_id;
    }

    /**
     * @depends testPUTTrackerWorkflowTransitionsActions
     */
    public function testPUTTrackerWorkflowTransitionFrozenFieldsActions(int $transition_id)
    {
        $used_field_id = $this->getAUsedFieldId(
            $this->simple_mode_workflow_tracker_id,
            'points'
        );

        $body = json_encode([
            "post_actions" => [
                [
                    "id" => null,
                    "type" => "frozen_fields",
                    "field_ids" => [$used_field_id]
                ]
            ]
        ]);

        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->put(
                "tracker_workflow_transitions/$transition_id/actions",
                null,
                $body
            )
        );

        $this->assertEquals($response->getStatusCode(), 200);

        return $transition_id;
    }

    /**
     * @depends testPUTTrackerWorkflowTransitionFrozenFieldsActions
     */
    public function testGETTrackerWorkflowTransitionReturnsTheFrozenFieldPostAction(int $transition_id): int
    {
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get(
                "tracker_workflow_transitions/$transition_id/actions"
            )
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $json_response = $response->json();
        $this->assertEquals('frozen_fields', $json_response[0]['type']);

        return $transition_id;
    }

    /**
     * @depends testGETTrackerWorkflowTransitionReturnsTheFrozenFieldPostAction
     */
    public function testPUTTrackerWorkflowTransitionFrozenFieldsActionsCannotUsedTheWorkflowField(int $transition_id)
    {
        $workflow_field_id = $this->getAUsedFieldId(
            $this->simple_mode_workflow_tracker_id,
            'status'
        );

        $body = json_encode([
            "post_actions" => [
                [
                    "id" => null,
                    "type" => "frozen_fields",
                    "field_ids" => [$workflow_field_id]
                ]
            ]
        ]);

        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->put(
                "tracker_workflow_transitions/$transition_id/actions",
                null,
                $body
            )
        );

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @depends testGETTrackerWorkflowTransitionReturnsTheFrozenFieldPostAction
     */
    public function testPUTTrackerWorkflowTransitionFrozenFieldsActionsCannotUsedAFieldUsedInFieldDependencies(
        int $transition_id
    ) {
        $workflow_field_id = $this->getAUsedFieldId(
            $this->simple_mode_workflow_tracker_id,
            'list01'
        );

        $body = json_encode([
            "post_actions" => [
                [
                    "id" => null,
                    "type" => "frozen_fields",
                    "field_ids" => [$workflow_field_id]
                ]
            ]
        ]);

        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->put(
                "tracker_workflow_transitions/$transition_id/actions",
                null,
                $body
            )
        );

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @depends testPUTTrackerWorkflowTransitionsActions
     */
    public function testPUTTrackerWorkflowTransitionHiddenFieldsetsActions(int $transition_id)
    {
        $used_field_id = $this->getAUsedFieldId(
            $this->simple_mode_workflow_tracker_id,
            'fieldset1'
        );

        $body = json_encode([
            "post_actions" => [
                [
                    "id" => null,
                    "type" => "hidden_fieldsets",
                    "fieldset_ids" => [$used_field_id]
                ]
            ]
        ]);

        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->put(
                "tracker_workflow_transitions/$transition_id/actions",
                null,
                $body
            )
        );

        $this->assertEquals($response->getStatusCode(), 200);

        return $transition_id;
    }

    /**
     * @depends testPUTTrackerWorkflowTransitionHiddenFieldsetsActions
     */
    public function testGETTrackerWorkflowTransitionReturnsTheHiddenFieldsetsPostAction(int $transition_id)
    {
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get(
                "tracker_workflow_transitions/$transition_id/actions"
            )
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $json_response = $response->json();
        $this->assertEquals('hidden_fieldsets', $json_response[0]['type']);

        return $transition_id;
    }

    public function testGETWorkflowImportedFromXML()
    {
        $tracker = $this->tracker_representations[$this->simple_mode_from_xml_tracker_id];

        $this->assertEquals($tracker['workflow']['is_advanced'], false);
        $this->assertEquals($tracker['workflow']['is_used'], "1");
        $this->assertCount(3, $tracker['workflow']['transitions']);

        $transition = $this->getSpecificTransition(
            $this->simple_mode_from_xml_tracker_id,
            'status',
            'Open',
            'Done'
        );
        $transition_id = $transition['id'];

        $response_transition = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get("tracker_workflow_transitions/$transition_id")
        );
        $this->assertEquals(200, $response_transition->getStatusCode());

        $transition_content = $response_transition->json();

        $this->assertEquals(
            $transition_content['authorized_user_group_ids']['0'],
            $this->tracker_workflows_project_id . '_4'
        );

        $this->assertTrue($transition_content['not_empty_field_ids']['0'] > 0);

        $response_actions = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get("tracker_workflow_transitions/$transition_id/actions")
        );
        $this->assertEquals(200, $response_actions->getStatusCode());

        $transition_post_actions = $response_actions->json();

        $this->assertCount(3, $transition_post_actions);
    }
}
