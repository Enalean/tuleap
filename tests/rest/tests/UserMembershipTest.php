<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

require_once dirname(__FILE__).'/../lib/autoload.php';

/**
 * @group UserMembershipTests
 */
class UserMembershipsTest extends RestBase
{

    protected function getResponse($request, $user_name = REST_TestDataBuilder::TEST_USER_1_NAME)
    {
        return parent::getResponse($request, $user_name);
    }

    public function testGET()
    {
        $response = $this->getResponse(
            $this->client->get('users_memberships/?query=with_ssh_key&limit=3&offset=0'),
            REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $user2_groups = array(
            "site_active",
            "private-member_project_members",
            "ug_102"
        );

        $response_json = $response->json();
        $this->assertCount(1, $response_json);
        $this->assertEquals($response_json[0]["user_groups"], $user2_groups);
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptions()
    {
        $response = $this->getResponse($this->client->options('users_memberships'), REST_TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }
}
