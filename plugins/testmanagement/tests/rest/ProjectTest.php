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

namespace Tuleap\TestManagement\REST;

use Tuleap\REST\RESTTestDataBuilder;
use Tuleap\TestManagement\REST\Tests\TestManagementRESTTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('TestManagementTest')]
final class ProjectTest extends TestManagementRESTTestCase
{
    public function testGetCampaigns(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', "projects/$this->project_id/testmanagement_campaigns"));

        $this->assertGETCampaings($response);
    }

    public function testGetCampaignsWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "projects/$this->project_id/testmanagement_campaigns"),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETCampaings($response);
    }

    private function assertGETCampaings(\Psr\Http\Message\ResponseInterface $response): void
    {
        $campaigns = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(3, $campaigns);

        $first_campaign = $campaigns[0];
        $this->assertArrayHasKey('id', $first_campaign);
        $this->assertIsString($first_campaign['id']);
        $this->assertEquals($first_campaign['label'], 'Tuleap 7.3');
        $this->assertEquals($first_campaign['status'], 'Open');

        $this->assertArrayHasKey('nb_of_notrun', $first_campaign);
        $this->assertEquals($first_campaign['nb_of_notrun'], 0);

        $this->assertArrayHasKey('nb_of_passed', $first_campaign);
        $this->assertEquals($first_campaign['nb_of_passed'], 2);

        $this->assertArrayHasKey('nb_of_failed', $first_campaign);
        $this->assertEquals($first_campaign['nb_of_failed'], 1);

        $this->assertArrayHasKey('nb_of_blocked', $first_campaign);
        $this->assertEquals($first_campaign['nb_of_blocked'], 0);

        $second_campaign = $campaigns[1];
        $this->assertArrayHasKey('id', $second_campaign);
        $this->assertEquals($second_campaign['label'], 'Tuleap 7.2');
        $this->assertEquals($second_campaign['status'], 'Closed');

        $third_campaign = $campaigns[2];
        $this->assertArrayHasKey('id', $third_campaign);
        $this->assertEquals($third_campaign['label'], 'Tuleap 7.1');
        $this->assertEquals($third_campaign['status'], 'Closed');
    }

    public function testGetDefinitions(): void
    {
        $response    = $this->getResponse($this->request_factory->createRequest('GET', "projects/$this->project_id/testmanagement_definitions"));
        $definitions = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(6, $definitions);
    }

    public function testGetDefinitionsWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "projects/$this->project_id/testmanagement_definitions"),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $definitions = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(6, $definitions);
    }
}
