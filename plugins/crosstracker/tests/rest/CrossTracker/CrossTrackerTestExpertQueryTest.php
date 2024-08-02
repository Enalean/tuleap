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

declare(strict_types=1);

namespace Tuleap\CrossTracker\REST\v1;

use RestBase;

final class CrossTrackerTestExpertQueryTest extends RestBase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->getEpicArtifactIds();
    }

    public function testWithParent(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'WITH PARENT',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'WITH PARENT',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testWithParentArtifact(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'WITH PARENT ARTIFACT = 123',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'WITH PARENT ARTIFACT = 123',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testWithParentTracker(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'WITH PARENT TRACKER = "epic"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'WITH PARENT TRACKER = "epic"',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testWithoutParent(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'WITHOUT PARENT',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'WITHOUT PARENT',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testWithoutParentArtifact(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'WITHOUT PARENT ARTIFACT = 123',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'WITHOUT PARENT ARTIFACT = 123',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testWithoutParentTracker(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'WITHOUT PARENT TRACKER = "epic"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'WITHOUT PARENT TRACKER = "epic"',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsLinkedFrom(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS LINKED FROM WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS LINKED FROM WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsLinkedFromArtifact(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS LINKED FROM ARTIFACT = 123 WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS LINKED FROM ARTIFACT = 123 WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsLinkedFromTracker(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS LINKED FROM TRACKER = "epic" WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS LINKED FROM TRACKER = "epic" WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsNotLinkedFrom(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS NOT LINKED FROM WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS NOT LINKED FROM WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsNotLinkedFromArtifact(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS NOT LINKED FROM ARTIFACT = 123 WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS NOT LINKED FROM ARTIFACT = 123 WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsNotLinkedFromTracker(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS NOT LINKED FROM TRACKER = "epic" WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS NOT LINKED FROM TRACKER = "epic" WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testWithChildren(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'WITH CHILDREN',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'WITH CHILDREN',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testWithChildrenArtifact(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'WITH CHILDREN ARTIFACT = 123',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'WITH CHILDREN ARTIFACT = 123',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testWithChildrenTracker(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'WITH CHILDREN TRACKER = "epic"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'WITH CHILDREN TRACKER = "epic"',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testWithoutChildren(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'WITHOUT CHILDREN',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'WITHOUT CHILDREN',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testWithoutChildrenArtifact(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'WITHOUT CHILDREN ARTIFACT = 123',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'WITHOUT CHILDREN ARTIFACT = 123',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testWithoutChildrenTracker(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'WITHOUT CHILDREN TRACKER = "epic"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'WITHOUT CHILDREN TRACKER = "epic"',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsLinkedToWithType(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS LINKED TO WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS LINKED TO WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsLinkedToArtifact(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS LINKED TO ARTIFACT = 123 WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS LINKED TO ARTIFACT = 123 WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsLinkedToTracker(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS LINKED TO TRACKER = "epic" WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS LINKED TO TRACKER = "epic" WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsLinkedToNotTracker(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS LINKED TO TRACKER != "epic" WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS LINKED TO TRACKER != "epic" WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsLinkedTo(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS LINKED TO',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS LINKED TO',
            $cross_tracker_report['expert_query']
        );

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsNotLinkedToWithType(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS NOT LINKED TO WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS NOT LINKED TO WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsNotLinkedToArtifact(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS NOT LINKED TO ARTIFACT = 123 WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS NOT LINKED TO ARTIFACT = 123 WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsNotLinkedToTracker(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS NOT LINKED TO TRACKER = "epic" WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS NOT LINKED TO TRACKER = "epic" WITH TYPE "_is_child"',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsNotLinkedToNotTracker(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS NOT LINKED TO TRACKER != "epic" WITH TYPE "_is_child"',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertStringContainsString(
            'Double negative',
            $body['error']['message']
        );
    }

    public function testIsNotLinkedTo(): void
    {
        $params = [
            'trackers_id'  => [$this->epic_tracker_id],
            'expert_query' => 'IS NOT LINKED TO',
            'report_mode'  => 'default',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            'IS NOT LINKED TO',
            $cross_tracker_report['expert_query']
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    private function getMatchingArtifactsFromJson()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'cross_tracker_reports/1/content?limit=50&offset=0&report_mode=default'));

        self::assertEquals(200, $response->getStatusCode());
        $cross_tracker_artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $cross_tracker_artifacts['artifacts'];
    }

    private function allEpicArtifactsMustBeRetrievedByQuery(): void
    {
        $cross_tracker_artifacts = $this->getMatchingArtifactsFromJson();

        self::assertCount(8, $cross_tracker_artifacts);

        self::assertEquals($cross_tracker_artifacts[0]['id'], $this->epic_artifact_ids[8]);
        self::assertEquals($cross_tracker_artifacts[1]['id'], $this->epic_artifact_ids[7]);
        self::assertEquals($cross_tracker_artifacts[2]['id'], $this->epic_artifact_ids[6]);
        self::assertEquals($cross_tracker_artifacts[3]['id'], $this->epic_artifact_ids[5]);
        self::assertEquals($cross_tracker_artifacts[4]['id'], $this->epic_artifact_ids[4]);
        self::assertEquals($cross_tracker_artifacts[5]['id'], $this->epic_artifact_ids[3]);
        self::assertEquals($cross_tracker_artifacts[6]['id'], $this->epic_artifact_ids[2]);
        self::assertEquals($cross_tracker_artifacts[7]['id'], $this->epic_artifact_ids[1]);

        self::assertNotEmpty($cross_tracker_artifacts[0]['title']);
        self::assertNotEmpty($cross_tracker_artifacts[1]['title']);
        self::assertNotEmpty($cross_tracker_artifacts[2]['title']);
        self::assertNotEmpty($cross_tracker_artifacts[3]['title']);
        self::assertNotEmpty($cross_tracker_artifacts[4]['title']);
        self::assertNotEmpty($cross_tracker_artifacts[5]['title']);
        self::assertNotEmpty($cross_tracker_artifacts[6]['title']);
        self::assertNotEmpty($cross_tracker_artifacts[7]['title']);
    }
}
