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

use Tuleap\REST\RESTTestDataBuilder;
use Tuleap\REST\RestBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('PlanningTests')]
class PlanningTest extends RestBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function testOptionsPlannings(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/plannings'));
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOptionsPlanningsWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/plannings'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGetPlanningsContainsAReleasePlanning(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/plannings'));

        $this->assertPlannigAndReleasePlanning($response);
    }

    public function testGetPlanningsContainsAReleasePlanningWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/plannings'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertPlannigAndReleasePlanning($response);
    }

    private function assertPlannigAndReleasePlanning(\Psr\Http\Message\ResponseInterface $response): void
    {
        $plannings = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $plannings);

        $release_planning = $plannings[0];
        $this->assertArrayHasKey('id', $release_planning);
        $this->assertEquals($release_planning['label'], 'Release Planning');
        $this->assertEquals($release_planning['project'], [
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => null,
            'icon' => '',
        ]);
        $this->assertArrayHasKey('id', $release_planning['milestone_tracker']);
        $this->assertArrayHasKey('uri', $release_planning['milestone_tracker']);
        $this->assertMatchesRegularExpression('%^trackers/[0-9]+$%', $release_planning['milestone_tracker']['uri']);
        $this->assertCount(1, $release_planning['backlog_trackers']);
        $this->assertEquals($release_planning['milestones_uri'], 'plannings/' . $release_planning['id'] . '/milestones');

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testReleasePlanningHasNoMilestone(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', $this->getMilestonesUri()));

        $this->assertCount(1, json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testReleasePlanningHasNoMilestoneWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', $this->getMilestonesUri()),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertCount(1, json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testReleasePlanningHasNoMilestone')]
    public function testPlanningMilestonesArePaginatedCorrectly(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', $this->getMilestonesUri() . '?limit=0'));

        $pagination_size  = (int) $response->getHeaderLine('X-PAGINATION-SIZE');
        $pagination_limit = (int) $response->getHeaderLine('X-PAGINATION-LIMIT');

        $this->assertEquals(0, $pagination_limit);
        $this->assertGreaterThanOrEqual(1, $pagination_size);
    }

    private function getMilestonesUri(): string
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/plannings')
        );

        $response_plannings = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $response_plannings[0]['milestones_uri'];
    }
}
