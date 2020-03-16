<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Timetracking\REST;

require_once dirname(__FILE__) . '/../bootstrap.php';

class TimetrackingTest extends TimetrackingBase
{
    public function testGetTimesForUserWithDates(): void
    {
        $query = urlencode(
            json_encode([
                "start_date" => "2018-04-01T00:00:00+01",
                "end_date"   => "2018-04-10T00:00:00+01"
            ])
        );
        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get("users/" . $this->user_ids[TimetrackingDataBuilder::USER_TESTER_NAME] . "/timetracking?query=$query")
        );
        $times_by_artifact = $response->json();
        $current_artifact_id = key($times_by_artifact);
        $times               = $times_by_artifact[$current_artifact_id];

        $this->assertTrue(count($times_by_artifact) === 2);
        $this->assertTrue(count($times) === 1);
        $this->assertEquals($times[0]['artifact']['id'], $current_artifact_id);
        $this->assertEquals($times[0]['id'], 1);
        $this->assertEquals($times[0]['minutes'], 600);
        $this->assertEquals($times[0]['date'], '2018-04-01');
    }

    public function testGetTimesForUserWithDatesWithReadOnlySiteAdmin(): void
    {
        $query = urlencode(
            json_encode([
                "start_date" => "2018-04-01T00:00:00+01",
                "end_date"   => "2018-04-10T00:00:00+01"
            ])
        );
        $response = $this->getResponse(
            $this->client->get("users/" . $this->user_ids[TimetrackingDataBuilder::USER_TESTER_NAME] . "/timetracking?query=$query"),
            \REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testGetTimesForUserForOneDay()
    {
        $query = urlencode(
            json_encode([
                "start_date" => "2018-04-01T00:00:00+01",
                "end_date"   => "2018-04-01T00:00:00+01"
            ])
        );

        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get("users/" . $this->user_ids[TimetrackingDataBuilder::USER_TESTER_NAME] . "/timetracking?query=$query")
        );
        $times_by_artifact = $response->json();
        $current_artifact_id = key($times_by_artifact);
        $times               = $times_by_artifact[$current_artifact_id];

        $this->assertTrue(count($times_by_artifact) === 2);
        $this->assertTrue(count($times) === 1);
        $this->assertEquals($times[0]['artifact']['id'], $current_artifact_id);
        $this->assertEquals($times[0]['id'], 1);
        $this->assertEquals($times[0]['minutes'], 600);
        $this->assertEquals($times[0]['date'], '2018-04-01');
    }

    public function testExceptionWhenStartDateMissing()
    {
        $query = urlencode(
            json_encode([
                "end_date" => "2018-04-10T00:00:00+01"
            ])
        );

        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get('users/' . $this->user_ids[TimetrackingDataBuilder::USER_TESTER_NAME] . "/timetracking?query=$query")
        );
        $body     = $response->json();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString(
            'Missing start_date entry in the query parameter',
            $body['error']['message']
        );
    }

    public function testExceptionWhenEndDateMissing()
    {
        $query = urlencode(
            json_encode([
                "start_date" => "2018-04-01T00:00:00+01"
            ])
        );

        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get('users/' . $this->user_ids[TimetrackingDataBuilder::USER_TESTER_NAME] . "/timetracking?query=$query")
        );
        $body     = $response->json();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString(
            'Missing end_date entry in the query parameter',
            $body['error']['message']
        );
    }

    public function testExceptionWhenStartDateGreaterThanEndDate()
    {
        $query = urlencode(
            json_encode([
                "start_date" => "2018-04-10T00:00:00+01",
                "end_date"   => "2018-04-01T00:00:00+01"
            ])
        );

        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get('users/' . $this->user_ids[TimetrackingDataBuilder::USER_TESTER_NAME] . "/timetracking?query=$query")
        );
        $body     = $response->json();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString(
            'end_date must be greater than start_date',
            $body['error']['message']
        );
    }

    public function testExceptionWhenDatesAreInvalid()
    {
        $query = urlencode(
            json_encode([
                "start_date" => "not a valid date",
                "end_date"   => ""
            ])
        );

        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get('users/' . $this->user_ids[TimetrackingDataBuilder::USER_TESTER_NAME] . "/timetracking?query=$query")
        );
        $body     = $response->json();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString(
            'Please provide valid ISO-8601 dates',
            $body['error']['message']
        );
    }

    public function testExceptionWhenDatesAreNotISO8601()
    {
        $query = urlencode(
            json_encode(
                [
                    "start_date" => "2018/01/01",
                    "end_date"   => "2018/01/30"
                ]
            )
        );

        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get('users/' . $this->user_ids[TimetrackingDataBuilder::USER_TESTER_NAME] . "/timetracking?query=$query")
        );
        $body     = $response->json();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString(
            'Please provide valid ISO-8601 dates',
            $body['error']['message']
        );
    }

    public function testGetTimesPaginated()
    {
        $query = urlencode(
            json_encode([
                "start_date" => "2010-04-01T00:00:00+01",
                "end_date"   => "2018-05-31T00:00:00+01"
            ])
        );

        $times_ids = [1, 2];

        for ($offset = 0; $offset <= 1; $offset ++) {
            $response = $this->getResponseByName(
                TimetrackingDataBuilder::USER_TESTER_NAME,
                $this->client->get('users/' . $this->user_ids[TimetrackingDataBuilder::USER_TESTER_NAME] . "/timetracking?limit=1&offset=$offset&query=$query")
            );

            $artifact_times      = $response->json();
            $current_artifact_id = key($artifact_times);
            $times               = $artifact_times[$current_artifact_id];

            $this->assertTrue(count($artifact_times) === 1);
            $this->assertTrue(count($times) === 1);
            $this->assertEquals($times[0]['artifact']['id'], $current_artifact_id);
            $this->assertEquals($times[0]['id'], $times_ids[$offset]);
        }
    }
    public function testAddTimeSuccess()
    {
        $query = json_encode([
            "date_time"   => "2018-04-01",
            "artifact_id" => $this->timetracking_artifact_ids[1]["id"],
            "time_value"  => "11:11",
            "step"        => "etape"
        ]);
        $response = $this->getResponse($this->client->post('/api/v1/timetracking', null, $query), TimetrackingDataBuilder::USER_TESTER_NAME);
        $this->assertEquals($response->getStatusCode(), 201);
    }

    /**
     * @depends testAddTimeSuccess
     */
    public function testGetTimeOnArtifact()
    {
        $query = urlencode(json_encode([
            "artifact_id" => $this->timetracking_artifact_ids[1]["id"],
        ], JSON_FORCE_OBJECT));
        $response = $this->getResponse($this->client->get('/api/v1/timetracking?query=' . $query), TimetrackingDataBuilder::USER_TESTER_NAME);
        $this->assertEquals($response->getStatusCode(), 200);
        $payload = $response->json();
        $this->assertTrue(count($payload) >= 2);
        $this->assertEquals(200, $payload[0]['minutes']);
        $this->assertEquals(600, $payload[1]['minutes']);
        $this->assertEquals('test', $payload[1]['step']);
    }

    public function testAddTimeReturnBadTimeFormatExceptionWrongSeparator()
    {
        $query = json_encode([
             "date_time"   => "2018-04-01",
             "artifact_id" => $this->timetracking_artifact_ids[1]["id"],
             "time_value"  => "11/11",
             "step"        => "etape"
         ]);
        $response = $this->getResponse(
            $this->client->post('timetracking', null, $query),
            TimetrackingDataBuilder::USER_TESTER_NAME
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGetProjects()
    {
        $query = urlencode(
            json_encode([
                "with_time_tracking" => true
            ])
        );

        $this->initUserId(TimetrackingDataBuilder::USER_TESTER_NAME);
        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get("projects?query=$query")
        );
        $projects = $response->json();
        $this->assertTrue(count($projects) === 1);
        $this->assertEquals($projects[0]["label"], "Timetracking");
    }

    public function testGetTrackers()
    {
        $query = urlencode(
            json_encode([
                'with_time_tracking' => true
            ])
        );

        $this->initUserId(TimetrackingDataBuilder::USER_TESTER_NAME);
        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->client->get('projects/' . $this->timetracking_project_id . '/trackers?query=' . $query)
        );
        $trackers =  $response->json();
        $this->assertTrue(count($trackers) === 1);
        $this->assertEquals($trackers[0]["id"], $this->tracker_timetracking);
    }

    public function testGetProjectsRaiseException()
    {
        $query = urlencode(
            json_encode([
                "with_time_tracking" => false
            ])
        );

        $this->initUserId(TimetrackingDataBuilder::USER_TESTER_NAME);
        $response = $this->getResponse($this->client->get("projects?query=$query"));

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGetTrackersRaiseException()
    {
        $query = urlencode(
            json_encode([
                "with_time_tracking" => false
            ])
        );

        $this->initUserId(TimetrackingDataBuilder::USER_TESTER_NAME);
        $response = $this->getResponse($this->client->get("projects/" . $this->timetracking_project_id . "/trackers?query=$query"));

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testAddTimeReturnBadDateFormatException()
    {
        $query = json_encode([
             "date_time"   => "oui",
             "artifact_id" => $this->timetracking_artifact_ids[1]["id"],
             "time_value"  => "11:11",
             "step"        => "etape"
         ]);
        $response = $this->getResponse(
            $this->client->post('timetracking', null, $query),
            TimetrackingDataBuilder::USER_TESTER_NAME
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testEditTimeSuccess()
    {
        $query = json_encode([
            "date_time"   => "2018-04-01",
            "time_value"  => "11:11",
            "step"        => "etape"
        ]);
        $response = $this->getResponse($this->client->put('/api/v1/timetracking/1', null, $query), TimetrackingDataBuilder::USER_TESTER_NAME);

        $this->assertEquals($response->getStatusCode(), 201);
    }

    public function testEditTimeReturnBadTimeFormatException()
    {
        $query = json_encode([
            "date_time"   => "2018-04-01",
            "time_value"  => "11/11",
            "step"        => "etape"
        ]);
        $response = $this->getResponse(
            $this->client->put('/api/v1/timetracking/1', null, $query),
            TimetrackingDataBuilder::USER_TESTER_NAME
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testEditTimeReturnBadDateFormatException()
    {
        $query = json_encode([
            "date_time"   => "201804-01",
            "time_value"  => "11:11",
            "step"        => "etape"
        ]);
        $response = $this->getResponse(
            $this->client->put('/api/v1/timetracking/1', null, $query),
            TimetrackingDataBuilder::USER_TESTER_NAME
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testEditTimeReturnNoTimeException()
    {
        $query = json_encode([
            "date_time"   => "2018-04-01",
            "time_value"  => "11:11",
            "step"        => "etape"
        ]);
        $response = $this->getResponse(
            $this->client->put('/api/v1/timetracking/8000', null, $query),
            TimetrackingDataBuilder::USER_TESTER_NAME
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDeleteTimeSuccess()
    {
        $response = $this->getResponse($this->client->delete('/api/v1/timetracking/1', null), TimetrackingDataBuilder::USER_TESTER_NAME);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testDeleteTimeReturnNoTimeException()
    {
        $response = $this->getResponse(
            $this->client->put('/api/v1/timetracking/8000', null),
            TimetrackingDataBuilder::USER_TESTER_NAME
        );

        $this->assertEquals(400, $response->getStatusCode());
    }
}
