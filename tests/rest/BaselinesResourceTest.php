<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

use RestBase;

class BaselinesResourceTest extends RestBase
{
    private const TEST_USER_NAME = 'rest_api_tester_1';
    private const PROJECT_NAME = "baseline-test";
    private const TRACKER_NAME = "base";
    private const ARTIFACT_TITLE = 'new title';

    /** @var int */
    private $an_artifact_id;

    public function setUp(): void
    {
        parent::setUp();

        $artifact_ids_by_title = $this->getArtifactIdsIndexedByTitle(self::PROJECT_NAME, self::TRACKER_NAME);
        $this->an_artifact_id  = $artifact_ids_by_title[self::ARTIFACT_TITLE];
    }

    public function testPost()
    {
        $response = $this->getResponseByName(
            self::TEST_USER_NAME,
            $this->client->post(
                'baselines',
                null,
                json_encode(
                    [
                        'name'        => 'new baseline',
                        'artifact_id' => $this->an_artifact_id
                    ]
                )
            )
        );
        $this->assertEquals(201, $response->getStatusCode());
        $json_response = $response->json();

        $this->assertNotNull($json_response['id']);
        $this->assertEquals('new baseline', $json_response['name']);
        $this->assertEquals($this->user_ids[self::TEST_USER_NAME], $json_response['author_id']);
        $this->assertNotNull($json_response['snapshot_date']);
    }

    /**
     * @depends testPost
     */
    public function testGetById()
    {
        $baseline = $this->createABaseline($this->an_artifact_id);

        $response      = $this->getResponseByName(
            self::TEST_USER_NAME,
            $this->client->get('baselines/' . $baseline['id'])
        );
        $json_response = $response->json();

        $this->assertEquals($baseline['id'], $json_response['id']);
        $this->assertEquals($baseline['name'], $json_response['name']);
        $this->assertEquals($baseline['artifact_id'], $json_response['artifact_id']);
        $this->assertEquals($baseline['snapshot_date'], $json_response['snapshot_date']);
        $this->assertEquals($baseline['author_id'], $json_response['author_id']);
    }

    /**
     * @depends testPost
     */
    public function testGetByProject()
    {
        $project_id    = $this->project_ids[self::PROJECT_NAME];
        $url           = 'projects/' . $project_id . '/baselines?limit=2';
        $response      = $this->getResponseByName(
            self::TEST_USER_NAME,
            $this->client->get($url)
        );
        $json_response = $response->json();

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

    public function testGetBaselineArtifacts()
    {
        $baseline = $this->createABaseline($this->an_artifact_id);

        $response = $this->getResponseByName(
            self::TEST_USER_NAME,
            $this->client->get('baselines/' . $baseline['id'] . '/artifacts')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $json_response = $response->json();

        $this->assertNotNull($json_response['artifacts']);
        $this->assertGreaterThanOrEqual(0, count($json_response['artifacts']));
    }

    public function testGetBaselineArtifactsWithIds()
    {
        $baseline = $this->createABaseline($this->an_artifact_id);

        $query    = json_encode(["ids" => [$this->an_artifact_id]]);
        $url      = 'baselines/' . $baseline['id'] . '/artifacts?query=' . urlencode($query);
        $response = $this->getResponseByName(
            self::TEST_USER_NAME,
            $this->client->get($url)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $json_response = $response->json();

        $this->assertNotNull($json_response['artifacts']);
        $artifacts_response = $json_response['artifacts'];
        $this->assertCount(1, $artifacts_response);

        $artifact_response = $artifacts_response[0];
        $this->assertEquals($this->an_artifact_id, $artifact_response['id']);
        $this->assertNotNull('new title', $artifact_response['title']);
        $this->assertNotNull('Base', $artifact_response['tracker_name']);
    }

    private function createABaseline(int $artifact_id): array
    {
        $response = $this->getResponseByName(
            self::TEST_USER_NAME,
            $this->client->post(
                'baselines',
                null,
                json_encode(
                    [
                        'name'        => 'created baseline',
                        'artifact_id' => $artifact_id
                    ]
                )
            )
        );
        return $response->json();
    }
}
