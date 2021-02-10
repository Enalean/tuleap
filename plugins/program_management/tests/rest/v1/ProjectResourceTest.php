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

namespace Tuleap\ProgramManagement\REST\v1;

use REST_TestDataBuilder;

class ProjectResourceTest extends \RestBase
{
    public function testOPTIONS(): void
    {
        $response = $this->getResponse(
            $this->client->options('projects/' . $this->getProgramProjectId() . '/program_plan'),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['OPTIONS', 'PUT'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testPUTTeam(): void
    {
        $program_id = $this->getProgramProjectId();
        $team_id    = $this->getTeamProjectId();

        $team_definition = json_encode(["team_ids" => [$team_id]]);

        $response = $this->getResponse(
            $this->client->put('projects/' . $program_id . '/program_teams', null, $team_definition),
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

        $plan_definition = json_encode(
            [
                  "program_increment_tracker_id" => $this->tracker_ids[$project_id]['rel'],
                  "plannable_tracker_ids" => [$this->tracker_ids[$project_id]['bug'],$this->tracker_ids[$project_id]['story']],
                  "permissions" => ['can_prioritize_features' => ["${project_id}_4"]],
            ]
        );

        $response = $this->getResponse(
            $this->client->put('projects/' . $project_id . '/program_plan', null, $plan_definition),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testPUTTeam
     */
    public function getToBePlannedElements(): void
    {
        $project_id = $this->getProgramProjectId();

        $response = $this->getResponse(
            $this->client->get('projects/' . $project_id . '/program_backlog'),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $to_be_planned = $response->json();
        $this->assertCount(2, $to_be_planned);
        $this->assertEquals(["tracker_name" => "bug", "artifact_id" => 1, "artifact_title" => "My bug"], $to_be_planned[0]);
        $this->assertEquals(["tracker_name" => "user story", "artifact_id" => 2, "artifact_title" => "My story"], $to_be_planned[1]);
    }

    /**
     * @depends testPUTTeam
     */
    public function testGetProgramIncrements(): int
    {
        $project_id = $this->getProgramProjectId();

        $response = $this->getResponse(
            $this->client->get('projects/' . urlencode((string) $project_id) . '/program_increments')
        );

        self::assertEquals(200, $response->getStatusCode());
        $program_increments = $response->json();
        self::assertCount(1, $program_increments);
        self::assertEquals('1.0.0', $program_increments[0]['title']);
        self::assertEquals('In development', $program_increments[0]['status']);
        self::assertNull($program_increments[0]['start_date']);
        self::assertNull($program_increments[0]['end_date']);

        return $program_increments[0]['id'];
    }

    /**
     * @depends testGetProgramIncrements
     */
    public function testGetProgramIncrementContent(int $id): void
    {
        $response = $this->getResponse(
            $this->client->get('program_increment/' . urlencode((string) $id) . '/content')
        );

        self::assertEquals(200, $response->getStatusCode());
        $content = $response->json();
        self::assertGreaterThan(1, $content);
        self::assertEquals('My artifact', $content[0]['artifact_title']);
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
