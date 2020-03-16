<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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

namespace Tuleap\TestManagement;

use REST_TestDataBuilder;
use TestManagementDataBuilder;

require_once dirname(__FILE__) . '/../bootstrap.php';

/**
 * @group TestManagementTest
 */
final class ExecutionsTest extends BaseTest
{
    public function testPutExecutionsWithRESTReadOnlyUser(): void
    {
        $initial_value = 'failed';
        $new_value     = 'blocked';

        $execution = $this->getLastExecutionForValid73Campaign(REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $this->assertEquals($initial_value, $execution['status']);

        $response = $this->getResponse(
            $this->client->put('testmanagement_executions/' . $execution['id'], null, json_encode(array(
                'status' => $new_value,
                'time'   => 0
            ))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(404, $response->getStatusCode());

        $updated_execution = $this->getLastExecutionForValid73Campaign(REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $this->assertEquals($initial_value, $updated_execution['status']);

        $response2 = $this->getResponse(
            $this->client->put('testmanagement_executions/' . $execution['id'], null, json_encode(array(
                'status' => $initial_value,
                'time'   => 0
            ))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(404, $response2->getStatusCode());
    }

    public function testPutExecutions(): void
    {
        $initial_value = 'failed';
        $new_value     = 'blocked';

        $execution = $this->getLastExecutionForValid73Campaign(TestManagementDataBuilder::USER_TESTER_NAME);
        $this->assertEquals($initial_value, $execution['status']);

        $response = $this->getResponse($this->client->put('testmanagement_executions/' . $execution['id'], null, json_encode(array(
            'status' => $new_value,
            'time'   => 0
        ))));

        $this->assertEquals($response->getStatusCode(), 200);

        $updated_execution = $this->getLastExecutionForValid73Campaign(TestManagementDataBuilder::USER_TESTER_NAME);
        $this->assertEquals($new_value, $updated_execution['status']);

        $this->getResponse($this->client->put('testmanagement_executions/' . $execution['id'], null, json_encode(array(
            'status' => $initial_value,
            'time'   => 0
        ))));
    }

    public function testPatchIssueLinkExecutionsWithRESTReadOnlyUser()
    {
        $issue_tracker_id = $this->tracker_ids[$this->project_id][TestManagementDataBuilder::ISSUE_TRACKER_SHORTNAME];

        $issue    = $this->getLastArtifactFromTracker($issue_tracker_id);
        $issue_id = $issue['id'];

        $execution = $this->getLastExecutionForValid73Campaign(REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $response  = $this->getResponse(
            $this->client->patch(
                'testmanagement_executions/' . $execution['id'] . '/issues',
                null,
                json_encode(array(
                    'issue_id' => $issue_id,
                    'comment'  => array(
                        'body'     => 'test result',
                        'format'   => 'html'
                    )
                ))
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPatchIssueLinkExecutions(): void
    {
        $issue_tracker_id = $this->tracker_ids[$this->project_id][TestManagementDataBuilder::ISSUE_TRACKER_SHORTNAME];

        $issue    = $this->getLastArtifactFromTracker($issue_tracker_id);
        $issue_id = $issue['id'];

        $execution = $this->getLastExecutionForValid73Campaign(TestManagementDataBuilder::USER_TESTER_NAME);
        $response  = $this->getResponse($this->client->patch(
            'testmanagement_executions/' . $execution['id'] . '/issues',
            null,
            json_encode(array(
                'issue_id' => $issue_id,
                'comment'  => array(
                    'body'     => 'test result',
                    'format'   => 'html'
                )
            ))
        ));

        $this->assertEquals($response->getStatusCode(), 200);

        $links     = $this->getArtifactData($execution['id'], '/linked_artifacts?direction=forward');
        $last_link = end($links['collection']);

        $this->assertEquals($issue_id, $last_link['id']);

        $comments = $this->getArtifactData($issue_id, '/changesets?fields=comments');
        $this->assertEquals('test result', $comments[0]['last_comment']['body']);
    }

    private function getLastExecutionForValid73Campaign(string $user_name)
    {
        $campaign = $this->valid_73_campaign;

        $all_executions_request  = $this->client->get('testmanagement_campaigns/' . $campaign['id'] . '/testmanagement_executions');
        $all_executions_response = $this->getResponse($all_executions_request, $user_name);

        $executions     = $all_executions_response->json();
        $last_execution = end($executions);
        $this->assertEquals('Import default template', $last_execution['definition']['summary']);

        return $last_execution;
    }

    private function getLastArtifactFromTracker($tracker_id)
    {
        $all_artifacts_request  = $this->client->get('trackers/' . $tracker_id . '/artifacts');
        $all_artifacts_response = $this->getResponse($all_artifacts_request);

        $artifacts      = $all_artifacts_response->json();
        $last_artifact = end($artifacts);

        return $last_artifact;
    }

    private function getArtifactData($artifact_id, $optional_querypath = '')
    {
        $artifact_request  = $this->client->get('artifacts/' . $artifact_id . $optional_querypath);
        $artifact_response = $this->getResponse($artifact_request);

        return $artifact_response->json();
    }
}
