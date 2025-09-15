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

namespace Tuleap\TestManagement\REST;

use Tuleap\REST\RESTTestDataBuilder;
use Tuleap\TestManagement\REST\Tests\TestManagementDataBuilder;
use Tuleap\TestManagement\REST\Tests\TestManagementRESTTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('TestManagementTest')]
final class ExecutionsTest extends TestManagementRESTTestCase
{
    public function testPutExecutionsWithRESTReadOnlyUser(): void
    {
        $initial_value = 'failed';
        $new_value     = 'blocked';

        $execution = $this->getLastExecutionForValid73Campaign(RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $this->assertEquals($initial_value, $execution['status']);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'testmanagement_executions/' . $execution['id'])->withBody($this->stream_factory->createStream(json_encode([
                'status' => $new_value,
                'time'   => 0,
            ]))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(404, $response->getStatusCode());

        $updated_execution = $this->getLastExecutionForValid73Campaign(RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $this->assertEquals($initial_value, $updated_execution['status']);

        $response2 = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'testmanagement_executions/' . $execution['id'])->withBody($this->stream_factory->createStream(json_encode([
                'status' => $initial_value,
                'time'   => 0,
            ]))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(404, $response2->getStatusCode());
    }

    public function testPutExecutions(): void
    {
        $initial_value = 'failed';
        $new_value     = 'blocked';

        $execution = $this->getLastExecutionForValid73Campaign(TestManagementDataBuilder::USER_TESTER_NAME);
        $this->assertEquals($initial_value, $execution['status']);

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'testmanagement_executions/' . $execution['id'])->withBody($this->stream_factory->createStream(json_encode([
            'status' => $new_value,
            'time'   => 0,
        ]))));

        $this->assertEquals($response->getStatusCode(), 200);

        $updated_execution = $this->getLastExecutionForValid73Campaign(TestManagementDataBuilder::USER_TESTER_NAME);
        $this->assertEquals($new_value, $updated_execution['status']);

        $this->getResponse($this->request_factory->createRequest('PUT', 'testmanagement_executions/' . $execution['id'])->withBody($this->stream_factory->createStream(json_encode([
            'status' => $initial_value,
            'time'   => 0,
        ]))));
    }

    public function testPutExecutionsWithFileAttachment(): void
    {
        $initial_value = 'passed';
        $new_value     = 'failed';

        // Get the execution
        $execution = $this->getLastExecutionForValid130Campaign(TestManagementDataBuilder::USER_TESTER_NAME);
        $this->assertEquals($initial_value, $execution['status']);
        $this->assertCount(0, $execution['attachments']);

        // initiate file upload
        $file_size     = 15;
        $file_resource = json_encode([
            'file_size' => $file_size,
            'file_type' => 'text/plain',
            'name'      => 'aaaa.txt',
        ]);

        $new_file_response = $this->getResponseByName(
            TestManagementDataBuilder::USER_TESTER_NAME,
            $this->request_factory
                ->createRequest('POST', $execution['upload_url'])
                ->withBody($this->stream_factory->createStream($file_resource))
        );

        $this->assertEquals(200, $new_file_response->getStatusCode());
        $new_file_response_json = json_decode($new_file_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotNull($new_file_response_json['upload_href']);

        // upload the file via tus
        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            TestManagementDataBuilder::USER_TESTER_NAME,
            $this->request_factory->createRequest('PATCH', $new_file_response_json['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream($file_content))
        );

        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        // Attach the uploaded file to the execution
        $put_resource = [
            'status'            => $new_value,
            'time'              => 0,
            'results'           => 'test result <img src="' . $new_file_response_json['download_href'] . '">',
            'uploaded_file_ids' => [$new_file_response_json['id']],
        ];
        $response     = $this->getResponse(
            $this->request_factory
                ->createRequest('PUT', 'testmanagement_executions/' . $execution['id'])
                ->withBody($this->stream_factory->createStream(json_encode($put_resource)))
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Assert that the file is present when we get the updated execution
        $updated_execution = $this->getLastExecutionForValid130Campaign(TestManagementDataBuilder::USER_TESTER_NAME);
        $this->assertEquals($new_value, $updated_execution['status']);
        $this->assertCount(1, $updated_execution['attachments']);
        $this->assertEquals('aaaa.txt', $updated_execution['attachments'][0]['filename']);
        $this->assertEquals($new_file_response_json['download_href'], $updated_execution['attachments'][0]['html_url']);

        $this->getResponse($this->request_factory->createRequest('PUT', 'testmanagement_executions/' . $execution['id'])->withBody($this->stream_factory->createStream(json_encode([
            'status' => $initial_value,
            'time'   => 0,
        ]))));
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPutExecutionsWithFileAttachment')]
    public function testPutExecutionDeletesFileAttachment(): void
    {
        $execution = $this->getLastExecutionForValid130Campaign(TestManagementDataBuilder::USER_TESTER_NAME);
        $this->assertCount(1, $execution['attachments']);
        $attachment_id = $execution['attachments'][0]['id'];

        $put_resource = [
            'status'            => 'failed',
            'time'              => 0,
            'deleted_file_ids' => [$attachment_id],
        ];
        $response     = $this->getResponse(
            $this->request_factory
                ->createRequest('PUT', 'testmanagement_executions/' . $execution['id'])
                ->withBody($this->stream_factory->createStream(json_encode($put_resource)))
        );

        $this->assertEquals(200, $response->getStatusCode());

        $execution = $this->getLastExecutionForValid130Campaign(TestManagementDataBuilder::USER_TESTER_NAME);
        $this->assertCount(0, $execution['attachments']);
    }

    public function testPutExecutionsReturnErrorIfWeAddFilesWithoutFileField(): void
    {
        $initial_value = 'failed';
        $new_value     = 'blocked';

        $execution = $this->getLastExecutionForValid73Campaign(TestManagementDataBuilder::USER_TESTER_NAME);
        $this->assertEquals($initial_value, $execution['status']);

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'testmanagement_executions/' . $execution['id'])->withBody($this->stream_factory->createStream(json_encode([
            'status' => $new_value,
            'time'   => 0,
            'uploaded_file_ids' => [12],
        ]))));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPatchIssueLinkExecutionsWithRESTReadOnlyUser()
    {
        $issue_tracker_id = $this->tracker_ids[$this->project_id][TestManagementDataBuilder::ISSUE_TRACKER_SHORTNAME];

        $issue    = $this->getLastArtifactFromTracker($issue_tracker_id);
        $issue_id = $issue['id'];

        $execution = $this->getLastExecutionForValid73Campaign(RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $response  = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'testmanagement_executions/' . $execution['id'] . '/issues')->withBody($this->stream_factory->createStream(json_encode([
                'issue_id' => $issue_id,
                'comment'  => [
                    'body'     => 'test result',
                    'format'   => 'html',
                ],
            ]))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPatchIssueLinkExecutions(): void
    {
        $issue_tracker_id = $this->tracker_ids[$this->project_id][TestManagementDataBuilder::ISSUE_TRACKER_SHORTNAME];

        $issue    = $this->getLastArtifactFromTracker($issue_tracker_id);
        $issue_id = $issue['id'];

        $execution = $this->getLastExecutionForValid73Campaign(TestManagementDataBuilder::USER_TESTER_NAME);
        $response  = $this->getResponse($this->request_factory->createRequest('PATCH', 'testmanagement_executions/' . $execution['id'] . '/issues')->withBody($this->stream_factory->createStream(json_encode([
            'issue_id' => $issue_id,
            'comment'  => [
                'body'     => 'test result',
                'format'   => 'html',
            ],
        ]))));

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

        $all_executions_request  = $this->request_factory->createRequest('GET', 'testmanagement_campaigns/' . $campaign['id'] . '/testmanagement_executions');
        $all_executions_response = $this->getResponse($all_executions_request, $user_name);

        $executions     = json_decode($all_executions_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $last_execution = end($executions);
        $this->assertEquals('Import default template', $last_execution['definition']['summary']);

        return $last_execution;
    }

    private function getLastExecutionForValid130Campaign(string $user_name)
    {
        $campaign = $this->valid_130_campaign;

        $all_executions_request  = $this->request_factory->createRequest('GET', 'testmanagement_campaigns/' . $campaign['id'] . '/testmanagement_executions');
        $all_executions_response = $this->getResponse($all_executions_request, $user_name);

        $executions     = json_decode($all_executions_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $last_execution = end($executions);
        $this->assertEquals('Create a repository', $last_execution['definition']['summary']);

        return $last_execution;
    }

    public function testPatchExecutionInClosedCampaignMustThrowAnError(): void
    {
        $execution = $this->getLastExecutionForClosedCampaign(TestManagementDataBuilder::USER_TESTER_NAME);
        $response  = $this->getResponse($this->request_factory->createRequest('PATCH', 'testmanagement_executions/' . $execution['id'] . '/issues')->withBody($this->stream_factory->createStream(json_encode([
            'steps_results'  => [
                'step_id'  => '1',
                'status'   => 'notrun',
            ],
        ]))));

        $this->assertEquals(400, $response->getStatusCode());
    }

    private function getLastExecutionForClosedCampaign(string $user_name): array
    {
        $campaign = $this->closed_71_campaign;

        $all_executions_request  = $this->request_factory->createRequest('GET', 'testmanagement_campaigns/' . $campaign['id'] . '/testmanagement_executions');
        $all_executions_response = $this->getResponse($all_executions_request, $user_name);

        $executions     = json_decode($all_executions_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $last_execution = end($executions);

        $this->assertEquals('Test in closed campaign', $last_execution['definition']['summary']);

        return $last_execution;
    }

    private function getLastArtifactFromTracker($tracker_id)
    {
        $all_artifacts_request  = $this->request_factory->createRequest('GET', 'trackers/' . $tracker_id . '/artifacts');
        $all_artifacts_response = $this->getResponse($all_artifacts_request);

        $artifacts     = json_decode($all_artifacts_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $last_artifact = end($artifacts);

        return $last_artifact;
    }

    private function getArtifactData($artifact_id, $optional_querypath = '')
    {
        $artifact_request  = $this->request_factory->createRequest('GET', 'artifacts/' . $artifact_id . $optional_querypath);
        $artifact_response = $this->getResponse($artifact_request);

        return json_decode($artifact_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }
}
