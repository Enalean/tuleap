<?php
/**
 * Copyright Enalean (c) 2019. All rights reserved.
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

use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__.'/../bootstrap.php';

class ArtifactsTest extends TrackerBase
{
    public function testGetArtifactWithMinimalStructure()
    {
        $artifact_id = end($this->base_artifact_ids);

        $response = $this->getResponse(
            $this->client->get("artifacts/$artifact_id", null, null)
        );

        $json = $response->json();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->base_tracker_id, $json['tracker']['id']);
        $this->assertFalse(isset($json['tracker']['fields']));
    }

    public function testGetArtifactWithCompleteStructure()
    {
        $artifact_id = end($this->base_artifact_ids);

        $response = $this->getResponse(
            $this->client->get("artifacts/$artifact_id?tracker_structure_format=complete", null, null)
        );

        $json = $response->json();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->base_tracker_id, $json['tracker']['id']);
        $this->assertTrue(isset($json['tracker']['fields']));
    }
}
