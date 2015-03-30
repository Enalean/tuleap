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

use TestingCampaignBuilder;
use TestingDataBuilder;
use TestDataBuilder;
use \RestBase;

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group TestingTest
 */
abstract class BaseTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function setUp() {
        parent::setUp();

        $campaign_builder = new TestingCampaignBuilder(
            $this->client,
            $this->rest_request
        );
        $campaign_builder->setUp();
    }

    protected function getValid73Campaign() {
        $all_campaigns_request  = $this->client->get('projects/'.TestingDataBuilder::PROJECT_TEST_MGMT_ID.'/testing_campaigns');
        $all_campaigns_response = $this->getResponse($all_campaigns_request);
        $campaigns = $all_campaigns_response->json();

        $index_of_valid73_when_sorted_by_id = 0;
        $campaign = $campaigns[$index_of_valid73_when_sorted_by_id];
        $this->assertEquals($campaign['label'], 'Tuleap 7.3');

        return $campaign;
    }
}