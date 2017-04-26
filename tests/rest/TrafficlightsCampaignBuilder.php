<?php
/**
 * Copyright (c) Enalean, 2014 - 2015. All rights reserved
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

class TrafficlightsCampaignBuilder {

    private $campaigns_data = array(
        array('label' => 'Tuleap 7.1', 'status' => 'Closed', 'executions' => array()),
        array('label' => 'Tuleap 7.2', 'status' => 'Closed', 'executions' => array()),
        array('label' => 'Tuleap 7.3', 'status' => 'Open', 'executions' => array(
            array('status' => 'passed', 'definition' => array(
                'summary' => 'Create a repository', 'description' => 'This is a description', 'category' => 'Git'
            ), 'time' => 1),
            array('status' => 'passed', 'definition' => array(
                'summary' => 'Delete a repository', 'description' => 'This is a description', 'category' => 'Git'
            ), 'time' => 1),
            array('status' => 'failed', 'definition' => array(
                'summary' => 'Import default template', 'description' => 'This is a description', 'category' => 'AgileDashboard'
            ), 'time' => 1),
        )),
    );

    /** @var TrackerFactory */
    private $tracker_factory;

    public function __construct(Client $client, RequestWrapper $rest_request, $project_id) {
        $this->tracker_factory = new TrackerFactory(
            $client,
            $rest_request,
            $project_id,
            TrafficlightsDataBuilder::USER_TESTER_NAME
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
            $definition = $this->createDefinition($execution_data['definition']);

            $execution = $this->createExecution(
                $execution_data['status'],
                $definition['id'],
                $execution_data['time']
            );

            $executions_ids[] = $execution['id'];
        }

        return $executions_ids;
    }

    private function createCampaign($label, $status, array $executions) {
        $tracker = $this->tracker_factory->getTrackerRest('campaign');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Label', $label),
                $tracker->getSubmitListValue('Status', $status),
                $tracker->getSubmitArtifactLinkValue($executions)
            )
        );
    }

    private function createExecution($status, $definition_id, $time) {
        $tracker  = $this->tracker_factory->getTrackerRest('test_exec');
        $artifact = $tracker->createArtifact(
            array(
                $tracker->getSubmitListValue('Status', $status),
                $tracker->getSubmitArtifactLinkValue(array($definition_id)),
                $tracker->getSubmitTextValue('Time', $time)
            )
        );
        return $artifact;
    }

    private function createDefinition($definition_data) {
        $tracker = $this->tracker_factory->getTrackerRest('test_def');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Summary', $definition_data['summary']),
                $tracker->getSubmitTextValue('Description', $definition_data['description']),
                $tracker->getSubmitListValue('Category', $definition_data['category'])
            )
        );
    }
}
