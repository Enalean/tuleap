<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Guzzle\Http\Message\Response;
use Tuleap\REST\MilestoneBase;

/**
 * @group MilestonesTest
 */
class MilestoneBurndownTest extends MilestoneBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function testOPTIONSBurndown(): void
    {
        $response = $this->getResponse(
            $this->client->options('milestones/' . $this->sprint_artifact_ids[1] . '/burndown')
        );
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSBurndownWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->options('milestones/' . $this->sprint_artifact_ids[1] . '/burndown'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGetBurndown(): void
    {
        $response = $this->getResponse($this->client->get('milestones/' . $this->sprint_artifact_ids[1] . '/burndown'));

        $this->assertGETBurndown($response);
    }

    public function testGetBurndownWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->get('milestones/' . $this->sprint_artifact_ids[1] . '/burndown'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETBurndown($response);
    }

    private function assertGETBurndown(Response $response): void
    {
        $burndown = $response->json();

        $this->assertEquals(10, $burndown['duration']);
        $this->assertEquals(29, $burndown['capacity']);
        $this->assertCount(0, $burndown['points']);
        $this->assertCount(0, $burndown['points_with_date']);
    }
}
