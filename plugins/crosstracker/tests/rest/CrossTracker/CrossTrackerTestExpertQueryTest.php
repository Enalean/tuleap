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
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE WITH PARENT',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testWithParentArtifact(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE WITH PARENT ARTIFACT = 123',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testWithParentTracker(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE WITH PARENT TRACKER = "epic"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testWithoutParent(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE WITHOUT PARENT',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testWithoutParentArtifact(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE WITHOUT PARENT ARTIFACT = 123',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testWithoutParentTracker(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE WITHOUT PARENT TRACKER = "epic"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsLinkedFrom(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED FROM WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsLinkedFromArtifact(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED FROM ARTIFACT = 123 WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsLinkedFromTracker(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED FROM TRACKER = "epic" WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsNotLinkedFrom(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED FROM WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsNotLinkedFromArtifact(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED FROM ARTIFACT = 123 WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsNotLinkedFromTracker(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED FROM TRACKER = "epic" WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testWithChildren(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE WITH CHILDREN',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testWithChildrenArtifact(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE WITH CHILDREN ARTIFACT = 123',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testWithChildrenTracker(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE WITH CHILDREN TRACKER = "epic"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testWithoutChildren(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE WITHOUT CHILDREN',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testWithoutChildrenArtifact(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE WITHOUT CHILDREN ARTIFACT = 123',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testWithoutChildrenTracker(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE WITHOUT CHILDREN TRACKER = "epic"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsLinkedToWithType(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED TO WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsLinkedToArtifact(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED TO ARTIFACT = 123 WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsLinkedToTracker(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED TO TRACKER = "epic" WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsLinkedToNotTracker(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED TO TRACKER != "epic" WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsLinkedTo(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED TO',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        self::assertEmpty($this->getMatchingArtifactsFromJson());
    }

    public function testIsNotLinkedToWithType(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED TO WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsNotLinkedToArtifact(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED TO ARTIFACT = 123 WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsNotLinkedToTracker(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED TO TRACKER = "epic" WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    public function testIsNotLinkedToNotTracker(): void
    {
        $params = [
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED TO TRACKER != "epic" WITH TYPE "_is_child"',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
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
            'trackers_id'  => [],
            'expert_query' => 'SELECT @id, @title FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED TO',
        ];

        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(json_encode($params))));
        self::assertEquals(201, $response->getStatusCode());

        $cross_tracker_report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($params['expert_query'], $cross_tracker_report['expert_query']);

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    private function getMatchingArtifactsFromJson()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'cross_tracker_reports/3/content?limit=50&offset=0'));

        self::assertEquals(200, $response->getStatusCode());
        $cross_tracker_artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $cross_tracker_artifacts['artifacts'];
    }

    private function allEpicArtifactsMustBeRetrievedByQuery(): void
    {
        $cross_tracker_artifacts = $this->getMatchingArtifactsFromJson();

        self::assertCount(8, $cross_tracker_artifacts);

        self::assertEquals($cross_tracker_artifacts[0]['@id']['value'], $this->epic_artifact_ids[8]);
        self::assertEquals($cross_tracker_artifacts[1]['@id']['value'], $this->epic_artifact_ids[7]);
        self::assertEquals($cross_tracker_artifacts[2]['@id']['value'], $this->epic_artifact_ids[6]);
        self::assertEquals($cross_tracker_artifacts[3]['@id']['value'], $this->epic_artifact_ids[5]);
        self::assertEquals($cross_tracker_artifacts[4]['@id']['value'], $this->epic_artifact_ids[4]);
        self::assertEquals($cross_tracker_artifacts[5]['@id']['value'], $this->epic_artifact_ids[3]);
        self::assertEquals($cross_tracker_artifacts[6]['@id']['value'], $this->epic_artifact_ids[2]);
        self::assertEquals($cross_tracker_artifacts[7]['@id']['value'], $this->epic_artifact_ids[1]);

        self::assertNotEmpty($cross_tracker_artifacts[0]['@title']['value']);
        self::assertNotEmpty($cross_tracker_artifacts[1]['@title']['value']);
        self::assertNotEmpty($cross_tracker_artifacts[2]['@title']['value']);
        self::assertNotEmpty($cross_tracker_artifacts[3]['@title']['value']);
        self::assertNotEmpty($cross_tracker_artifacts[4]['@title']['value']);
        self::assertNotEmpty($cross_tracker_artifacts[5]['@title']['value']);
        self::assertNotEmpty($cross_tracker_artifacts[6]['@title']['value']);
        self::assertNotEmpty($cross_tracker_artifacts[7]['@title']['value']);
    }
}
