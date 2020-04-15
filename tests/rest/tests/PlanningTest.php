<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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

/**
 * @group PlanningTests
 */
class PlanningTest extends RestBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    public function testOptionsPlannings(): void
    {
        $response = $this->getResponse($this->client->options('projects/' . $this->project_private_member_id . '/plannings'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOptionsPlanningsWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse($this->client->options('projects/' . $this->project_private_member_id . '/plannings'));

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGetPlanningsContainsAReleasePlanning(): void
    {
        $response = $this->getResponse($this->client->get('projects/' . $this->project_private_member_id . '/plannings'));

        $this->assertPlannigAndReleasePlanning($response);
    }

    public function testGetPlanningsContainsAReleasePlanningWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->get('projects/' . $this->project_private_member_id . '/plannings'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertPlannigAndReleasePlanning($response);
    }

    private function assertPlannigAndReleasePlanning(Response $response): void
    {
        $plannings = $response->json();

        $this->assertCount(2, $plannings);

        $release_planning = $plannings[0];
        $this->assertArrayHasKey('id', $release_planning);
        $this->assertEquals($release_planning['label'], "Release Planning");
        $this->assertEquals($release_planning['project'], array(
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => null
        ));
        $this->assertArrayHasKey('id', $release_planning['milestone_tracker']);
        $this->assertArrayHasKey('uri', $release_planning['milestone_tracker']);
        $this->assertMatchesRegularExpression('%^trackers/[0-9]+$%', $release_planning['milestone_tracker']['uri']);
        $this->assertCount(1, $release_planning['backlog_trackers']);
        $this->assertEquals($release_planning['milestones_uri'], 'plannings/' . $release_planning['id'] . '/milestones');

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testReleasePlanningHasNoMilestone(): void
    {
        $response = $this->getResponse($this->client->get($this->getMilestonesUri()));

        $this->assertCount(1, $response->json());

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testReleasePlanningHasNoMilestoneWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->get($this->getMilestonesUri()),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertCount(1, $response->json());

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testReleasePlanningHasNoMilestone
     */
    public function testPlanningMilestonesArePaginatedCorrectly(): void
    {
        $response = $this->getResponse($this->client->get($this->getMilestonesUri() . '?limit=0'));

        $pagination_size  = (int) (string) $response->getHeader('X-PAGINATION-SIZE');
        $pagination_limit = (int) (string) $response->getHeader('X-PAGINATION-LIMIT');

        $this->assertEquals(0, $pagination_limit);
        $this->assertGreaterThanOrEqual(1, $pagination_size);
    }

    private function getMilestonesUri(): string
    {
        $response_plannings = $this->getResponse(
            $this->client->get('projects/' . $this->project_private_member_id . '/plannings')
        )->json();

        return $response_plannings[0]['milestones_uri'];
    }
}
