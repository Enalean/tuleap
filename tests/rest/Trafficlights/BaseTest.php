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

namespace Trafficlights;

use TrafficlightsCampaignBuilder;
use TrafficlightsDataBuilder;
use RestBase;

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group TrafficlightsTest
 */
abstract class BaseTest extends RestBase {

    protected $project_id;

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TrafficlightsDataBuilder::USER_TESTER_NAME),
            $request
        );
    }

    public function setUp() {
        parent::setUp();

        if ($this->project_id === null) {
            $this->project_id = $this->getProjectId();
        }

        $campaign_builder = new TrafficlightsCampaignBuilder(
            $this->client,
            $this->rest_request,
            $this->project_id
        );

        $campaign_builder->setUp();
    }

    protected function getValid73Campaign() {
        $all_campaigns_request  = $this->client->get("projects/$this->project_id/trafficlights_campaigns");
        $all_campaigns_response = $this->getResponse($all_campaigns_request);
        $campaigns = $all_campaigns_response->json();

        $index_of_valid73_when_sorted_by_id = 0;
        $campaign = $campaigns[$index_of_valid73_when_sorted_by_id];
        $this->assertEquals($campaign['label'], 'Tuleap 7.3');

        return $campaign;
    }

    private function getProjectId()
    {
        $query = http_build_query(
            array(
                'limit' => 1,
                'query' => json_encode(
                    array(
                        'shortname' => TrafficlightsDataBuilder::PROJECT_TEST_MGMT_SHORTNAME
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->get("projects/?$query"));
        $project  = $response->json();

        return $project[0]['id'];
    }
}