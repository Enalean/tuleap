<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
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

namespace Tuleap\Tracker\Tests\REST\Artifacts;

use Guzzle\Http\Message\Response;
use REST_TestDataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

final class ArtifactsTest extends TrackerBase
{
    public function testGetArtifactWithMinimalStructure(): void
    {
        $artifact_id = end($this->base_artifact_ids);

        $response = $this->getResponse(
            $this->client->get("artifacts/$artifact_id", null, null)
        );

        $this->assertArtifactWithMinimalStructure($response);
    }

    public function testGetArtifactWithCompleteStructure(): void
    {
        $artifact_id = end($this->base_artifact_ids);

        $response = $this->getResponse(
            $this->client->get("artifacts/$artifact_id?tracker_structure_format=complete", null, null)
        );

        $this->assertArtifactWithCompleteStructure($response);
    }

    public function testGetArtifactWithMinimalStructureWithUserRESTReadOnlyAdmin(): void
    {
        $artifact_id = end($this->base_artifact_ids);

        $response = $this->getResponse(
            $this->client->get("artifacts/$artifact_id", null, null),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertArtifactWithMinimalStructure($response);
    }

    public function testGetArtifactWithCompleteStructureWithUserRESTReadOnlyAdmin(): void
    {
        $artifact_id = end($this->base_artifact_ids);

        $response = $this->getResponse(
            $this->client->get("artifacts/$artifact_id?tracker_structure_format=complete", null, null),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertArtifactWithCompleteStructure($response);
    }

    private function assertArtifactWithMinimalStructure(Response $response): void
    {
        $json = $response->json();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->base_tracker_id, $json['tracker']['id']);
        $this->assertFalse(isset($json['tracker']['fields']));
    }

    private function assertArtifactWithCompleteStructure(Response $response): void
    {
        $json = $response->json();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->base_tracker_id, $json['tracker']['id']);
        $this->assertTrue(isset($json['tracker']['fields']));
    }
}
