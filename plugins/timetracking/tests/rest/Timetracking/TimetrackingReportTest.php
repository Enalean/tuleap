<?php
/**
 * Copyright Enalean (c) 2019. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registrated trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Timetracking\REST;

require_once dirname(__FILE__) . '/../bootstrap.php';

class TimetrackingReportTest extends TimetrackingBase
{
    public function testGetId()
    {
        $report = [
            "id"               => 1,
            "uri"              => 'timetracking_reports/1',
            "trackers"         => [],
            "invalid_trackers" => []
        ];

        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get('timetracking_reports/1')
        );

        $this->assertEquals($response->json(), $report);
    }

    public function testGetIdRaiseExeptionIfReportNotExist()
    {
        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get('timetracking_reports/3')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetIdRaiseExeptionIfUserTryToAccessToSomebodyElseReport()
    {
        $response = $this->getResponseByName(
            TimetrackingDataBuilder::TEST_USER_4_NAME,
            $this->client->get('timetracking_reports/1')
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testUserCanUpdateHisReport()
    {
        $query = json_encode(["trackers_id" => [$this->tracker_timetracking]]);

        $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->put('timetracking_reports/1', null, $query)
        );

        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get('timetracking_reports/1')
        );

        $this->assertEquals($response->json()["trackers"][0]["id"], $this->tracker_timetracking);
    }

    public function testUpdateReportRaiseExeptionIfUserTryToUpdateToSomebodyElseReport()
    {
        $query = json_encode(["trackers_id" => [$this->tracker_timetracking]]);

        $response = $this->getResponseByName(
            TimetrackingDataBuilder::TEST_USER_4_NAME,
            $this->client->put('timetracking_reports/1', null, $query)
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testGetTimesByTrackers()
    {
        $query = urlencode(
            json_encode([
                            "trackers_id" => [$this->tracker_timetracking],
                            "start_date"  => "2010-03-01T00:00:00+01",
                            "end_date"    => "2019-03-21T00:00:00+01"
                        ])
        );
        $this->initUserId(TimetrackingDataBuilder::USER_TESTER_NAME);
        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get("/api/v1/timetracking_reports/1/times?query=$query")
        );
        $result   = $response->json();
        $total    = 0;

        foreach ($result as $tracker) {
            $total += $this->getTotaltimeByTracker($tracker["time_per_user"]);
        }

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($total, 1200);
    }

    public function testGetTimesByReportReturnsTheSumOfTrackedTimesOfPeriod()
    {
        $query = urlencode(
            json_encode([
                            "start_date"  => "2010-03-01T00:00:00+01",
                            "end_date"    => "2019-03-21T00:00:00+01"
                        ])
        );
        $this->initUserId(TimetrackingDataBuilder::USER_TESTER_NAME);
        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get("/api/v1/timetracking_reports/1/times?query=$query")
        );
        $result   = $response->json();
        $total    = 0;

        foreach ($result as $tracker) {
            $total += $this->getTotaltimeByTracker($tracker["time_per_user"]);
        }

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($total, 1200);
    }

    public function testGetTimeByRetortsReturnsNoTrackedTimeForLastMonth()
    {
        $this->initUserId(TimetrackingDataBuilder::USER_TESTER_NAME);
        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get("/api/v1/timetracking_reports/1/times")
        );
        $result   = $response->json();
        $total    = 0;

        foreach ($result as $tracker) {
            $total += $this->getTotaltimeByTracker($tracker["time_per_user"]);
        }

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($total, 200);
    }

    private function getTotaltimeByTracker(array $times_per_user) : int
    {
        $minutes = 0;
        foreach ($times_per_user as $time_per_user) {
            $minutes += (int) $time_per_user['minutes'];
        }

        return $minutes;
    }
}
