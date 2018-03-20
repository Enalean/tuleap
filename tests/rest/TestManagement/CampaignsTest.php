<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All rights reserved
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

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group TestManagementTest
 */
class CampaignsTest extends BaseTest {

    public function testGetCampaign() {
        $expected_campaign = $this->getValid73Campaign();

        $response  = $this->getResponse($this->client->get('testmanagement_campaigns/'. $expected_campaign['id']));
        $campaign = $response->json();

        $this->assertEquals($expected_campaign, $campaign);
    }

    public function testGetExecutions() {
        $campaign = $this->getValid73Campaign();

        $all_executions_request  = $this->client->get('testmanagement_campaigns/'. $campaign['id'] .'/testmanagement_executions');
        $all_executions_response = $this->getResponse($all_executions_request);

        $executions = $all_executions_response->json();
        $this->assertCount(3, $executions);
        $this->assertExecutionsContains($executions, 'Import default template');
        $this->assertExecutionsContains($executions, 'Create a repository');
        $this->assertExecutionsContains($executions, 'Delete a repository');
    }

    private function assertExecutionsContains($executions, $summary) {
        foreach ($executions as $execution) {
            if ($summary === $execution['definition']['summary']) {
                $this->assertTrue(true);
                return;
            }
        }
        $this->fail();
    }

    public function testPatchCampaignLabel()
    {
        $campaign = $this->getValid73Campaign();

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
        $campaign = $this->getValid73Campaign();

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
        $campaign = $this->getValid73Campaign();

        try {
            $this->getResponse(
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
            $this->fail('Should have receive a 400');
        } catch (\Guzzle\Http\Exception\ClientErrorResponseException $exception) {
            $this->assertEquals(400, $exception->getResponse()->getStatusCode());
            return;
        }
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
