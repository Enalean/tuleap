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
    private $a_milestone_id;

    public function setUp(): void
    {
        parent::setUp();

        $artifact_ids_by_title = $this->getArtifactIdsIndexedByTitle(self::PROJECT_NAME, self::TRACKER_NAME);
        $this->a_milestone_id  = $artifact_ids_by_title[self::ARTIFACT_TITLE];
    }

    public function testGetByArtifactIdAndDate()
    {
        $url           = 'baselines/?' . http_build_query(
                [
                    'artifact_id' => $this->a_milestone_id,
                    "date"        => "2017-09-02"
                ]
            );
        $response      = $this->getResponse($this->client->get($url));
        $json_response = $response->json();

        $this->assertEquals("old title", $json_response['artifact_title']);
        $this->assertEquals(1479378846, $json_response['last_modification_date_before_baseline_date']);
        $this->assertEquals("To be done", $json_response['artifact_status']);
        $this->assertEquals("Artifact that will be moved in another tracker", $json_response['artifact_description']);
    }

    public function testPost()
    {
        $response      = $this->getResponseByName(
            self::TEST_USER_NAME,
            $this->client->post(
                'baselines',
                null,
                json_encode(
                    [
                        'name'         => 'new baseline',
                        'milestone_id' => $this->a_milestone_id
                    ]
                )
            )
        );
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
        $baseline = $this->createABaseline();

        $response      = $this->getResponseByName(
            self::TEST_USER_NAME,
            $this->client->get('baselines/' . $baseline['id'])
        );
        $json_response = $response->json();

        $this->assertEquals($baseline['id'], $json_response['id']);
        $this->assertEquals($baseline['name'], $json_response['name']);
        $this->assertEquals($baseline['milestone_id'], $json_response['milestone_id']);
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
        $this->assertNotNull($baseline_response['milestone_id']);
        $this->assertNotNull($baseline_response['snapshot_date']);
        $this->assertNotNull($baseline_response['author_id']);
    }

    private function createABaseline(): array
    {
        $response = $this->getResponseByName(
            self::TEST_USER_NAME,
            $this->client->post(
                'baselines',
                null,
                json_encode(
                    [
                        'name'         => 'created baseline',
                        'milestone_id' => $this->a_milestone_id
                    ]
                )
            )
        );
        return $response->json();
    }
}
