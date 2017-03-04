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
namespace Trafficlights;

use TrafficlightsDataBuilder;
use TrafficlightsCampaignBuilder;
use BackendLogger;

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group TrafficlightsTest
 */
class DefinitionsTest extends BaseTest {

    public function testGetDefinition() {
        $first_definition = $this->getFirstDefinition();

        $definition_request = $this->client->get('trafficlights_definitions/' . $first_definition['id']);
        $definition         = $this->getResponse($definition_request)->json();

        $this->assertEquals($definition, $first_definition);
    }

    private function getFirstDefinition() {
        $campaign  = $this->getFirstCampaign();
        $execution = $this->getFirstExecution($campaign['id']);

        return $execution['definition'];
    }

    private function getFirstCampaign() {
        $campaigns_request  = $this->client->get("projects/$this->project_id/trafficlights_campaigns");
        $campaigns          = $this->getResponse($campaigns_request)->json();

        return $campaigns[0];
    }

    private function getFirstExecution($campaign_id) {
        $executions_request = $this->client->get('trafficlights_campaigns/'.$campaign_id.'/trafficlights_executions');
        $executions         = $this->getResponse($executions_request)->json();

        return $executions[0];
    }
}