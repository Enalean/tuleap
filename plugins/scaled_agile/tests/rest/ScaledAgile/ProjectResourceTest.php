<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\REST\v1;

use REST_TestDataBuilder;

class ProjectResourceTest extends \RestBase
{
    public function testOPTIONS(): void
    {
        $response = $this->getResponse(
            $this->client->options('projects/' . $this->getProgramProjectId() . '/scaled_agile_plan'),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['OPTIONS', 'PUT'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testPUTTeam(): void
    {
        $program_id = $this->getProgramProjectId();
        $team_id = $this->getTeamProjectId();

        $team_definition  = json_encode(["team_ids" => [$team_id]]);

        $response = $this->getResponse(
            $this->client->put('projects/' . $program_id . '/scaled_agile_teams', null, $team_definition),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testPUTTeam
     */
    public function testPUTPlan(): void
    {
        $project_id = $this->getProgramProjectId();

        $plan_definition  = json_encode(
            [
                  "program_increment_tracker_id" => $this->tracker_ids[$project_id]['rel'],
                  "plannable_tracker_ids" => [$this->tracker_ids[$project_id]['bug'],$this->tracker_ids[$project_id]['story']]
            ]
        );

        $response = $this->getResponse(
            $this->client->put('projects/' . $project_id . '/scaled_agile_plan', null, $plan_definition),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    private function getProgramProjectId(): int
    {
        return $this->getProjectId('program');
    }

    private function getTeamProjectId(): int
    {
        return $this->getProjectId('team');
    }
}
