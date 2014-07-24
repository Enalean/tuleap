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

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group ArtifactsTest
 */
class CampaignsTest extends BaseTest {

    public function testGetCampaign() {
        $expected_campaign = $this->getValid73Campaign();

        $response  = $this->getResponse($this->client->get('testing_campaigns/'. $expected_campaign['id']));
        $campaign = $response->json();

        $this->assertEquals($expected_campaign, $campaign);
    }


    public function testGetExecutions() {
        $campaign = $this->getValid73Campaign();

        $all_executions_request  = $this->client->get('testing_campaigns/'. $campaign['id'] .'/testing_executions');
        $all_executions_response = $this->getResponse($all_executions_request);

        $executions = $all_executions_response->json();
        $this->assertCount(3, $executions);
        $this->assertExecutionsAreSortedByCategoryAndId($executions);
    }


    public function testGetEnvironments() {
        $campaign = $this->getValid73Campaign();

        $all_environments_request  = $this->client->get('testing_campaigns/'. $campaign['id'] .'/testing_environments');
        $all_environments_response = $this->getResponse($all_environments_request);

        $environments = $all_environments_response->json();
        $this->assertCount(2, $environments);
        $this->assertEquals('CentOS 5 - PHP 5.3', $environments[0]);
        $this->assertEquals('CentOS 6 - PHP 5.3', $environments[1]);
    }

    private function assertExecutionsAreSortedByCategoryAndId($executions) {
        $this->assertEquals('Import default template', $executions[0]['definition']['summary']);
        $this->assertEquals('Create a repository', $executions[1]['definition']['summary']);
        $this->assertEquals('Delete a repository', $executions[2]['definition']['summary']);
    }
}