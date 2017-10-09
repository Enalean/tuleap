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

namespace Tuleap\TestManagement;

use TestManagementCampaignBuilder;
use TestManagementDataBuilder;
use RestBase;

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group TestManagementTest
 */
abstract class BaseTest extends RestBase
{
    protected $project_id;

    protected function getResponse($request, $user_name = TestManagementDataBuilder::USER_TESTER_NAME)
    {
        return parent::getResponse($request, $user_name);
    }

    public function setUp()
    {
        parent::setUp();

        if ($this->project_id === null) {
            $this->project_id = $this->getProjectId(TestManagementDataBuilder::PROJECT_TEST_MGMT_SHORTNAME);
        }
    }

    protected function getValid73Campaign()
    {
        $all_campaigns_request  = $this->client->get("projects/$this->project_id/testmanagement_campaigns");
        $all_campaigns_response = $this->getResponse($all_campaigns_request);
        $campaigns = $all_campaigns_response->json();

        $index_of_valid73_when_sorted_by_id = 0;
        $campaign = $campaigns[$index_of_valid73_when_sorted_by_id];
        $this->assertEquals($campaign['label'], 'Tuleap 7.3');

        return $campaign;
    }
}
