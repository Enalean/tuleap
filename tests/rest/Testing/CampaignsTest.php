<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
namespace Testing;

use TestingDataBuilder;
use TestingCampaignBuilder;

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group ArtifactsTest
 */
class CampaignsTest extends BaseTest {

    public function testGetCampaign() {
        $expected_campaign = $this->getValid73Campaign();

        $response  = $this->getResponse($this->client->get('campaigns/'. $expected_campaign['id']));
        $campaign = $response->json();

        $this->assertEquals($expected_campaign, $campaign);
    }


    public function testGetExecutions() {
        $campaign = $this->getValid73Campaign();

        $all_executions_request  = $this->client->get('campaigns/'. $campaign['id'] .'/executions');
        $all_executions_response = $this->getResponse($all_executions_request);

        $executions = $all_executions_response->json();
        $this->assertCount(3, $executions);
        $this->assertExecutionsAreSortedByCategoryAndId($executions);
    }

    private function assertExecutionsAreSortedByCategoryAndId($executions) {
        $this->assertEquals('Import default template', $executions[0]['test_definition']['summary']);
        $this->assertEquals('Create a repository', $executions[1]['test_definition']['summary']);
        $this->assertEquals('Delete a repository', $executions[2]['test_definition']['summary']);
    }

    public function getValid73Campaign() {
        $all_campaigns_request  = $this->client->get('projects/'.TestingDataBuilder::PROJECT_TEST_MGMT_ID.'/campaigns');
        $all_campaigns_response = $this->getResponse($all_campaigns_request);
        $campaigns = $all_campaigns_response->json();

        $index_of_valid73_when_sorted_by_id = 0;
        $campaign = $campaigns[$index_of_valid73_when_sorted_by_id];
        $this->assertEquals($campaign['label'], 'Tuleap 7.3');

        return $campaign;
    }
}