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

namespace Tuleap\Baseline\Tests\REST;

use RestBase;

class BaselinesResourceTest extends RestBase
{
    public function testGetByArtifactIdAndDate()
    {
        $artifact = $this->fetchArtifact();

        $url           = 'baselines/?' . http_build_query(['artifact_id' => $artifact['id'], "date" => "2017-09-02"]);
        $response      = $this->getResponse($this->client->get($url));
        $json_response = $response->json();

        $this->assertEquals("old title", $json_response['artifact_title']);
        $this->assertEquals(1479378846, $json_response['last_modification_date_before_baseline_date']);
        $this->assertEquals("To be done", $json_response['artifact_status']);
        $this->assertEquals("Artifact that will be moved in another tracker", $json_response['artifact_description']);
    }

    public function fetchArtifact(): array
    {
        $project_id = $this->project_ids["baseline-test"];
        $trackers   = $this->tracker_ids[$project_id];
        return $this->getArtifacts($trackers['base'])[0];
    }
}
