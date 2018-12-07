<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Tests\REST\Workflows;

use Guzzle\Http\Exception\BadResponseException;
use REST_TestDataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

class TrackerWorkflowTransitionsTest extends TrackerBase
{
    public function testPOSTTrackerWorkflowTransitionsSavesANewTransitionAndReturnsTheTransitionRepresentation()
    {
        $transition_combinations = $this->getAllTransitionCombinations($this->tracker_workflow_transitions_tracker_id);
        $available_transition    = $transition_combinations["missing_transitions"][0];

        $params = json_encode(array(
            "tracker_id" => $this->tracker_workflow_transitions_tracker_id,
            "from_id" => $available_transition['from_id'] ?: 0,
            "to_id" => $available_transition['to_id']
        ));

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->post(
                'tracker_workflow_transitions',
                null,
                $params
            )
        );

        $this->assertEquals($response->getStatusCode(), 201);

        $response_content = $response->json();

        $this->assertNotNull($response_content['id']);
        $this->assertEquals($response_content['uri'], "tracker_workflow_transitions/{$response_content['id']}");
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPOSTTrackerWorkflowTransitionsRegularUsersHaveForbiddenAccess()
    {
        $transition_combinations   = $this->getAllTransitionCombinations($this->tracker_workflow_transitions_tracker_id);
        $available_transition      = $transition_combinations["missing_transitions"][0];

        $params = json_encode(array(
            "tracker_id" => $this->tracker_workflow_transitions_tracker_id,
            "from_id" => $available_transition['from_id'] ? $available_transition['from_id'] : 0,
            "to_id" => $available_transition['to_id']
        ));

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->client->post(
                'tracker_workflow_transitions',
                null,
                $params
            )
        );

        $this->fail('An exception was expected');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPOSTTrackerWorkflowTransitionsWhenTrackerDoesNotExistReturnsError()
    {
        $params = json_encode(array(
            "tracker_id" => 0,
            "from_id" => 0,
            "to_id" => 0
        ));

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->post(
                'tracker_workflow_transitions',
                null,
                $params
            )
        );

        $this->fail('An exception was expected');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPOSTTrackerWorkflowTransitionsWhenTrackerHasNoWorkflowReturnsError()
    {
        $params = json_encode(array(
            "tracker_id" => $this->tracker_workflows_tracker_id,
            "from_id" => 0,
            "to_id" => 0
        ));

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->post(
                'tracker_workflow_transitions',
                null,
                $params
            )
        );

        $this->fail('An exception was expected');
        $this->assertEquals($response->getStatusCode(), 404);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPOSTTrackerWorkflowTransitionsWhenTransitionAlreadyExistReturnsError()
    {
        $transition_combinations   = $this->getAllTransitionCombinations($this->tracker_workflow_transitions_tracker_id);
        $used_transition      = $transition_combinations["transitions"][0];

        $params = json_encode(array(
            "tracker_id" => $this->tracker_workflow_transitions_tracker_id,
            "from_id" => $used_transition['from_id'] ? $used_transition['from_id'] : 0,
            "to_id" => $used_transition['to_id']
        ));

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->post(
                'tracker_workflow_transitions',
                null,
                $params
            )
        );

        $this->fail('An exception was expected');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPOSTTrackerWorkflowTransitionsWhenFieldValueDoesNotExistReturnsError()
    {
        $transition_combinations   = $this->getAllTransitionCombinations($this->tracker_workflow_transitions_tracker_id);
        $available_transition      = $transition_combinations["missing_transitions"][0];

        $params = json_encode(array(
            "tracker_id" => $this->tracker_workflow_transitions_tracker_id,
            "from_id" => $available_transition['from_id'] ? $available_transition['from_id'] : 0,
            "to_id" => 1
        ));

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->post(
                'tracker_workflow_transitions',
                null,
                $params
            )
        );

        $this->fail('An exception was expected');
        $this->assertEquals($response->getStatusCode(), 404);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPOSTTrackerWorkflowTransitionsWhenFromIdEqualsToIdReturnsError()
    {
        $transition_combinations   = $this->getAllTransitionCombinations($this->tracker_workflow_transitions_tracker_id);
        $available_transition      = $transition_combinations["missing_transitions"][0];

        $params = json_encode(array(
            "tracker_id" => $this->tracker_workflow_transitions_tracker_id,
            "from_id" => $available_transition['to_id'],
            "to_id" => $available_transition['to_id']
        ));

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->post(
                'tracker_workflow_transitions',
                null,
                $params
            )
        );

        $this->fail('An exception was expected');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testDELETETrackerWorkflowTransitions()
    {
        $transition_combinations = $this->getAllTransitionCombinations($this->tracker_workflow_transitions_tracker_id);
        $used_transition         = $transition_combinations["transitions"][0];

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->delete(
                'tracker_workflow_transitions/' . $used_transition['id'],
                null
            )
        );

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testDELETETrackerWorkflowTransitionsReturns404WhenTransitionDoesNotExist()
    {
        try {
            $this->getResponseByName(
                REST_TestDataBuilder::TEST_USER_1_NAME,
                $this->client->delete(
                    'tracker_workflow_transitions/0',
                    null
                )
            );
            $this->fail('Expected exception not raised');
        } catch (BadResponseException $e) {
            $this->assertEquals(404, $e->getResponse()->getStatusCode());
        }
    }

    public function testGETTrackerWorkflowTransitionsReturnsTheTransitionRepresentation()
    {
        $transition_combinations = $this->getAllTransitionCombinations($this->tracker_workflow_transitions_tracker_id);
        $transition = $transition_combinations["transitions"][0];

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get('tracker_workflow_transitions/' . $transition['id'])
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response_content = $response->json();
        $this->assertEquals($transition['id'], $response_content['id']);
        $this->assertEquals($transition['from_id'] ?: 0, $response_content['from_id']);
        $this->assertEquals($transition['to_id'], $response_content['to_id']);
    }
}
