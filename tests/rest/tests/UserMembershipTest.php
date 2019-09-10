<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Guzzle\Http\Message\Response;

/**
 * @group UserMembershipTests
 */
final class UserMembershipsTest extends RestBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    protected function getResponse($request, $user_name = REST_TestDataBuilder::TEST_USER_1_NAME)
    {
        return parent::getResponse($request, $user_name);
    }

    public function testGET(): void
    {
        $response = $this->getResponse(
            $this->client->get('users_memberships/?query=with_ssh_key&limit=3&offset=0'),
            REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertGET($response);
    }

    public function testGETWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->get('users_memberships/?query=with_ssh_key&limit=3&offset=0'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGET($response);
    }

    private function assertGET(Response $response): void
    {
        $user2_groups = array(
            "site_active",
            "private-member_project_members",
            "ug_102"
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response_json = $response->json();
        $this->assertCount(1, $response_json);
        $this->assertEquals($user2_groups, $response_json[0]["user_groups"]);
    }

    public function testOptions(): void
    {
        $response = $this->getResponse(
            $this->client->options('users_memberships'),
            REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertOPTIONS($response);
    }

    public function testOptionsWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->options('users_memberships'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertOPTIONS($response);
    }

    private function assertOPTIONS(Response $response): void
    {
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }
}
