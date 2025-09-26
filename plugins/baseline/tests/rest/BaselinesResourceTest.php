<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Baseline\Tests\REST;

require_once __DIR__ . '/BaselineFixtureData.php';

use Tuleap\REST\RestBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BaselinesResourceTest extends RestBase
{
    /** @var int */
    private $an_artifact_id;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $artifact_ids_by_title = $this->getArtifactIdsIndexedByTitle(
            BaselineFixtureData::PROJECT_NAME,
            BaselineFixtureData::TRACKER_NAME
        );
        $this->an_artifact_id  = $artifact_ids_by_title[BaselineFixtureData::ARTIFACT_TITLE];
    }

    public function testPost(): void
    {
        $response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->request_factory->createRequest('POST', 'baselines')->withBody($this->stream_factory->createStream(json_encode(
                [
                    'name'          => 'new baseline',
                    'artifact_id'   => $this->an_artifact_id,
                    'snapshot_date' => '2019-03-21T11:47:04+02:00',
                ]
            )))
        );
        $this->assertEquals(201, $response->getStatusCode());
        $json_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($json_response['id']);
        $this->assertEquals('new baseline', $json_response['name']);
        $this->assertEquals($this->user_ids[BaselineFixtureData::TEST_USER_NAME], $json_response['author_id']);
        $this->assertEquals('2019-03-21T11:47:04+02:00', $json_response['snapshot_date']);
    }

    public function testPOSTWithReadOnlyAdmin(): void
    {
        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->request_factory->createRequest('POST', 'baselines')->withBody($this->stream_factory->createStream(json_encode(
                [
                    'name'          => 'new baseline',
                    'artifact_id'   => $this->an_artifact_id,
                    'snapshot_date' => '2019-03-21T11:47:04+02:00',
                ]
            )))
        );
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetById(): void
    {
        $baseline = $this->createABaseline($this->an_artifact_id);
        $response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->request_factory->createRequest('GET', 'baselines/' . $baseline['id'])
        );

        $this->assertGETById($baseline, $response);
    }

    public function testGetByIdWithReadOnlyAdmin(): void
    {
        $baseline = $this->createABaseline($this->an_artifact_id);
        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->request_factory->createRequest('GET', 'baselines/' . $baseline['id'])
        );

        $this->assertGETById($baseline, $response);
    }

    private function assertGETById(array $baseline, \Psr\Http\Message\ResponseInterface $response): void
    {
        $this->assertEquals(200, $response->getStatusCode());

        $json_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($baseline['id'], $json_response['id']);
        $this->assertEquals($baseline['name'], $json_response['name']);
        $this->assertEquals($baseline['artifact_id'], $json_response['artifact_id']);
        $this->assertEquals($baseline['snapshot_date'], $json_response['snapshot_date']);
        $this->assertEquals($baseline['author_id'], $json_response['author_id']);
    }

    public function testDelete(): void
    {
        $baseline = $this->createABaseline($this->an_artifact_id);

        $delete_response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'baselines/' . $baseline['id'])
        );
        $this->assertEquals(200, $delete_response->getStatusCode());

        $get_response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->request_factory->createRequest('GET', 'baselines/' . $baseline['id'])
        );
        $this->assertEquals(404, $get_response->getStatusCode());
    }

    public function testDeleteWithReadOnlyAdmin(): void
    {
        $baseline = $this->createABaseline($this->an_artifact_id);

        $delete_response = $this->getResponseForReadOnlyUserAdmin(
            $this->request_factory->createRequest('DELETE', 'baselines/' . $baseline['id'])
        );
        $this->assertEquals(404, $delete_response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPost')]
    public function testGetByProject(): void
    {
        $project_id = $this->project_ids[BaselineFixtureData::PROJECT_NAME];
        $url        = 'projects/' . $project_id . '/baselines?limit=2';
        $response   = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->request_factory->createRequest('GET', $url)
        );

        $this->assertGETByProject($response);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPost')]
    public function testGETByProjectWithReadOnlyAdmin(): void
    {
        $project_id = $this->project_ids[BaselineFixtureData::PROJECT_NAME];
        $url        = 'projects/' . $project_id . '/baselines?limit=2';
        $response   = $this->getResponseForReadOnlyUserAdmin(
            $this->request_factory->createRequest('GET', $url)
        );

        $this->assertGETByProject($response);
    }

    private function assertGETByProject(\Psr\Http\Message\ResponseInterface $response): void
    {
        $this->assertEquals(200, $response->getStatusCode());

        $json_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertGreaterThanOrEqual(1, $json_response['total_count']);

        $baselines_response = $json_response['baselines'];
        $this->assertGreaterThanOrEqual(1, count($baselines_response));
        $this->assertLessThanOrEqual(2, count($baselines_response));

        $baseline_response = $baselines_response[0];
        $this->assertNotNull($baseline_response['id']);
        $this->assertNotNull($baseline_response['name']);
        $this->assertNotNull($baseline_response['artifact_id']);
        $this->assertNotNull($baseline_response['snapshot_date']);
        $this->assertNotNull($baseline_response['author_id']);
    }

    public function testGetBaselineArtifacts(): void
    {
        $baseline = $this->createABaseline($this->an_artifact_id);

        $response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->request_factory->createRequest('GET', 'baselines/' . $baseline['id'] . '/artifacts')
        );

        $this->assertGETBaselineArtifacts($response);
    }

    public function testGETBaselineArtifactsWithReadOnlyAdmin(): void
    {
        $baseline = $this->createABaseline($this->an_artifact_id);

        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->request_factory->createRequest('GET', 'baselines/' . $baseline['id'] . '/artifacts')
        );

        $this->assertGETBaselineArtifacts($response);
    }

    private function assertGETBaselineArtifacts(\Psr\Http\Message\ResponseInterface $response): void
    {
        $this->assertEquals(200, $response->getStatusCode());
        $json_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($json_response['artifacts']);
        $this->assertGreaterThanOrEqual(0, count($json_response['artifacts']));
    }

    public function testGetBaselineArtifactsWithIds(): void
    {
        $baseline = $this->createABaseline($this->an_artifact_id);
        $query    = json_encode(['ids' => [$this->an_artifact_id]]);
        $url      = 'baselines/' . $baseline['id'] . '/artifacts?query=' . urlencode($query);
        $response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->request_factory->createRequest('GET', $url)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $json_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($json_response['artifacts']);
        $artifacts_response = $json_response['artifacts'];
        $this->assertCount(1, $artifacts_response);

        $artifact_response = $artifacts_response[0];
        $this->assertEquals($this->an_artifact_id, $artifact_response['id']);
        $this->assertNotNull('new title', $artifact_response['title']);
        $this->assertNotNull('base', $artifact_response['tracker_name']);
    }

    private function createABaseline(int $artifact_id): array
    {
        $response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->request_factory->createRequest('POST', 'baselines')->withBody($this->stream_factory->createStream(json_encode(
                [
                    'name'        => 'created baseline',
                    'artifact_id' => $artifact_id,
                ]
            )))
        );
        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }
}
