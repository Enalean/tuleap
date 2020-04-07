<?php
/**
 * Copyright (c) Enalean, 2018 - 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Tests\REST\ArtifactsActions;

use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

class TrackerWorkflowsTest extends TrackerBase
{
    public function testGetStatusFieldId(): int
    {
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('trackers/' . $this->tracker_workflows_tracker_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $tracker = $response->json();

        $status_field_index = array_search('status_id', array_column($tracker['fields'], 'name'));

        return (int) $tracker['fields'][$status_field_index]['field_id'];
    }

    /**
     * @depends testGetStatusFieldId
     */
    public function testPATCHTrackerWorkflowsCreatesANewWorklowAndReturnsTheNewWorkflowRepresentation($field_status_id)
    {
        $query = '{"workflow": {"set_transitions_rules": {"field_id":' . $field_status_id . '}}}';

        $response = $this->getResponseByName(
            \REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->patch(
                "trackers/" . $this->tracker_workflows_tracker_id . '?query=' . urlencode($query),
                null,
                null
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetStatusFieldId
     */
    public function testPATCHTrackerWorkflowsRegularUsersHaveForbiddenAccess($field_status_id)
    {
        $query = '{"workflow": {"set_transitions_rules": {"field_id":' . $field_status_id . '}}}';

        $response = $this->getResponseByName(
            \REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->client->patch("trackers/" . $this->tracker_workflows_tracker_id . '?query=' . urlencode($query), null, null)
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetStatusFieldId
     * @depends testPATCHTrackerWorkflowsCreatesANewWorklowAndReturnsTheNewWorkflowRepresentation
     */
    public function testPATCHTrackerWorkflowsWhenAWorkflowIsAlreadyDefinedReturnsError($field_status_id)
    {
        $query = '{"workflow": {"set_transitions_rules": {"field_id":' . $field_status_id . '}}}';

        $response = $this->getResponseByName(
            \REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->patch("trackers/" . $this->tracker_workflows_tracker_id . '?query=' . urlencode($query), null, null)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetStatusFieldId
     */
    public function testPATCHTrackerWorkflowsWhenDeleteAndSetATransitionSameTimeReturnsError($field_status_id)
    {
        $query = '{"workflow": {"set_transitions_rules": {"field_id":' . $field_status_id . '}, "delete_transitions_rules": true}}';

        $response = $this->getResponseByName(
            \REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->patch("trackers/" . $this->tracker_workflows_tracker_id . '?query=' . urlencode($query), null, null)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testPATCHTrackerWorkflowsCreatesANewWorklowAndReturnsTheNewWorkflowRepresentation
     */
    public function testPATCHTrackerWorkflowsIsUsed()
    {
        $query = '{"workflow": {"set_transitions_rules": {"is_used": true}}}';
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->patch("trackers/" . $this->tracker_workflows_tracker_id . '?query=' . urlencode($query), null, null)
        );
        $result = $response->json();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("1", $result['workflow']['is_used']);
    }

    /**
     * @depends testPATCHTrackerWorkflowsIsUsed
     */
    public function testPATCHTrackerWorkflowsIsNotUsed()
    {
        $query = '{"workflow": {"set_transitions_rules": {"is_used": false}}}';
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->patch("trackers/" . $this->tracker_workflows_tracker_id . '?query=' . urlencode($query), null, null)
        );
        $result = $response->json();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("0", $result['workflow']['is_used']);
    }

    /**
     * @depends testPATCHTrackerWorkflowsIsUsed
     * @depends testPATCHTrackerWorkflowsIsNotUsed
     */
    public function testPATCHTrackerWorkflowsDeleteAWorkflowTransitionRules()
    {
        $query = '{"workflow": {"delete_transitions_rules": true}}';
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->patch(
                "trackers/" . $this->tracker_workflows_tracker_id . '?query=' . urlencode($query),
                null,
                null
            )
        );
        $result = $response->json();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertTrue($result['workflow']['field_id'] === 0);
        $this->assertTrue(empty($result['workflow']['is_used']));
        $this->assertTrue(sizeof($result['workflow']['transitions']) === 0);
    }

    /**
     * @depends testPATCHTrackerWorkflowsDeleteAWorkflowTransitionRules
     */
    public function testPATCHTrackerWorkflowsDeleteAWorkflowTransitionRulesWhenNotExist()
    {
        $query    = '{"workflow": {"delete_transitions_rules": true}}';
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->patch(
                "trackers/" . $this->tracker_workflows_tracker_id . '?query=' . urlencode($query),
                null,
                null
            )
        );
        $this->assertEquals(400, $response->getStatusCode());
    }
}
