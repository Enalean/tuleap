<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
use Tuleap\TestManagement\REST\Tests\TestManagementRESTTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('TestManagementTest')]
final class CampaignsTest extends TestManagementRESTTestCase
{
    public function testGetCampaign(): void
    {
        $expected_campaign = $this->valid_73_campaign;

        $response = $this->getResponse($this->request_factory->createRequest('GET', 'testmanagement_campaigns/' . $expected_campaign['id']));
        $campaign = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($expected_campaign, $campaign);
    }

    public function testGetCampaignWithRESTReadOnlyUser(): void
    {
        $expected_campaign = $this->valid_73_campaign;

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'testmanagement_campaigns/' . $expected_campaign['id']),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $campaign = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($expected_campaign, $campaign);
    }

    public function testGetExecutions(): void
    {
        $campaign = $this->valid_73_campaign;

        $all_executions_request  = $this->request_factory->createRequest('GET', 'testmanagement_campaigns/' . $campaign['id'] . '/testmanagement_executions');
        $all_executions_response = $this->getResponse($all_executions_request);

        $executions = json_decode($all_executions_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(3, $executions);
        $this->assertExecutionsContains($executions, 'Import default template');
        $this->assertExecutionsContains($executions, 'Create a repository');
        $this->assertExecutionsContains($executions, 'Delete a repository');
    }

    public function testGetExecutionsWithRESTReadOnlyUser(): void
    {
        $campaign = $this->valid_73_campaign;

        $all_executions_request = $this->request_factory->createRequest('GET', 'testmanagement_campaigns/' . $campaign['id'] . '/testmanagement_executions');

        $all_executions_response = $this->getResponse(
            $all_executions_request,
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $executions = json_decode($all_executions_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(3, $executions);
        $this->assertExecutionsContains($executions, 'Import default template');
        $this->assertExecutionsContains($executions, 'Create a repository');
        $this->assertExecutionsContains($executions, 'Delete a repository');
    }

    private function assertExecutionsContains($executions, $summary)
    {
        foreach ($executions as $execution) {
            if ($summary === $execution['definition']['summary']) {
                $this->assertTrue(true);
                return;
            }
        }
        $this->fail();
    }

    public function testPatchCampaignWithRESTReadOnlyUser(): void
    {
        $campaign = $this->valid_73_campaign;

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'testmanagement_campaigns/' . $campaign['id'])->withBody($this->stream_factory->createStream(json_encode(
                [
                    'label' => 'Tuleap 9.18',
                ]
            ))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPatchCampaignLabel()
    {
        $campaign = $this->valid_73_campaign;

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'testmanagement_campaigns/' . $campaign['id'])->withBody($this->stream_factory->createStream(json_encode(
                [
                    'label' => 'Tuleap 9.18',
                ]
            )))
        );
        $this->assertEquals(200, $response->getStatusCode());

        $updated_campaign = json_decode($this->getResponse(
            $this->request_factory->createRequest('GET', 'testmanagement_campaigns/' . $campaign['id'])
        )->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Tuleap 9.18', $updated_campaign['label']);

        $this->revertCampaign($campaign);
    }

    public function testPatchCampaignJobUrlToken()
    {
        $campaign = $this->valid_73_campaign;

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'testmanagement_campaigns/' . $campaign['id'])->withBody($this->stream_factory->createStream(json_encode(
                [
                    'job_configuration' => ['url' => 'https://example.com', 'token' => 'so secret'],
                ]
            )))
        );
        $this->assertEquals(200, $response->getStatusCode());

        $updated_campaign = json_decode($this->getResponse(
            $this->request_factory->createRequest('GET', 'testmanagement_campaigns/' . $campaign['id'])
        )->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('https://example.com', $updated_campaign['job_configuration']['url']);
        $this->assertEquals('so secret', $updated_campaign['job_configuration']['token']);

        $this->revertCampaign($campaign);
    }

    public function testPatchCampaignSuccessWithAutomatedTests()
    {
        $campaign                = $this->valid_73_campaign;
        $automated_tests_results = [
            'build_url'      => 'https://exemple/of/url',
            'junit_contents' => [],
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'testmanagement_campaigns/' . $campaign['id'])->withBody($this->stream_factory->createStream(json_encode(
                [
                    'job_configuration' => ['url' => 'https://example.com', 'token' => 'so secret'],
                    'automated_tests_results' => $automated_tests_results,
                ]
            )))
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->revertCampaign($campaign);
    }

    public function testPatchCampaignWithAutomatedTestsThrows400IfCampaignIsClosed()
    {
        $campaign = $this->closed_71_campaign;
        $this->assertFalse($campaign['is_open']);

        $automated_tests_results = [
            'build_url'      => 'https://exemple/of/url',
            'junit_contents' => [],
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'testmanagement_campaigns/' . $campaign['id'])->withBody($this->stream_factory->createStream(json_encode(
                [
                    'job_configuration' => ['url' => 'https://example.com', 'token' => 'so secret'],
                    'automated_tests_results' => $automated_tests_results,
                ]
            )))
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPatchCampaignThrow400IfJobUrlIsInvalid()
    {
        $campaign = $this->valid_73_campaign;

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'testmanagement_campaigns/' . $campaign['id'])->withBody($this->stream_factory->createStream(json_encode(
                [
                    'job_configuration' => ['url' => 'avadakedavra', 'token' => 'so secret'],
                ]
            )))
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPatchCampaignExecutions(): void
    {
        $campaign = $this->valid_73_campaign;
        $def_id   = $this->getFirstExecution($campaign['id'], RESTTestDataBuilder::TEST_BOT_USER_NAME)['definition']['id'];

        $all_executions_response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'testmanagement_campaigns/' . $campaign['id'] . '/testmanagement_executions')->withBody($this->stream_factory->createStream(json_encode(['definition_ids_to_add' => [$def_id], 'execution_ids_to_remove' => []])))
        );

        $executions = json_decode($all_executions_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(3, $executions);
        $this->assertExecutionsContains($executions, 'Import default template');
        $this->assertExecutionsContains($executions, 'Create a repository');
        $this->assertExecutionsContains($executions, 'Delete a repository');
    }

    public function testPatchCampaignExecutionsThrows400IfCampaignIsClosed(): void
    {
        $campaign = $this->closed_71_campaign;
        $this->assertFalse($campaign['is_open']);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'testmanagement_campaigns/' . $campaign['id'] . '/testmanagement_executions')->withBody($this->stream_factory->createStream(json_encode(['definition_ids_to_add' => [], 'execution_ids_to_remove' => []])))
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPatchCampaignCanReopenACampaign(): void
    {
        $campaign = $this->closed_71_campaign;
        $this->assertFalse($campaign['is_open']);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'testmanagement_campaigns/' . $campaign['id'])->withBody($this->stream_factory->createStream(json_encode(['change_status' => 'open'])))
        );

        $this->assertEquals(200, $response->getStatusCode());

        $updated_campaign = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($updated_campaign['is_open']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPatchCampaignCanReopenACampaign')]
    public function testPatchCampaignCanCloseACampaign(): void
    {
        $campaign = $this->closed_71_campaign;
        $this->assertFalse($campaign['is_open']);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'testmanagement_campaigns/' . $campaign['id'])->withBody($this->stream_factory->createStream(json_encode(['change_status' => 'closed'])))
        );

        $this->assertEquals(200, $response->getStatusCode());

        $updated_campaign = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($updated_campaign['is_open']);
    }

    private function revertCampaign(array $campaign)
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'testmanagement_campaigns/' . $campaign['id'])->withBody($this->stream_factory->createStream(json_encode(
                [
                    'label'             => $campaign['label'],
                    'job_configuration' => $campaign['job_configuration'],
                ]
            )))
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $updated_campaign = json_decode($this->getResponse(
            $this->request_factory->createRequest('GET', 'testmanagement_campaigns/' . $campaign['id'])
        )->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($campaign['label'], $updated_campaign['label']);
        $this->assertEquals($campaign['job_configuration'], $updated_campaign['job_configuration']);
    }

    private function getFirstExecution($campaign_id, string $user_name): array
    {
        $executions_request = $this->request_factory->createRequest('GET', 'testmanagement_campaigns/' . $campaign_id . '/testmanagement_executions');
        $executions         = json_decode($this->getResponse(
            $executions_request,
            $user_name
        )->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $executions[0];
    }
}
