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
use \Guzzle\Http\Client;
use \Test\Rest\RequestWrapper;

class TestingCampaignBuilder {

    /** @var TrackerFactory */
    private $tracker_factory;

    public function __construct(Client $client, RequestWrapper $rest_request) {
        $this->tracker_factory = new TrackerFactory(
            $client,
            $rest_request,
            TestingDataBuilder::PROJECT_TEST_MGMT_ID,
            TestDataBuilder::TEST_USER_1_NAME
        );
    }

    public function setUp() {
        if ($this->areCampaignAlreadyCreated()) {
            return;
        }

        $this->createCampaigns();
    }

    private function areCampaignAlreadyCreated() {
        $tracker = $this->tracker_factory->getTrackerRest('campaign');

        return $tracker->countArtifacts() > 0;
    }

    private function createCampaigns() {
        $exec1 = $this->createExecutions('First execution', 'Passed');
        $exec2 = $this->createExecutions('Second execution', 'Passed');
        $exec3 = $this->createExecutions('Third execution', 'Failed');

        $this->createCampaign('Tuleap 7.1', 'Passed', array());
        $this->createCampaign('Tuleap 7.2', 'Passed', array());
        $this->createCampaign('Tuleap 7.3', 'Not Run', array($exec1['id'], $exec2['id'], $exec3['id']));
    }

    private function createCampaign($label, $status,array $executions) {
        $tracker = $this->tracker_factory->getTrackerRest('campaign');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Label', $label),
                $tracker->getSubmitListValue('Status', $status),
                $tracker->getSubmitArtifactLinkValue($executions),
            )
        );
    }

    private function createExecutions($label, $status) {
        $tracker= $this->tracker_factory->getTrackerRest('test_exec');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Label', $label),
                $tracker->getSubmitListValue('Status', $status)
            )
        );
    }
}