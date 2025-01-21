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

use Psr\Http\Message\ResponseInterface;
use REST_TestDataBuilder;
use RestBase;
use function Psl\Json\decode;
use function Psl\Json\encode;

final class CrossTrackerTest extends RestBase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->getEpicArtifactIds();
    }

    public function testGetId(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'cross_tracker_reports/1'));

        self::assertSame(200, $response->getStatusCode());
        $this->assertGetIdReport($response);
    }

    public function testGetIdForReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'cross_tracker_reports/1'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(200, $response->getStatusCode());
        $this->assertGetIdReport($response);
    }

    private function assertGetIdReport(ResponseInterface $response): void
    {
        $expected_cross_tracker = [
            'id'               => 1,
            'uri'              => 'cross_tracker_reports/1',
            'expert_query'     => '',
            'trackers'         => [],
            'report_mode'      => 'expert',
            'title'            => '',
            'description'      => '',
        ];

        self::assertEquals($expected_cross_tracker, decode($response->getBody()->getContents()));
    }

    public function testPut(): void
    {
        $params   = [
            'trackers_id'  => [],
            'expert_query' => "SELECT @id FROM @project = 'self' WHERE @id = {$this->epic_artifact_ids[1]}",
        ];
        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(encode($params))));

        self::assertSame(201, $response->getStatusCode());

        $expected_cross_tracker = [
            'id'           => 3,
            'uri'          => 'cross_tracker_reports/3',
            'expert_query' => "SELECT @id FROM @project = 'self' WHERE @id = {$this->epic_artifact_ids[1]}",
            'trackers'     => [],
            'report_mode'  => 'expert',
            'title'        => '',
            'description'  => '',
        ];

        self::assertEquals($expected_cross_tracker, decode($response->getBody()->getContents()));
    }

    public function testPutForReadOnlyUser(): void
    {
        $params   = [
            'trackers_id'  => [],
            'expert_query' => "SELECT @id FROM @project = 'self' WHERE @id = {$this->epic_artifact_ids[1]}",
        ];
        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'cross_tracker_reports/3')->withBody($this->stream_factory->createStream(encode($params))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testGetContentIdForReadOnlyUser(): void
    {
        $query    = encode([
            'expert_query' => "SELECT @id FROM @project = 'self' WHERE @id = {$this->epic_artifact_ids[1]}",
        ]);
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'cross_tracker_reports/3/content?limit=50&offset=0&query=' . urlencode($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(200, $response->getStatusCode());
        $report = decode($response->getBody()->getContents());
        self::assertCount(2, $report['selected']);
        self::assertSame('@artifact', $report['selected'][0]['name']);
        self::assertSame('@id', $report['selected'][1]['name']);

        self::assertCount(1, $report['artifacts']);
        self::assertEquals($this->epic_artifact_ids[1], $report['artifacts'][0]['@id']['value']);
    }

    public function testGetContentIdWithExpertMode(): void
    {
        $query    = encode([
            'expert_query' => "SELECT @id FROM @project = 'self' WHERE @id = {$this->epic_artifact_ids[1]}",
        ]);
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'cross_tracker_reports/3/content?report_mode=expert&limit=50&offset=0&query=' . urlencode($query)),
        );

        self::assertSame(200, $response->getStatusCode());
        $report = decode($response->getBody()->getContents());
        self::assertCount(2, $report['selected']);
        self::assertSame('@artifact', $report['selected'][0]['name']);
        self::assertSame('@id', $report['selected'][1]['name']);

        self::assertCount(1, $report['artifacts']);
        self::assertEquals($this->epic_artifact_ids[1], $report['artifacts'][0]['@id']['value']);
    }

    public function testGetReportWithoutArtifacts(): void
    {
        $query    = encode([
            'expert_query' => "SELECT @id FROM @project = 'self' WHERE @id < 1",
        ]);
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'cross_tracker_reports/3/content?report_mode=expert&limit=50&offset=0&query=' . urlencode($query)),
        );

        self::assertSame(200, $response->getStatusCode());
        $cross_tracker_artifacts = decode($response->getBody()->getContents());

        self::assertEmpty($cross_tracker_artifacts['artifacts']);
    }

    private function assertGetReport(array $cross_tracker_artifacts): void
    {
        self::assertCount(5, $cross_tracker_artifacts['artifacts']);

        self::assertEquals($this->epic_artifact_ids[7], $cross_tracker_artifacts['artifacts'][0]['id']);
        self::assertEquals($this->epic_artifact_ids[6], $cross_tracker_artifacts['artifacts'][1]['id']);
        self::assertEquals($this->epic_artifact_ids[5], $cross_tracker_artifacts['artifacts'][2]['id']);
        self::assertEquals($this->epic_artifact_ids[4], $cross_tracker_artifacts['artifacts'][3]['id']);
        self::assertEquals($this->epic_artifact_ids[1], $cross_tracker_artifacts['artifacts'][4]['id']);
    }
}
