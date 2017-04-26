<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All rights reserved
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
namespace Trafficlights;

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group TrafficlightsTest
 */
class CampaignsTest extends BaseTest {

    public function testGetCampaign() {
        $expected_campaign = $this->getValid73Campaign();

        $response  = $this->getResponse($this->client->get('trafficlights_campaigns/'. $expected_campaign['id']));
        $campaign = $response->json();

        $this->assertEquals($expected_campaign, $campaign);
    }


    public function testGetExecutions() {
        $campaign = $this->getValid73Campaign();

        $all_executions_request  = $this->client->get('trafficlights_campaigns/'. $campaign['id'] .'/trafficlights_executions');
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
}
