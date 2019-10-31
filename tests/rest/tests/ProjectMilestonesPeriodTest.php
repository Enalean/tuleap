<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\REST;

use REST_TestDataBuilder;

/**
 * @group ProjectTests
 */
class ProjectMilestonesPeriodTest extends ProjectBase
{
    public function testGETmilestonesWithPeriodFutureQuery(): void
    {
        $query    = urlencode(json_encode(["period" => "future"]));
        $response = $this->getResponse(
            $this->client->get(
                'projects/' . $this->project_future_releases_id . '/milestones?query=' . $query
            )
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $milestones = $response->json();
        $this->assertCount(3, $milestones);
    }

    public function testGETonlyOpenedMilestonesWithPeriodCurrentQuery(): void
    {
        $query    = urlencode(json_encode(["period" => "current"]));
        $response = $this->getResponse(
            $this->client->get(
                'projects/' . $this->project_future_releases_id . '/milestones?query=' . $query
            )
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $milestones = $response->json();
        $this->assertCount(4, $milestones);
        $this->assertEquals('open', $milestones[0]['semantic_status']);
        $this->assertEquals('open', $milestones[1]['semantic_status']);
        $this->assertEquals('open', $milestones[2]['semantic_status']);
        $this->assertEquals('open', $milestones[3]['semantic_status']);
    }

    public function testGETmilestonesWithPeriodCurrentQueryWithRESTReadOnlyUser(): void
    {
        $query    = urlencode(json_encode(["period" => "current"]));
        $response = $this->getResponse(
            $this->client->get(
                'projects/' . $this->project_future_releases_id . '/milestones?query=' . $query
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $milestones = $response->json();
        $this->assertCount(4, $milestones);
    }
}
