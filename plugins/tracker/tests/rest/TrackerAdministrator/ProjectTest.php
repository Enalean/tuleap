<?php
/**
 * Copyright (c) Enalean, 2018. All rights reserved
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

namespace Tuleap\Tracker\Tests\REST\TrackerAdministrator;

use Tuleap\Tracker\REST\DataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

class ProjectTest extends TrackerBase
{

    public function testItFiltersProjectsWithTrackerAdministrationPermission()
    {
        $url = 'projects?' . http_build_query([
            'limit' => 50, 'offset' => 0
        ]);

        $response      = $this->getResponse($this->client->get($url), DataBuilder::USER_TESTER_NAME);
        $json_projects = $response->json();

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertTrue(count($json_projects) > 1);

        $url = 'projects?' . http_build_query([
            'query'  => '{"is_tracker_admin":true}',
            'limit'  => 50,
            'offset' => 0
        ]);

        $response = $this->getResponse($this->client->get($url), DataBuilder::USER_TESTER_NAME);

        $json_projects = $response->json();

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(1, count($json_projects));
        $this->assertArrayHasKey('id', $json_projects[0]);
        $this->assertEquals($this->tracker_administrator_project_id, $json_projects[0]['id']);
    }

    public function testItReturnsAnErrorIfTheTrackerAdministratorFilterIsSetToFalse()
    {
        $url = 'projects?' . http_build_query([
            'query' => '{"is_tracker_admin":false}',
        ]);

        $response = $this->getResponse($this->client->get($url), DataBuilder::USER_TESTER_NAME);

        $this->assertEquals($response->getStatusCode(), 400);
    }
}
