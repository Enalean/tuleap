<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\REST\v1\CrossTracker;

use REST_TestDataBuilder;
use RestBase;

class CrossTrackerTest extends RestBase
{
    private function getResponse($request)
    {
        return $this->getResponseByToken(
            $this->getTokenForUserName(\REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testGetId()
    {
        $response = $this->getResponse($this->client->get('cross_tracker_reports/1'));

        $this->assertEquals($response->getStatusCode(), 200);

        $expected_cross_tracker = array(
            "id"       => 1,
            "uri"      => "cross_tracker_reports/1",
            "trackers" => array(
                array(
                    "id"    => $this->kanban_tracker_id,
                    "uri"   => "trackers/" . $this->kanban_tracker_id,
                    "label" => REST_TestDataBuilder::KANBAN_TRACKER_LABEL
                )
            )
        );

        $this->assertEquals(
            $response->json(),
            $expected_cross_tracker
        );
    }

    public function testPut()
    {
        $params   = array(
            "trackers_id" => array($this->epic_tracker_id)
        );
        $response = $this->getResponse($this->client->put('cross_tracker_reports/1', null, $params));

        $this->assertEquals($response->getStatusCode(), 201);

        $expected_cross_tracker = array(
            "id"       => 1,
            "uri"      => "cross_tracker_reports/1",
            "trackers" => array(
                array(
                    "id"    => $this->epic_tracker_id,
                    "uri"   => "trackers/" . $this->epic_tracker_id,
                    "label" => REST_TestDataBuilder::EPICS_TRACKER_LABEL
                )
            )
        );

        $this->assertEquals(
            $response->json(),
            $expected_cross_tracker
        );
    }
}
