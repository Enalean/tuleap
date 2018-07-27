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

class ProjectTrackerTest extends TrackerBase
{

    public function testProjectAdministratorHaveAllTheTrackersOfTheProject()
    {
        $url = 'projects/' . $this->tracker_administrator_project_id . '/trackers?' . http_build_query([
            'query'  => '{"is_tracker_admin":true}'
        ]);

        $response      = $this->getResponse($this->client->get($url));
        $json_trackers = $response->json();

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(2, count($json_trackers));
        $this->assertTrue($json_trackers[0]['item_name'] === TrackerBase::SIMPLE_01_TRACKER_SHORTNAME);
        $this->assertTrue($json_trackers[1]['item_name'] === TrackerBase::SIMPLE_02_TRACKER_SHORTNAME);
    }

    public function testProjectMembersHaveOnlyTheTrackersOfTheProjectTheyAreAdminstrator()
    {
        $url = 'projects/' . $this->tracker_administrator_project_id . '/trackers?' . http_build_query([
            'query'  => '{"is_tracker_admin":true}'
        ]);

        $response      = $this->getResponse($this->client->get($url), DataBuilder::USER_TESTER_NAME);
        $json_trackers = $response->json();

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(1, count($json_trackers));
        $this->assertTrue($json_trackers[0]['item_name'] === TrackerBase::SIMPLE_02_TRACKER_SHORTNAME);
    }

    /**
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testItReturnsAnErrorIfTheTrackerAdministratorFilterIsSetToFalse()
    {
        $url = 'projects/' . $this->tracker_administrator_project_id . '/trackers?' . http_build_query([
            'query'  => '{"is_tracker_admin":false}'
        ]);

        $response = $this->getResponse($this->client->get($url), DataBuilder::USER_TESTER_NAME);

        $this->assertEquals($response->getStatusCode(), 400);
    }
}
