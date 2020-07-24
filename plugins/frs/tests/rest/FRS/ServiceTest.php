<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\FRS\Tests\REST;

use REST_TestDataBuilder;
use RestBase;

/**
 * @group FRSTests
 */
final class ServiceTest extends RestBase
{
    public const PROJECT_NAME = 'frs-test';

    private $project_id;

    public function setUp(): void
    {
        parent::setUp();
        $this->project_id = (int) $this->getProjectId(self::PROJECT_NAME);
    }

    public function testOPTIONS(): void
    {
        $response = $this->getResponse($this->client->options(sprintf('projects/%d/frs_service', $this->project_id)));
        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testServiceAsAdmin(): void
    {
        $response = $this->getResponse($this->client->get(sprintf('projects/%d/frs_service', $this->project_id)));
        $service  = $response->json();

        $this->assertFrsService($service);
    }

    public function testServiceAsRandomUser(): void
    {
        $response = $this->getResponse($this->client->get(sprintf('projects/%d/frs_service', $this->project_id)), REST_TestDataBuilder::TEST_USER_5_NAME);
        $service  = $response->json();

        $this->assertNull($service['permissions_for_groups']);
    }

    public function testServiceAsReadOnlyUser(): void
    {
        $response = $this->getResponse($this->client->get(sprintf('projects/%d/frs_service', $this->project_id)), REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $service  = $response->json();

        $this->assertFrsService($service);
    }

    public function testServiceIsInProjectResources(): void
    {
        $response = $this->getResponse($this->client->get(sprintf('projects/%d', $this->project_id)));
        $project  = $response->json();

        $this->assertContains(
            [
                'type' => 'frs_service',
                'uri'  => sprintf('projects/%d/frs_service', $this->project_id),
            ],
            $project['resources']
        );
    }

    private function assertFrsService(array $service): void
    {
        $this->assertCount(1, $service['permissions_for_groups']['can_read']);
        $this->assertEquals('project_members', $service['permissions_for_groups']['can_read'][0]['short_name']);

        $this->assertCount(1, $service['permissions_for_groups']['can_admin']);
        $this->assertEquals('FRS_Admin', $service['permissions_for_groups']['can_admin'][0]['short_name']);
    }
}
