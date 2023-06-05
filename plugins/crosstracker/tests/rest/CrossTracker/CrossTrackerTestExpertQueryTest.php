<?php
/**
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\REST\v1;

use RestBase;

class CrossTrackerTestExpertQueryTest extends RestBase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->getEpicArtifactIds();
    }

    public function testNonEmptyTitle()
    {
        $params = [
            "trackers_id"  => [$this->epic_tracker_id],
            "expert_query" => ' @title != "" ',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        $this->assertEquals($response->getStatusCode(), 201);

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            $cross_tracker_report["expert_query"],
            '@title != ""'
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    /**
     * @depends testNonEmptyTitle
     */
    public function testMultipleTitles()
    {
        $params = [
            "trackers_id"  => [$this->epic_tracker_id],
            "expert_query" => ' @title = "first" OR @title = "third" ',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        $this->assertEquals($response->getStatusCode(), 201);

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            $cross_tracker_report["expert_query"],
            '@title = "first" OR @title = "third"'
        );

        $this->getMatchingEpicArtifactByIds([
            $this->epic_artifact_ids[3],
            $this->epic_artifact_ids[1],
        ]);
    }

    /**
     * @depends testMultipleTitles
     */
    public function testEmptyDescription()
    {
        $params = [
            "trackers_id"  => [$this->epic_tracker_id],
            "expert_query" => ' @description = "" ',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        $this->assertEquals($response->getStatusCode(), 201);

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            $cross_tracker_report["expert_query"],
            '@description = ""'
        );

        $this->getMatchingEpicArtifactByIds([
            $this->epic_artifact_ids[8],
        ]);
    }

    /**
     * @depends testEmptyDescription
     */
    public function testEmptyDescriptionWithNotEmptyTitle()
    {
        $params = [
            "trackers_id"  => [$this->epic_tracker_id],
            "expert_query" => ' @description = "" AND @title != "" ',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        $this->assertEquals($response->getStatusCode(), 201);

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            $cross_tracker_report["expert_query"],
            '@description = "" AND @title != ""'
        );

        $this->getMatchingEpicArtifactByIds([
            $this->epic_artifact_ids[8],
        ]);
    }

    public function testSubmittedByEqualsNotEmpty()
    {
        $params = [
            "trackers_id"  => [ $this->epic_tracker_id ],
            "expert_query" => ' @submitted_by = "rest_api_tester_1"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        $this->assertEquals($response->getStatusCode(), 201);

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            $cross_tracker_report["expert_query"],
            '@submitted_by = "rest_api_tester_1"'
        );

        $this->getMatchingEpicArtifactByIds([
            $this->epic_artifact_ids[8],
            $this->epic_artifact_ids[7],
            $this->epic_artifact_ids[6],
            $this->epic_artifact_ids[5],
            $this->epic_artifact_ids[4],
            $this->epic_artifact_ids[3],
            $this->epic_artifact_ids[2],
            $this->epic_artifact_ids[1],
        ]);
    }

    /**
     * @depends testSubmittedByEqualsNotEmpty
     */
    public function testSubmittedByNotEqualsNotEmpty()
    {
        $params = [
            "trackers_id"  => [ $this->epic_tracker_id ],
            "expert_query" => ' @submitted_by != "rest_api_tester_1"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        $this->assertEquals($response->getStatusCode(), 201);

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            $cross_tracker_report["expert_query"],
            '@submitted_by != "rest_api_tester_1"'
        );

        $this->getMatchingEpicArtifactByIds([]);
    }

    public function testLastUpdateByNotEmpty()
    {
        $params = [
            "trackers_id"  => [ $this->epic_tracker_id ],
            "expert_query" => ' @last_update_by = "rest_api_tester_1"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        $this->assertEquals($response->getStatusCode(), 201);

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            $cross_tracker_report["expert_query"],
            '@last_update_by = "rest_api_tester_1"'
        );

        $this->getMatchingEpicArtifactByIds([
            $this->epic_artifact_ids[8],
            $this->epic_artifact_ids[7],
            $this->epic_artifact_ids[6],
            $this->epic_artifact_ids[5],
            $this->epic_artifact_ids[4],
            $this->epic_artifact_ids[3],
            $this->epic_artifact_ids[2],
            $this->epic_artifact_ids[1],
        ]);
    }

    public function testAssignedToNotEmpty()
    {
        $params = [
            "trackers_id"  => [ $this->epic_tracker_id ],
            "expert_query" => ' @assigned_to = "rest_api_tester_1"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        $this->assertEquals($response->getStatusCode(), 201);

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            $cross_tracker_report["expert_query"],
            '@assigned_to = "rest_api_tester_1"'
        );

        $this->getMatchingEpicArtifactByIds([
            $this->epic_artifact_ids[3],
        ]);
    }

    public function testWithParent(): void
    {
        $params = [
            "trackers_id"  => [ $this->epic_tracker_id ],
            "expert_query" => 'WITH PARENT',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        $this->assertEquals($response->getStatusCode(), 201);

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            $cross_tracker_report["expert_query"],
            'WITH PARENT'
        );

        $this->getMatchingEpicArtifactByIds([]);
    }

    public function testWithoutParent(): void
    {
        $params = [
            "trackers_id"  => [ $this->epic_tracker_id ],
            "expert_query" => 'WITHOUT PARENT',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        $this->assertEquals($response->getStatusCode(), 201);

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            $cross_tracker_report["expert_query"],
            'WITHOUT PARENT'
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    private function getMatchingArtifactsFromJson()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'cross_tracker_reports/1/content?limit=50&offset=0'));

        $this->assertEquals($response->getStatusCode(), 200);
        $cross_tracker_artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $cross_tracker_artifacts['artifacts'];
    }

    private function allEpicArtifactsMustBeRetrievedByQuery()
    {
        $cross_tracker_artifacts = $this->getMatchingArtifactsFromJson();

        $this->assertEquals(
            count($cross_tracker_artifacts),
            8
        );

        $this->assertEquals($cross_tracker_artifacts[0]['id'], $this->epic_artifact_ids[8]);
        $this->assertEquals($cross_tracker_artifacts[1]['id'], $this->epic_artifact_ids[7]);
        $this->assertEquals($cross_tracker_artifacts[2]['id'], $this->epic_artifact_ids[6]);
        $this->assertEquals($cross_tracker_artifacts[3]['id'], $this->epic_artifact_ids[5]);
        $this->assertEquals($cross_tracker_artifacts[4]['id'], $this->epic_artifact_ids[4]);
        $this->assertEquals($cross_tracker_artifacts[5]['id'], $this->epic_artifact_ids[3]);
        $this->assertEquals($cross_tracker_artifacts[6]['id'], $this->epic_artifact_ids[2]);
        $this->assertEquals($cross_tracker_artifacts[7]['id'], $this->epic_artifact_ids[1]);

        $this->assertNotEmpty($cross_tracker_artifacts[0]['title']);
        $this->assertNotEmpty($cross_tracker_artifacts[1]['title']);
        $this->assertNotEmpty($cross_tracker_artifacts[2]['title']);
        $this->assertNotEmpty($cross_tracker_artifacts[3]['title']);
        $this->assertNotEmpty($cross_tracker_artifacts[4]['title']);
        $this->assertNotEmpty($cross_tracker_artifacts[5]['title']);
        $this->assertNotEmpty($cross_tracker_artifacts[6]['title']);
        $this->assertNotEmpty($cross_tracker_artifacts[7]['title']);
    }

    private function getMatchingEpicArtifactByIds(array $artifact_ids)
    {
        $cross_tracker_artifacts = $this->getMatchingArtifactsFromJson();

        $this->assertEquals(
            count($cross_tracker_artifacts),
            count($artifact_ids)
        );

        foreach ($artifact_ids as $key => $artifact_id) {
            $this->assertEquals($cross_tracker_artifacts[$key]['id'], $artifact_id);
        }
    }
}
