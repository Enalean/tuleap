<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST;

require_once dirname(__FILE__) . '/bootstrap.php';

class ArtifactsTest extends ArtifactBase
{
    public function testGETReleaseBurnup()
    {
        $response = $this->getResponse(
            $this->client->get("artifacts/" . $this->burnup_artifact_ids[1])
        );

        $burnup = $response->json();

        $this->assertEquals(200, $response->getStatusCode());

        $expected_burnup_chart = [
            ["date" => '2017-12-11T23:59:59+01:00', "team_effort" => 0, "total_effort" => 0],
            ["date" => '2017-12-12T23:59:59+01:00', "team_effort" => 0, "total_effort" => 0],
            ["date" => '2017-12-13T23:59:59+01:00', "team_effort" => 20, "total_effort" => 100],
            ["date" => '2017-12-14T23:59:59+01:00', "team_effort" => 20, "total_effort" => 100],
            ["date" => '2017-12-15T23:59:59+01:00', "team_effort" => 20, "total_effort" => 100],
            ["date" => '2017-12-18T23:59:59+01:00', "team_effort" => 30, "total_effort" => 110],
            ["date" => '2017-12-19T23:59:59+01:00', "team_effort" => 30, "total_effort" => 110],
            ["date" => '2017-12-20T23:59:59+01:00', "team_effort" => 30, "total_effort" => 110]
        ];

        foreach ($burnup['values'] as $field) {
            if ($field['label'] === ArtifactBase::BURNUP_FIELD_SHORTNAME) {
                $this->assertArrayHasKey('points_with_date_count_elements', $field['value']);
                $this->assertEquals($expected_burnup_chart, $field['value']['points_with_date']);
            }
        }
    }
}
