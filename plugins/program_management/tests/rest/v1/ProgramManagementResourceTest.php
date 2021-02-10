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

class ProgramManagementResourceTest extends \RestBase
{
    /**
     * @depends Tuleap\ProgramManagement\REST\v1\ProjectResourceTest::class
     */
    public function testPUTHierarchy(): void
    {
        $program_id = $this->getProgramProjectId();
        $team_id    = $this->getTeamProjectId();

        $hierarchy_definition = json_encode(
            [
                "program_tracker_id" => $this->tracker_ids[$program_id]['bug'],
                "team_tracker_ids" => [$this->tracker_ids[$team_id]['story']]
            ]
        );

        $response = $this->getResponse(
            $this->client->put('program/' . $program_id . '/hierarchy', null, $hierarchy_definition),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        self::assertEquals(200, $response->getStatusCode());
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
