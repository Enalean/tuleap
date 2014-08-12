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

    private $campaigns_data = array(
        array('label' => 'Tuleap 7.1', 'status' => 'Passed', 'executions' => array()),
        array('label' => 'Tuleap 7.2', 'status' => 'Passed', 'executions' => array()),
        array('label' => 'Tuleap 7.3', 'status' => 'Not Run', 'executions' => array(
            array('label' => 'First execution', 'status' => 'Passed'),
            array('label' => 'Second execution', 'status' => 'Passed'),
            array('label' => 'Third execution', 'status' => 'Failed'),
        )),
    );

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
        foreach ($this->campaigns_data as $campaign_data) {
            $executions_ids = $this->createExecutionsForCampaign($campaign_data['executions']);

            $this->createCampaign(
                $campaign_data['label'],
                $campaign_data['status'],
                $executions_ids
            );
        }
    }

    private function createExecutionsForCampaign(array $executions_data) {
        $executions_ids = array();
        foreach ($executions_data as $execution_data) {
            $execution = $this->createExecution($execution_data['label'], $execution_data['status']);

            $executions_ids[] = $execution['id'];
        }

        return $executions_ids;
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

    private function createExecution($label, $status) {
        $tracker= $this->tracker_factory->getTrackerRest('test_exec');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Label', $label),
                $tracker->getSubmitListValue('Status', $status)
            )
        );
    }
}