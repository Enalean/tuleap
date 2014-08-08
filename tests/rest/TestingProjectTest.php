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

require_once dirname(__FILE__).'/bootstrap.php';

use Test\Rest\Tracker\TrackerFactory;

/**
 * @group ArtifactsTest
 */
class TestingProjectTest extends RestBase {

    private $tracker_test_helper;

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function setUp() {
        parent::setUp();
        $this->tracker_test_helper = new TrackerFactory(
            $this->client,
            $this->rest_request,
            TestingDataBuilder::PROJECT_TEST_MGMT_ID,
            TestDataBuilder::TEST_USER_1_NAME
        );

        $this->createCampaigns();
    }

    public function testGetCampaigns() {

        $response  = $this->getResponse($this->client->get('projects/'.TestingDataBuilder::PROJECT_TEST_MGMT_ID.'/campaigns'));
        $campaigns = $response->json();

        $this->assertCount(3, $campaigns);

        $first_campaign = $campaigns[0];
        $this->assertArrayHasKey('id', $first_campaign);
        $this->assertEquals($first_campaign['name'], 'Tuleap 7.3');
        $this->assertEquals($first_campaign['status'], 'Not Run');

        $second_campaign = $campaigns[1];
        $this->assertArrayHasKey('id', $second_campaign);
        $this->assertEquals($second_campaign['name'], 'Tuleap 7.2');
        $this->assertEquals($second_campaign['status'], 'Passed');

        $third_campaign = $campaigns[2];
        $this->assertArrayHasKey('id', $third_campaign);
        $this->assertEquals($third_campaign['name'], 'Tuleap 7.1');
        $this->assertEquals($third_campaign['status'], 'Passed');
    }

    public function testStatusOfExecutionsAreCorrect() {

        $response  = $this->getResponse($this->client->get('projects/'.TestingDataBuilder::PROJECT_TEST_MGMT_ID.'/campaigns'));
        $campaigns = $response->json();

        $first_campaign = $campaigns[0];
        $this->assertArrayHasKey('nb_of_not_run', $first_campaign);
        $this->assertEquals($first_campaign['nb_of_not_run'], 0);

        $this->assertArrayHasKey('nb_of_passed', $first_campaign);
        $this->assertEquals($first_campaign['nb_of_passed'], 2);

        $this->assertArrayHasKey('nb_of_failed', $first_campaign);
        $this->assertEquals($first_campaign['nb_of_failed'], 1);

        $this->assertArrayHasKey('nb_of_blocked', $first_campaign);
        $this->assertEquals($first_campaign['nb_of_blocked'], 0);
    }

    private function createCampaigns() {
        $exec1 = $this->createExecutions('First execution', 'Passed');
        $exec2 = $this->createExecutions('Second execution', 'Passed');
        $exec3 = $this->createExecutions('Third execution', 'Failed');

        $this->createCampaign('Tuleap 7.1', 'Passed', array());
        $this->createCampaign('Tuleap 7.2', 'Passed', array());
        $this->createCampaign('Tuleap 7.3', 'Not Run', array($exec1['id'], $exec2['id'], $exec3['id']));
    }

    private function createCampaign($name, $status,array $executions) {
        $tracker = $this->tracker_test_helper->getTrackerRest('campaign');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Name', $name),
                $tracker->getSubmitListValue('Status', $status),
                $tracker->getSubmitArtifactLinkValue($executions),
            )
        );
    }

    private function createExecutions($name, $status) {
        $tracker= $this->tracker_test_helper->getTrackerRest('test_exec');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Name', $name),
                $tracker->getSubmitListValue('Status', $status)
            )
        );
    }
}