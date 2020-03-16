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

namespace Tuleap\TestManagement;

use REST_TestDataBuilder;

require_once dirname(__FILE__) . '/../bootstrap.php';

/**
 * @group TestManagementTest
 */
final class CampaignsTest extends BaseTest
{

    public function testGetCampaign(): void
    {
        $expected_campaign = $this->valid_73_campaign;

        $response  = $this->getResponse($this->client->get('testmanagement_campaigns/' . $expected_campaign['id']));
        $campaign = $response->json();

        $this->assertEquals($expected_campaign, $campaign);
    }

    public function testGetCampaignWithRESTReadOnlyUser(): void
    {
        $expected_campaign = $this->valid_73_campaign;

        $response  = $this->getResponse(
            $this->client->get('testmanagement_campaigns/' . $expected_campaign['id']),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $campaign = $response->json();

        $this->assertEquals($expected_campaign, $campaign);
    }

    public function testGetExecutions(): void
    {
        $campaign = $this->valid_73_campaign;

        $all_executions_request  = $this->client->get('testmanagement_campaigns/' . $campaign['id'] . '/testmanagement_executions');
        $all_executions_response = $this->getResponse($all_executions_request);

        $executions = $all_executions_response->json();
        $this->assertCount(3, $executions);
        $this->assertExecutionsContains($executions, 'Import default template');
        $this->assertExecutionsContains($executions, 'Create a repository');
        $this->assertExecutionsContains($executions, 'Delete a repository');
    }

    public function testGetExecutionsWithRESTReadOnlyUser(): void
    {
        $campaign = $this->valid_73_campaign;

        $all_executions_request  = $this->client->get('testmanagement_campaigns/' . $campaign['id'] . '/testmanagement_executions');

        $all_executions_response = $this->getResponse(
            $all_executions_request,
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $executions = $all_executions_response->json();
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
            $this->client->patch(
                'testmanagement_campaigns/' . $campaign['id'],
                null,
                json_encode(
                    [
                        'label' => 'Tuleap 9.18'
                    ]
                )
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPatchCampaignLabel()
    {
        $campaign = $this->valid_73_campaign;

        $response = $this->getResponse(
            $this->client->patch(
                'testmanagement_campaigns/' . $campaign['id'],
                null,
                json_encode(
                    [
                        'label' => 'Tuleap 9.18'
                    ]
                )
            )
        );
        $this->assertEquals(200, $response->getStatusCode());

        $updated_campaign = $this->getResponse(
            $this->client->get('testmanagement_campaigns/' . $campaign['id'])
        )->json();
        $this->assertEquals('Tuleap 9.18', $updated_campaign['label']);

        $this->revertCampaign($campaign);
    }

    public function testPatchCampaignJobUrlToken()
    {
        $campaign = $this->valid_73_campaign;

        $response = $this->getResponse(
            $this->client->patch(
                'testmanagement_campaigns/' . $campaign['id'],
                null,
                json_encode(
                    [
                        'job_configuration' => ['url' => 'https://example.com', 'token' => 'so secret']
                    ]
                )
            )
        );
        $this->assertEquals(200, $response->getStatusCode());

        $updated_campaign = $this->getResponse(
            $this->client->get('testmanagement_campaigns/' . $campaign['id'])
        )->json();
        $this->assertEquals('https://example.com', $updated_campaign['job_configuration']['url']);
        $this->assertEquals('so secret', $updated_campaign['job_configuration']['token']);

        $this->revertCampaign($campaign);
    }

    public function testPatchCampaignThrow400IfJobUrlIsInvalid()
    {
        $campaign = $this->valid_73_campaign;

        $response = $this->getResponse(
            $this->client->patch(
                'testmanagement_campaigns/' . $campaign['id'],
                null,
                json_encode(
                    [
                        'job_configuration' => ['url' => 'avadakedavra', 'token' => 'so secret']
                    ]
                )
            )
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    private function revertCampaign(array $campaign)
    {
        $response = $this->getResponse(
            $this->client->patch(
                'testmanagement_campaigns/' . $campaign['id'],
                null,
                json_encode(
                    [
                        'label'             => $campaign['label'],
                        'job_configuration' => $campaign['job_configuration']
                    ]
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $updated_campaign = $this->getResponse(
            $this->client->get('testmanagement_campaigns/' . $campaign['id'])
        )->json();
        $this->assertEquals($campaign['label'], $updated_campaign['label']);
        $this->assertEquals($campaign['job_configuration'], $updated_campaign['job_configuration']);
    }
}
